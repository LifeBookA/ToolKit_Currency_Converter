<?php

namespace Toolkit\Currency\Provider;

use Toolkit\Currency\Contracts\ExchangeRateProviderInterface;
use Toolkit\Currency\Config\CurrencyConfig;
use Toolkit\Currency\Exceptions\ApiException;
use Toolkit\Currency\Exceptions\InvalidCurrencyException;
use Toolkit\Currency\Helpers\CurrencyHelper;

/**
 * Exchange Rate Host API Provider
 * 
 * Fetches exchange rates from exchangerate.host API
 * 
 * @package Toolkit\Currency\Provider
 */
class ExchangeRateHostProvider implements ExchangeRateProviderInterface
{
    /**
     * API URL
     */
    private string $apiUrl;

    /**
     * API timeout in seconds
     */
    private int $timeout;

    /**
     * Constructor
     * 
     * @param string|null $apiUrl Optional custom API URL
     * @param int|null $timeout Optional custom timeout
     */
    public function __construct(?string $apiUrl = null, ?int $timeout = null)
    {
        $this->apiUrl = $apiUrl ?? CurrencyConfig::getApiUrl();
        $this->timeout = $timeout ?? CurrencyConfig::getApiTimeout();
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

        // Build API URL
        $url = sprintf(
            '%s?base=%s&symbols=%s',
            rtrim($this->apiUrl, '/'),
            urlencode($from),
            urlencode($to)
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

        // Extract rate
        if (!isset($data['rates']) || !is_array($data['rates'])) {
            throw new ApiException("Invalid API response: missing 'rates' field");
        }

        if (!isset($data['rates'][$to])) {
            throw new InvalidCurrencyException($to, "Currency not found in API response");
        }

        $rate = (float) $data['rates'][$to];
        
        // Handle same currency conversion
        if ($from === $to) {
            return 1.0;
        }

        // If base is not USD, we need to calculate cross rate
        if ($from !== 'USD') {
            // Fetch rates with USD as base to calculate cross rate
            $usdRates = $this->fetchRates('USD');
            
            if (!isset($usdRates[$from]) || !isset($usdRates[$to])) {
                throw new InvalidCurrencyException($from, "Cannot calculate cross rate");
            }
            
            // Cross rate: (USD->to) / (USD->from)
            $rate = $usdRates[$to] / $usdRates[$from];
        }

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

        // Build API URL
        $url = sprintf(
            '%s?base=%s',
            rtrim($this->apiUrl, '/'),
            urlencode($base)
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
        if (!isset($data['rates']) || !is_array($data['rates'])) {
            throw new ApiException("Invalid API response: missing 'rates' field");
        }

        return $data['rates'];
    }
}
