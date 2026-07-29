<?php

namespace Toolkit\Currency\Config;

/**
 * Configuration class for Currency module
 * 
 * Contains all configurable settings for the currency converter
 * 
 * @package Toolkit\Currency\Config
 */
class CurrencyConfig
{
    /**
     * Directory for cache files
     */
    public static string $cacheDir = __DIR__ . '/../../../cache/currency';

    /**
     * Cache Time To Live in seconds (default: 1 hour)
     */
    public static int $cacheTtl = 3600;

    /**
     * Default source currency
     */
    public static string $defaultFrom = 'USD';

    /**
     * Default target currency
     */
    public static string $defaultTo = 'EUR';

    /**
     * Provider type: 'api' or 'fixed'
     */
    public static string $provider = 'api';

    /**
     * API URL for exchange rates (using free exchangerate-api.com V6)
     * Note: V6 endpoint format is different from V4
     */
    public static string $apiUrl = 'https://v6.exchangerate-api.com/v6/';
    
    /**
     * API Key for exchangerate-api.com (free tier)
     * Set this to your actual API key from https://www.exchangerate-api.com/
     * For demo purposes, we use a placeholder that will be replaced
     */
    public static string $apiKey = 'YOUR_FREE_API_KEY_HERE';

    /**
     * API timeout in seconds
     */
    public static int $apiTimeout = 5;

    /**
     * Fixed exchange rates (for testing/offline mode)
     * Base currency is USD
     */
    public static array $fixedRates = [
        'USD' => 1.0,
        'EUR' => 0.85,
        'GBP' => 0.75,
        'IRR' => 42000.0,
        'JPY' => 110.0,
        'CAD' => 1.25,
        'AUD' => 1.35,
        'CHF' => 0.92,
        'CNY' => 6.45,
        'SEK' => 8.55,
        'NZD' => 1.42,
    ];

    /**
     * Get cache directory
     * 
     * @return string
     */
    public static function getCacheDir(): string
    {
        return self::$cacheDir;
    }

    /**
     * Set cache directory
     * 
     * @param string $dir
     * @return void
     */
    public static function setCacheDir(string $dir): void
    {
        self::$cacheDir = $dir;
    }

    /**
     * Get cache TTL
     * 
     * @return int
     */
    public static function getCacheTtl(): int
    {
        return self::$cacheTtl;
    }

    /**
     * Set cache TTL
     * 
     * @param int $ttl
     * @return void
     */
    public static function setCacheTtl(int $ttl): void
    {
        self::$cacheTtl = $ttl;
    }

    /**
     * Get default source currency
     * 
     * @return string
     */
    public static function getDefaultFrom(): string
    {
        return self::$defaultFrom;
    }

    /**
     * Set default source currency
     * 
     * @param string $currency
     * @return void
     */
    public static function setDefaultFrom(string $currency): void
    {
        self::$defaultFrom = $currency;
    }

    /**
     * Get default target currency
     * 
     * @return string
     */
    public static function getDefaultTo(): string
    {
        return self::$defaultTo;
    }

    /**
     * Set default target currency
     * 
     * @param string $currency
     * @return void
     */
    public static function setDefaultTo(string $currency): void
    {
        self::$defaultTo = $currency;
    }

    /**
     * Get provider type
     * 
     * @return string
     */
    public static function getProvider(): string
    {
        return self::$provider;
    }

    /**
     * Set provider type
     * 
     * @param string $provider
     * @return void
     */
    public static function setProvider(string $provider): void
    {
        self::$provider = $provider;
    }

    /**
     * Get API URL
     * 
     * @return string
     */
    public static function getApiUrl(): string
    {
        return self::$apiUrl;
    }

    /**
     * Set API URL
     * 
     * @param string $url
     * @return void
     */
    public static function setApiUrl(string $url): void
    {
        self::$apiUrl = $url;
    }

    /**
     * Get API Key
     * 
     * @return string
     */
    public static function getApiKey(): string
    {
        return self::$apiKey;
    }

    /**
     * Set API Key
     * 
     * @param string $key
     * @return void
     */
    public static function setApiKey(string $key): void
    {
        self::$apiKey = $key;
    }

    /**
     * Get API timeout
     * 
     * @return int
     */
    public static function getApiTimeout(): int
    {
        return self::$apiTimeout;
    }

    /**
     * Set API timeout
     * 
     * @param int $timeout
     * @return void
     */
    public static function setApiTimeout(int $timeout): void
    {
        self::$apiTimeout = $timeout;
    }

    /**
     * Get fixed rates
     * 
     * @return array
     */
    public static function getFixedRates(): array
    {
        return self::$fixedRates;
    }

    /**
     * Set fixed rates
     * 
     * @param array $rates
     * @return void
     */
    public static function setFixedRates(array $rates): void
    {
        self::$fixedRates = $rates;
    }

    /**
     * Get a specific fixed rate
     * 
     * @param string $currency
     * @return float|null
     */
    public static function getFixedRate(string $currency): ?float
    {
        return self::$fixedRates[$currency] ?? null;
    }
}
