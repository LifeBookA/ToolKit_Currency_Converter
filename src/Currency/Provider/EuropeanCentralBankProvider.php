<?php

namespace Toolkit\Currency\Provider;

use Toolkit\Currency\Contracts\ExchangeRateProviderInterface;
use Toolkit\Currency\Exceptions\ApiException;
use Toolkit\Currency\Exceptions\InvalidCurrencyException;
use Toolkit\Currency\Helpers\CurrencyHelper;

/**
 * European Central Bank Provider
 * 
 * Fetches exchange rates from ECB (European Central Bank) API
 * This is a FREE provider that doesn't require an API key
 * 
 * @package Toolkit\Currency\Provider
 */
class EuropeanCentralBankProvider implements ExchangeRateProviderInterface
{
    /**
     * Frankfurter API URL (reliable, free, no key required)
     */
    private const API_URL = 'https://api.frankfurter.app/latest';
    
    /**
     * API timeout in seconds
     */
    private int $timeout;

    /**
     * Constructor
     * 
     * @param int|null $timeout Optional custom timeout
     */
    public function __construct(?int $timeout = null)
    {
        $this->timeout = $timeout ?? 5;
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

        // Handle same currency conversion
        if ($from === $to) {
            return 1.0;
        }

        // ECB base is always EUR, so we need to calculate cross-rate
        $url = sprintf(
            '%s?base=%s&symbols=%s',
            self::API_URL,
            urlencode('EUR'),
            urlencode($from . ',' . $to)
        );

        // Initialize cURL
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPGET => true,
        ]);

        // Execute request
        $response = curl_exec($ch);
        
        // Check for cURL errors
        if ($response === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new ApiException("cURL error ({$errno}): {$error}", 0);
        }

        // Get HTTP status code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Check HTTP status
        if ($httpCode !== 200) {
            throw new ApiException("HTTP error: {$httpCode}", $httpCode);
        }

        // Parse JSON response
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException("Invalid JSON response: " . json_last_error_msg());
        }

        // Extract rates based on API format
        $rates = [];
        
        if (isset($data['rates']) && is_array($data['rates'])) {
            $rates = $data['rates'];
            // Add base currency with rate 1.0 if not present
            $baseCurrency = $data['base'] ?? 'EUR';
            if (!isset($rates[$baseCurrency])) {
                $rates[$baseCurrency] = 1.0;
            }
        } elseif (isset($data['conversion_rates']) && is_array($data['conversion_rates'])) {
            $rates = $data['conversion_rates'];
        } else {
            throw new ApiException("Invalid API response: missing rates field");
        }

        // Calculate cross-rate: FROM -> TO
        // Formula: rate = (1 / from_rate_in_eur) * to_rate_in_eur
        $fromKey = isset($rates[$from]) ? $from : strtoupper($from);
        $toKey = isset($rates[$to]) ? $to : strtoupper($to);
        
        if (!isset($rates[$fromKey])) {
            throw new InvalidCurrencyException($from, "Currency not found in API response");
        }
        
        if (!isset($rates[$toKey])) {
            throw new InvalidCurrencyException($to, "Currency not found in API response");
        }

        $fromRate = (float) $rates[$fromKey];
        $toRate = (float) $rates[$toKey];

        // Cross-rate calculation
        // If base is EUR: FROM_TO = (1 / FROM_EUR) * TO_EUR
        $rate = (1.0 / $fromRate) * $toRate;

        return $rate;
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

        // ECB base is always EUR
        $url = sprintf(
            '%s?base=%s',
            self::API_URL,
            urlencode('EUR')
        );

        // Initialize cURL
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPGET => true,
        ]);

        // Execute request
        $response = curl_exec($ch);
        
        // Check for cURL errors
        if ($response === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new ApiException("cURL error ({$errno}): {$error}", 0);
        }

        // Get HTTP status code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Check HTTP status
        if ($httpCode !== 200) {
            throw new ApiException("HTTP error: {$httpCode}", $httpCode);
        }

        // Parse JSON response
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException("Invalid JSON response: " . json_last_error_msg());
        }

        // Extract rates
        $rates = [];
        if (isset($data['rates']) && is_array($data['rates'])) {
            $rates = $data['rates'];
        } elseif (isset($data['conversion_rates']) && is_array($data['conversion_rates'])) {
            $rates = $data['conversion_rates'];
        } else {
            throw new ApiException("Invalid API response: missing rates field");
        }

        // If base is not EUR, convert all rates
        if ($base !== 'EUR') {
            $baseRate = $rates[$base] ?? 1.0;
            $convertedRates = [];
            
            foreach ($rates as $currency => $rate) {
                $convertedRates[$currency] = $rate / $baseRate;
            }
            
            return $convertedRates;
        }

        return $rates;
    }
}
