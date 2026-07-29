<?php

namespace Toolkit\Currency\Contracts;

use Toolkit\Currency\Result\ConversionResult;

/**
 * Interface for Currency Converter
 * 
 * Defines the contract for currency conversion operations
 * 
 * @package Toolkit\Currency\Contracts
 */
interface CurrencyConverterInterface
{
    /**
     * Convert an amount from one currency to another
     * 
     * @param float $amount The amount to convert
     * @param string $from The source currency code (e.g., 'USD')
     * @param string $to The target currency code (e.g., 'EUR')
     * @return ConversionResult The conversion result object
     * @throws \Toolkit\Currency\Exceptions\CurrencyException If conversion fails
     */
    public function convert(float $amount, string $from, string $to): ConversionResult;

    /**
     * Get the exchange rate between two currencies
     * 
     * @param string $from The source currency code
     * @param string $to The target currency code
     * @return float The exchange rate
     * @throws \Toolkit\Currency\Exceptions\CurrencyException If rate cannot be retrieved
     */
    public function getRate(string $from, string $to): float;

    /**
     * Get list of supported currencies
     * 
     * @return array List of supported currency codes
     */
    public function getSupportedCurrencies(): array;
}
