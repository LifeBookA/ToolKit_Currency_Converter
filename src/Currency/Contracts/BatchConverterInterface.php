<?php

namespace Toolkit\Currency\Contracts;

/**
 * Interface for Batch Currency Conversion
 * 
 * Defines the contract for batch conversion operations
 * to optimize multiple conversions with a single API call
 * 
 * @package Toolkit\Currency\Contracts
 */
interface BatchConverterInterface
{
    /**
     * Convert multiple amounts from one currency to another
     * 
     * @param array $amounts Array of amounts to convert
     * @param string $from The source currency code
     * @param string $to The target currency code
     * @return array Array of ConversionResult objects
     * @throws \Toolkit\Currency\Exceptions\CurrencyException If conversion fails
     */
    public function convertBatch(array $amounts, string $from, string $to): array;

    /**
     * Convert a single amount to multiple target currencies
     * 
     * @param float $amount The amount to convert
     * @param string $from The source currency code
     * @param array $toArray Array of target currency codes
     * @return array Array of ConversionResult objects
     * @throws \Toolkit\Currency\Exceptions\CurrencyException If conversion fails
     */
    public function convertToMultiple(float $amount, string $from, array $toArray): array;

    /**
     * Convert multiple amounts to multiple target currencies
     * 
     * @param array $amounts Associative array of [from_currency => [amounts]]
     * @param array $toArray Array of target currency codes
     * @return array Multi-dimensional array of ConversionResult objects
     * @throws \Toolkit\Currency\Exceptions\CurrencyException If conversion fails
     */
    public function convertMultiToMulti(array $amounts, array $toArray): array;

    /**
     * Get all exchange rates for a base currency in a single call
     * 
     * @param string $base The base currency code
     * @return array Associative array of currency codes and their rates
     * @throws \Toolkit\Currency\Exceptions\ApiException If API request fails
     */
    public function getAllRates(string $base): array;
}
