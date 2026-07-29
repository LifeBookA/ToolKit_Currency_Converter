<?php

namespace Toolkit\Currency\Batch;

use Toolkit\Currency\Contracts\BatchConverterInterface;
use Toolkit\Currency\Contracts\CurrencyConverterInterface;
use Toolkit\Currency\Contracts\ExchangeRateProviderInterface;
use Toolkit\Currency\Config\CurrencyConfig;
use Toolkit\Currency\Helpers\CurrencyHelper;
use Toolkit\Currency\Result\ConversionResult;
use Toolkit\Currency\Exceptions\ApiException;
use Toolkit\Currency\Exceptions\CurrencyException;
use Toolkit\Currency\Exceptions\InvalidCurrencyException;

/**
 * Batch Currency Converter
 * 
 * Implements batch conversion operations to optimize API calls
 * by fetching all rates in a single request
 * 
 * @package Toolkit\Currency\Batch
 */
class BatchCurrencyConverter implements BatchConverterInterface
{
    /**
     * Main currency converter instance
     */
    protected CurrencyConverterInterface $converter;

    /**
     * Exchange rate provider
     */
    protected ExchangeRateProviderInterface $provider;

    /**
     * Cache for fetched rates
     */
    protected array $ratesCache = [];

    /**
     * Constructor
     * 
     * @param CurrencyConverterInterface $converter The main converter instance
     */
    public function __construct(CurrencyConverterInterface $converter)
    {
        $this->converter = $converter;
        
        // Get provider from converter using reflection or getter
        if (method_exists($converter, 'getProvider')) {
            $this->provider = $converter->getProvider();
        } else {
            throw new \InvalidArgumentException(
                'Converter must have a getProvider() method'
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function convertBatch(array $amounts, string $from, string $to): array
    {
        // Validate currencies
        if (!CurrencyHelper::isValidCurrencyCode($from)) {
            throw new InvalidCurrencyException($from, "Invalid source currency code");
        }
        if (!CurrencyHelper::isValidCurrencyCode($to)) {
            throw new InvalidCurrencyException($to, "Invalid target currency code");
        }

        $from = CurrencyHelper::normalizeCurrencyCode($from);
        $to = CurrencyHelper::normalizeCurrencyCode($to);

        // Get the rate once
        $rate = $this->getRate($from, $to);

        $results = [];
        foreach ($amounts as $amount) {
            if (!is_numeric($amount) || $amount < 0) {
                throw new \InvalidArgumentException("Invalid amount: {$amount}");
            }

            $results[] = new ConversionResult(
                (float)$amount,
                $rate,
                $from,
                $to,
                time(),
                false
            );
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function convertToMultiple(float $amount, string $from, array $toArray): array
    {
        if (!CurrencyHelper::isValidCurrencyCode($from)) {
            throw new InvalidCurrencyException($from, "Invalid source currency code");
        }

        $from = CurrencyHelper::normalizeCurrencyCode($from);

        // Validate all target currencies
        $toArray = array_map(function($currency) {
            if (!CurrencyHelper::isValidCurrencyCode($currency)) {
                throw new InvalidCurrencyException($currency, "Invalid target currency code");
            }
            return CurrencyHelper::normalizeCurrencyCode($currency);
        }, $toArray);

        // Fetch all rates at once
        $allRates = $this->getAllRates($from);

        $results = [];
        foreach ($toArray as $to) {
            if (!isset($allRates[$to])) {
                throw new ApiException("Rate not available for {$from} to {$to}");
            }

            $rate = $allRates[$to];
            $results[] = new ConversionResult(
                $amount,
                $rate,
                $from,
                $to,
                time(),
                false
            );
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function convertMultiToMulti(array $amounts, array $toArray): array
    {
        $results = [];

        foreach ($amounts as $from => $amountList) {
            if (!CurrencyHelper::isValidCurrencyCode($from)) {
                throw new InvalidCurrencyException($from, "Invalid source currency code");
            }

            $from = CurrencyHelper::normalizeCurrencyCode($from);

            if (!is_array($amountList)) {
                $amountList = [$amountList];
            }

            $results[$from] = $this->convertToMultiple(
                0, // Placeholder, will be overridden
                $from,
                $toArray
            );

            // Apply amounts to results
            $tempResults = [];
            foreach ($amountList as $amount) {
                foreach ($results[$from] as $result) {
                    $tempResults[] = new ConversionResult(
                        (float)$amount,
                        $result->rate,
                        $result->from,
                        $result->to,
                        time(),
                        false
                    );
                }
            }
            $results[$from] = $tempResults;
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllRates(string $base): array
    {
        $base = CurrencyHelper::normalizeCurrencyCode($base);

        // Check cache first
        if (isset($this->ratesCache[$base])) {
            return $this->ratesCache[$base];
        }

        // Fetch from provider
        try {
            $rates = $this->provider->fetchRates($base);
            
            // Cache the rates
            $this->ratesCache[$base] = $rates;
            
            return $rates;
        } catch (ApiException $e) {
            throw $e;
        }
    }

    /**
     * Get exchange rate between two currencies
     * 
     * @param string $from Source currency
     * @param string $to Target currency
     * @return float Exchange rate
     */
    protected function getRate(string $from, string $to): float
    {
        if ($from === $to) {
            return 1.0;
        }

        // Try to get from cached rates
        if (isset($this->ratesCache[$from]) && isset($this->ratesCache[$from][$to])) {
            return $this->ratesCache[$from][$to];
        }

        // Fallback to single rate fetch
        return $this->converter->getRate($from, $to);
    }

    /**
     * Clear the rates cache
     * 
     * @return void
     */
    public function clearRatesCache(): void
    {
        $this->ratesCache = [];
    }
}
