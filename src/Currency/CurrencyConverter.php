<?php

namespace Toolkit\Currency;

use Toolkit\Currency\Contracts\CurrencyConverterInterface;
use Toolkit\Currency\Contracts\ExchangeRateProviderInterface;
use Toolkit\Currency\Cache\CacheInterface;
use Toolkit\Currency\Cache\FileCacheManager;
use Toolkit\Currency\Config\CurrencyConfig;
use Toolkit\Currency\Exceptions\ApiException;
use Toolkit\Currency\Exceptions\CacheException;
use Toolkit\Currency\Exceptions\CurrencyException;
use Toolkit\Currency\Exceptions\InvalidCurrencyException;
use Toolkit\Currency\Helpers\CurrencyHelper;
use Toolkit\Currency\Provider\ProviderFactory;
use Toolkit\Currency\Result\ConversionResult;

/**
 * Currency Converter
 * 
 * Main class for currency conversion operations with caching support
 * 
 * @package Toolkit\Currency
 */
class CurrencyConverter implements CurrencyConverterInterface
{
    /**
     * Exchange rate provider
     */
    protected ExchangeRateProviderInterface $provider;

    /**
     * Cache manager
     */
    protected CacheInterface $cache;

    /**
     * Configuration array
     */
    protected array $config;

    /**
     * Constructor
     * 
     * @param ExchangeRateProviderInterface|null $provider Optional custom provider
     * @param CacheInterface|null $cache Optional custom cache
     * @param array $config Optional configuration overrides
     */
    public function __construct(
        ?ExchangeRateProviderInterface $provider = null,
        ?CacheInterface $cache = null,
        array $config = []
    ) {
        // Use provided or create default provider
        $this->provider = $provider ?? ProviderFactory::create();
        
        // Use provided or create default cache
        $this->cache = $cache ?? new FileCacheManager();
        
        // Merge config with defaults
        $this->config = array_merge([
            'cacheTtl' => CurrencyConfig::getCacheTtl(),
            'defaultFrom' => CurrencyConfig::getDefaultFrom(),
            'defaultTo' => CurrencyConfig::getDefaultTo(),
        ], $config);
    }

    /**
     * {@inheritDoc}
     */
    public function convert(float $amount, string $from, string $to): ConversionResult
    {
        // Validate and normalize currency codes
        if (!CurrencyHelper::isValidCurrencyCode($from)) {
            throw new InvalidCurrencyException($from, "Invalid source currency code");
        }
        
        if (!CurrencyHelper::isValidCurrencyCode($to)) {
            throw new InvalidCurrencyException($to, "Invalid target currency code");
        }

        $from = CurrencyHelper::normalizeCurrencyCode($from);
        $to = CurrencyHelper::normalizeCurrencyCode($to);

        // Get exchange rate with cache info
        $rateInfo = $this->getRateWithCacheInfo($from, $to);
        $rate = $rateInfo['rate'];
        $fromCache = $rateInfo['fromCache'];

        // Create and return result
        return new ConversionResult(
            $amount,
            $rate,
            $from,
            $to,
            time(),
            $fromCache
        );
    }

