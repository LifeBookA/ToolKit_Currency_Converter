<?php

namespace Toolkit\Currency\Provider;

use Toolkit\Currency\Contracts\ExchangeRateProviderInterface;
use Toolkit\Currency\Config\CurrencyConfig;
use Toolkit\Currency\Exceptions\InvalidCurrencyException;
use Toolkit\Currency\Helpers\CurrencyHelper;

/**
 * Fixed Rate Provider
 * 
 * Provides fixed exchange rates for testing or offline mode
 * Rates are defined in CurrencyConfig::$fixedRates with USD as base
 * 
 * @package Toolkit\Currency\Provider
 */
class FixedRateProvider implements ExchangeRateProviderInterface
{
    /**
     * Fixed rates array (USD as base)
     */
    private array $rates;

    /**
     * Constructor
     * 
     * @param array|null $rates Optional custom rates array
     */
    public function __construct(?array $rates = null)
    {
        $this->rates = $rates ?? CurrencyConfig::getFixedRates();
    }

    /**
     * {@inheritDoc}
     */
    public function fetchRate(string $from, string $to): float
    {
        // Validate currency codes
        if (!CurrencyHelper::isValidCurrencyCode($from)) {
            throw new InvalidCurrencyException($from, "Invalid source currency code");
        }
        
        if (!CurrencyHelper::isValidCurrencyCode($to)) {
            throw new InvalidCurrencyException($to, "Invalid target currency code");
        }

        $from = CurrencyHelper::normalizeCurrencyCode($from);
        $to = CurrencyHelper::normalizeCurrencyCode($to);

        // Handle same currency
        if ($from === $to) {
            return 1.0;
        }

        // Get rates relative to USD
        $fromRate = $this->rates[$from] ?? null;
        $toRate = $this->rates[$to] ?? null;

        if ($fromRate === null) {
            throw new InvalidCurrencyException($from, "Currency not supported in fixed rates");
        }

        if ($toRate === null) {
            throw new InvalidCurrencyException($to, "Currency not supported in fixed rates");
        }

        // Calculate cross rate: (USD->to) / (USD->from)
        return $toRate / $fromRate;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchRates(string $base): array
    {
        // Validate base currency
        if (!CurrencyHelper::isValidCurrencyCode($base)) {
            throw new InvalidCurrencyException($base, "Invalid base currency code");
        }

        $base = CurrencyHelper::normalizeCurrencyCode($base);

        // Check if base is supported
        if (!isset($this->rates[$base])) {
            throw new InvalidCurrencyException($base, "Base currency not supported in fixed rates");
        }

        $baseRate = $this->rates[$base];
        $result = [];

        // Calculate all rates relative to the base
        foreach ($this->rates as $currency => $rate) {
            $result[$currency] = $rate / $baseRate;
        }

        return $result;
    }

    /**
     * Get all available currencies
     * 
     * @return array List of currency codes
     */
    public function getSupportedCurrencies(): array
    {
        return array_keys($this->rates);
    }
}
