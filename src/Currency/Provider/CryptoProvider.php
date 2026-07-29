<?php

namespace Toolkit\Currency\Provider;

use Toolkit\Currency\Contracts\ExchangeRateProviderInterface;
use Toolkit\Currency\Config\CurrencyConfig;
use Toolkit\Currency\Exceptions\ApiException;
use Toolkit\Currency\Exceptions\InvalidCurrencyException;
use Toolkit\Currency\Helpers\CurrencyHelper;

/**
 * Cryptocurrency Provider
 * 
 * Fetches cryptocurrency exchange rates from CoinGecko API (free, no key required)
 * Supports BTC, ETH, and other major cryptocurrencies
 * 
 * @package Toolkit\Currency\Provider
 * @link https://www.coingecko.com/api/documentation
 */
class CryptoProvider implements ExchangeRateProviderInterface
{
    /**
     * API base URL
     */
    protected string $apiUrl = 'https://api.coingecko.com/api/v3';

    /**
     * Supported cryptocurrencies
     */
    protected array $supportedCryptos = [
        'BTC' => 'bitcoin',
        'ETH' => 'ethereum',
        'USDT' => 'tether',
        'BNB' => 'binancecoin',
        'XRP' => 'ripple',
        'ADA' => 'cardano',
        'DOGE' => 'dogecoin',
        'SOL' => 'solana',
        'TRX' => 'tron',
        'DOT' => 'polkadot',
    ];

    /**
     * Supported fiat currencies
     */
    protected array $supportedFiats = [
        'USD', 'EUR', 'GBP', 'JPY', 'CNY', 'KRW', 'RUB', 'CAD', 'AUD', 'CHF'
    ];

    /**
     * Cache for rates
     */
    protected array $ratesCache = [];

    /**
     * {@inheritDoc}
     */
    public function fetchRate(string $from, string $to): float
    {
        $from = CurrencyHelper::normalizeCurrencyCode($from);
        $to = CurrencyHelper::normalizeCurrencyCode($to);

        // Same currency
        if ($from === $to) {
            return 1.0;
        }

        // Check cache
        $cacheKey = "{$from}_{$to}";
        if (isset($this->ratesCache[$cacheKey])) {
            return $this->ratesCache[$cacheKey];
        }

        // Determine conversion type
        $isFromCrypto = isset($this->supportedCryptos[$from]);
        $isToCrypto = isset($this->supportedCryptos[$to]);
        $isToFiat = in_array($to, $this->supportedFiats);
        $isFromFiat = in_array($from, $this->supportedFiats);

        try {
            // Crypto to Fiat
            if ($isFromCrypto && $isToFiat) {
                $rate = $this->fetchCryptoToFiat($from, $to);
            }
            // Fiat to Crypto
            elseif ($isFromFiat && $isToCrypto) {
                $rate = 1.0 / $this->fetchCryptoToFiat($to, $from);
            }
            // Crypto to Crypto
            elseif ($isFromCrypto && $isToCrypto) {
                $rate = $this->fetchCryptoToCrypto($from, $to);
            }
            // Unsupported conversion
            else {
                throw new InvalidCurrencyException(
                    $from, 
                    "Conversion from {$from} to {$to} not supported. Use crypto or major fiat."
                );
            }

            // Cache the rate
            $this->ratesCache[$cacheKey] = $rate;
            
            return $rate;

        } catch (\Exception $e) {
            throw new ApiException("Failed to fetch crypto rate: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Fetch cryptocurrency to fiat rate
     * 
     * @param string $crypto Crypto currency code
     * @param string $fiat Fiat currency code
     * @return float Exchange rate
     */
    protected function fetchCryptoToFiat(string $crypto, string $fiat): float
    {
        $coinId = $this->supportedCryptos[$crypto];
        
        $url = "{$this->apiUrl}/simple/price?ids={$coinId}&vs_currencies={$fiat}";
        
        $response = $this->makeRequest($url);
        $data = json_decode($response, true);

        if (!isset($data[$coinId][strtolower($fiat)])) {
            throw new ApiException("Rate not found for {$crypto} to {$fiat}");
        }

        return (float) $data[$coinId][strtolower($fiat)];
    }

    /**
     * Fetch crypto to crypto rate via USD
     * 
     * @param string $from Source crypto
     * @param string $to Target crypto
     * @return float Exchange rate
     */
    protected function fetchCryptoToCrypto(string $from, string $to): float
    {
        // Get both rates in USD
        $fromUsd = $this->fetchCryptoToFiat($from, 'USD');
        $toUsd = $this->fetchCryptoToFiat($to, 'USD');
        
        return $fromUsd / $toUsd;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchRates(string $base): array
    {
        $base = CurrencyHelper::normalizeCurrencyCode($base);
        
        $isCrypto = isset($this->supportedCryptos[$base]);
        $rates = [];

        if ($isCrypto) {
            // Fetch all fiat rates at once
            $coinId = $this->supportedCryptos[$base];
            $fiats = implode(',', array_map('strtolower', $this->supportedFiats));
            
            $url = "{$this->apiUrl}/simple/price?ids={$coinId}&vs_currencies={$fiats}";
            $response = $this->makeRequest($url);
            $data = json_decode($response, true);

            foreach ($this->supportedFiats as $fiat) {
                if (isset($data[$coinId][strtolower($fiat)])) {
                    $rates[$fiat] = (float) $data[$coinId][strtolower($fiat)];
                }
            }

            // Add some crypto rates
            foreach ($this->supportedCryptos as $code => $id) {
                if ($code !== $base) {
                    try {
                        $rates[$code] = $this->fetchRate($base, $code);
                    } catch (\Exception $e) {
                        // Skip if rate unavailable
                    }
                }
            }
        } else {
            // For fiat base, get crypto rates
            foreach ($this->supportedCryptos as $code => $id) {
                try {
                    $rates[$code] = $this->fetchRate($base, $code);
                } catch (\Exception $e) {
                    // Skip if rate unavailable
                }
            }
        }

        return $rates;
    }

    /**
     * Make HTTP request to API
     * 
     * @param string $url API URL
     * @return string Response body
     * @throws ApiException If request fails
     */
    protected function makeRequest(string $url): string
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: Toolkit-Currency-Converter/1.0'
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            throw new ApiException(
                "API request failed (HTTP {$httpCode}): {$error}",
                $httpCode
            );
        }

        return $response;
    }

    /**
     * Get list of supported cryptocurrencies
     * 
     * @return array Array of crypto codes
     */
    public function getSupportedCryptocurrencies(): array
    {
        return array_keys($this->supportedCryptos);
    }

    /**
     * Get list of supported fiat currencies
     * 
     * @return array Array of fiat codes
     */
    public function getSupportedFiatCurrencies(): array
    {
        return $this->supportedFiats;
    }

    /**
     * Check if a currency is supported
     * 
     * @param string $currency Currency code
     * @return bool True if supported
     */
    public function isSupported(string $currency): bool
    {
        $currency = strtoupper($currency);
        return isset($this->supportedCryptos[$currency]) || in_array($currency, $this->supportedFiats);
    }

    /**
     * Clear the rates cache
     * 
     * @return void
     */
    public function clearCache(): void
    {
        $this->ratesCache = [];
    }
}