    /**
     * Get exchange rate with cache information
     * 
     * @param string $from Source currency
     * @param string $to Target currency
     * @return array{rate: float, fromCache: bool}
     */
    private function getRateWithCacheInfo(string $from, string $to): array
    {
        // Build cache key
        $cacheKey = CurrencyHelper::buildCacheKey($from, $to);

        // Try to get from cache first
        try {
            $cachedRate = $this->cache->get($cacheKey);
            if ($cachedRate !== null) {
                return ['rate' => (float) $cachedRate, 'fromCache' => true];
            }
        } catch (CacheException $e) {
            // Log cache error but continue
            error_log("Currency cache read error: " . $e->getMessage());
        }

        // Cache miss or error, fetch from provider
        try {
            $rate = $this->provider->fetchRate($from, $to);
            
            // Store in cache
            try {
                $this->cache->set($cacheKey, $rate, $this->config['cacheTtl']);
            } catch (CacheException $e) {
                // Log cache write error but continue
                error_log("Currency cache write error: " . $e->getMessage());
            }
            
            return ['rate' => $rate, 'fromCache' => false];
        } catch (ApiException $e) {
            // API failed, try to use stale cache if available
            error_log("Currency API error: " . $e->getMessage() . ", attempting to use stale cache");
            
            // Try to read expired cache as fallback
            $staleRate = $this->getStaleCache($cacheKey);
            if ($staleRate !== null) {
                error_log("Using stale cache rate for {$from}_{$to}: {$staleRate}");
                return ['rate' => (float) $staleRate, 'fromCache' => true];
            }
            
            // No stale cache, rethrow exception
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getRate(string $from, string $to): float
    {
        // Validate and normalize currency codes
        if (!CurrencyHelper::isValidCurrencyCode($from)) {
            throw new InvalidCurrencyException($from, "Invalid source currency code");
        }
        
        if (!CurrencyHelper::isValidCurrencyCode($to)) {
            throw new InvalidCurrencyException($to, "Invalid target currency code");
        }

        $from = CurrencyHelper::normalizeCurrencyCode($from);
        $to = CurrencyHelper::normalizeCurrencyCode($to);

        // Build cache key
        $cacheKey = CurrencyHelper::buildCacheKey($from, $to);

        // Try to get from cache first
        try {
            $cachedRate = $this->cache->get($cacheKey);
            if ($cachedRate !== null) {
                return (float) $cachedRate;
            }
        } catch (CacheException $e) {
            // Log cache error but continue
            error_log("Currency cache read error: " . $e->getMessage());
        }

        // Cache miss or error, fetch from provider
        try {
            $rate = $this->provider->fetchRate($from, $to);
            
            // Store in cache
            try {
                $this->cache->set($cacheKey, $rate, $this->config['cacheTtl']);
            } catch (CacheException $e) {
                // Log cache write error but continue
                error_log("Currency cache write error: " . $e->getMessage());
            }
            
            return $rate;
        } catch (ApiException $e) {
            // API failed, try to use stale cache if available
            error_log("Currency API error: " . $e->getMessage() . ", attempting to use stale cache");
            
            // Try to read expired cache as fallback
            $staleRate = $this->getStaleCache($cacheKey);
            if ($staleRate !== null) {
                error_log("Using stale cache rate for {$from}_{$to}: {$staleRate}");
                return (float) $staleRate;
            }
            
            // No stale cache, rethrow exception
            throw $e;
        }
    }

    /**
     * Get stale (expired) cache value as fallback
     * 
     * @param string $key Cache key
     * @return float|null Stale rate or null
     */
    private function getStaleCache(string $key): ?float
    {
        if ($this->cache instanceof FileCacheManager) {
            $filePath = $this->cache->getCacheDir() . '/' . preg_replace('/[^a-zA-Z0-9_]/', '_', $key) . '.json';
            
            if (file_exists($filePath)) {
                $content = @file_get_contents($filePath);
                if ($content !== false) {
                    $data = @json_decode($content, true);
                    if (is_array($data) && isset($data['value'])) {
                        return (float) $data['value'];
                    }
                }
            }
        }
        
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedCurrencies(): array
    {
        if ($this->provider instanceof FixedRateProvider) {
            return $this->provider->getSupportedCurrencies();
        }
        
        // For API provider, return common currencies
        // In a real scenario, this could fetch from the API
        return [
            'USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY',
            'SEK', 'NZD', 'MXN', 'SGD', 'HKD', 'NOK', 'KRW', 'TRY',
            'RUB', 'INR', 'BRL', 'ZAR', 'IRR', 'AED', 'SAR', 'QAR'
        ];
    }

    /**
     * Get the provider instance
     * 
     * @return ExchangeRateProviderInterface
     */
    public function getProvider(): ExchangeRateProviderInterface
    {
        return $this->provider;
    }

    /**
     * Get the cache instance
     * 
     * @return CacheInterface
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }
}
