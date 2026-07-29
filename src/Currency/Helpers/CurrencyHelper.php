<?php

namespace Toolkit\Currency\Helpers;

/**
 * Helper class for currency operations
 * 
 * Provides utility methods for currency code validation and formatting
 * 
 * @package Toolkit\Currency\Helpers
 */
class CurrencyHelper
{
    /**
     * Normalize a currency code
     * 
     * Converts to uppercase and removes whitespace
     * 
     * @param string $code The currency code to normalize
     * @return string Normalized currency code
     */
    public static function normalizeCurrencyCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    /**
     * Validate a currency code
     * 
     * Checks if the code is exactly 3 uppercase letters
     * 
     * @param string $code The currency code to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValidCurrencyCode(string $code): bool
    {
        $normalized = self::normalizeCurrencyCode($code);
        return preg_match('/^[A-Z]{3}$/', $normalized) === 1;
    }

    /**
     * Format an amount with decimals
     * 
     * @param float $amount The amount to format
     * @param int $decimals Number of decimal places (default: 2)
     * @return string Formatted amount
     */
    public static function formatAmount(float $amount, int $decimals = 2): string
    {
        return number_format($amount, $decimals);
    }

    /**
     * Build a cache key from two currency codes
     * 
     * @param string $from Source currency code
     * @param string $to Target currency code
     * @return string Cache key in format FROM_TO
     */
    public static function buildCacheKey(string $from, string $to): string
    {
        return self::normalizeCurrencyCode($from) . '_' . self::normalizeCurrencyCode($to);
    }
}
