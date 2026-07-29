<?php

namespace Toolkit\Currency\Contracts;

/**
 * Interface for Exchange Rate Provider
 * 
 * Defines the contract for fetching exchange rates from various sources
 * 
 * @package Toolkit\Currency\Contracts
 */
interface ExchangeRateProviderInterface
{
    /**
     * Fetch the exchange rate between two currencies
     * 
     * @param string $from The source currency code
     * @param string $to The target currency code
     * @return float The exchange rate
     * @throws \Toolkit\Currency\Exceptions\ApiException If API request fails
     * @throws \Toolkit\Currency\Exceptions\InvalidCurrencyException If currency is not supported
     */
    public function fetchRate(string $from, string $to): float;

    /**
     * Fetch all exchange rates for a base currency
     * 
     * @param string $base The base currency code
     * @return array Associative array of currency codes and their rates
     * @throws \Toolkit\Currency\Exceptions\ApiException If API request fails
     */
    public function fetchRates(string $base): array;
}
