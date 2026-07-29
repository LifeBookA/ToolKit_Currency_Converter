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
 * Fetches exchange rates from exchangerate-api.com V6 API
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
     * API Key
     */
    private string $apiKey;

    /**
     * API timeout in seconds
     */
    private int $timeout;

    /**
     * Constructor
     * 
     * @param string|null $apiUrl Optional custom API URL
     * @param string|null $apiKey Optional API key (uses config if not provided)
     * @param int|null $timeout Optional custom timeout
     */
    public function __construct(?string $apiUrl = null, ?string $apiKey = null, ?int $timeout = null)
    {
        $this->apiUrl = $apiUrl ?? CurrencyConfig::getApiUrl();
        $this->apiKey = $apiKey ?? CurrencyConfig::getApiKey();
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

        // Handle same currency conversion
        if ($from === $to) {
            return 1.0;
        }

        // Check if API key is set
        if (empty($this->apiKey) || $this->apiKey === 'YOUR_FREE_API_KEY_HERE') {
            throw new ApiException("API key not configured. Please set CurrencyConfig::\$apiKey or pass it to the constructor.");
        }

        // Build API URL for V6: https://v6.exchangerate-api.com/v6/{API_KEY}/latest/{BASE}
        $url = sprintf(
            '%s%s/latest/%s',
            rtrim($this->apiUrl, '/'),
            $this->apiKey,
            urlencode($from)
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

        // Check for API error result
        if (isset($data['result']) && $data['result'] === 'error') {
            $errorMsg = $data['error-type'] ?? 'Unknown API error';
            throw new ApiException("API error: {$errorMsg}");
        }

        // Extract rate
        if (!isset($data['conversion_rates']) || !is_array($data['conversion_rates'])) {
            throw new ApiException("Invalid API response: missing 'conversion_rates' field");
        }

        if (!isset($data['conversion_rates'][$to])) {
            throw new InvalidCurrencyException($to, "Currency not found in API response");
        }

        return (float) $data['conversion_rates'][$to];
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

        // Check if API key is set
        if (empty($this->apiKey) || $this->apiKey === 'YOUR_FREE_API_KEY_HERE') {
            throw new ApiException("API key not configured. Please set CurrencyConfig::\$apiKey or pass it to the constructor.");
        }

        // Build API URL for V6
        $url = sprintf(
            '%s%s/latest/%s',
            rtrim($this->apiUrl, '/'),
            $this->apiKey,
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

        // Check for API error result
        if (isset($data['result']) && $data['result'] === 'error') {
            $errorMsg = $data['error-type'] ?? 'Unknown API error';
            throw new ApiException("API error: {$errorMsg}");
        }

        // Extract rates
        if (!isset($data['conversion_rates']) || !is_array($data['conversion_rates'])) {
            throw new ApiException("Invalid API response: missing 'conversion_rates' field");
        }

        return $data['conversion_rates'];
    }
}
