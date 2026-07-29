<?php

/**
 * Currency Converter Demo
 * 
 * Demonstrates the usage of the Currency Converter module
 * with both API and fixed rate providers, including caching functionality
 */

// Bootstrap the Toolkit
require_once __DIR__ . '/../src/Autoloader.php';
require_once __DIR__ . '/../src/Bootstrap.php';

use Toolkit\Autoloader;
use Toolkit\Bootstrap;
use Toolkit\Currency\Config\CurrencyConfig;
use Toolkit\Currency\CurrencyConverter;
use Toolkit\Currency\Cache\FileCacheManager;
use Toolkit\Currency\Provider\FixedRateProvider;
use Toolkit\Currency\Provider\ExchangeRateHostProvider;

// Initialize
Bootstrap::init();

echo "========================================\n";
echo "   Currency Converter Demo\n";
echo "========================================\n\n";

// Clear cache for fresh demo
$cache = new FileCacheManager();
$cache->clear();
echo "[INFO] Cache cleared for fresh demo\n\n";

// ========================================
// Test 1: Convert USD to EUR (Fixed Rate - Demo Mode)
// ========================================
echo "--- Test 1: Convert 100 USD to EUR (Fixed Rate) ---\n";
try {
    // Use fixed rate provider for demo (no API key required)
    $fixedProvider = new FixedRateProvider();
    $converter = new CurrencyConverter($fixedProvider);
    $result = $converter->convert(100, 'USD', 'EUR');
    
    echo "Amount: {$result->amount} {$result->from}\n";
    echo "Result: {$result->result} {$result->to}\n";
    echo "Rate: {$result->rate}\n";
    echo "From Cache: " . ($result->fromCache ? 'Yes' : 'No') . "\n";
    echo "Timestamp: " . date('Y-m-d H:i:s', $result->timestamp) . "\n";
    echo "String: {$result}\n\n";
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n\n";
}

// ========================================
// Test 2: Convert GBP to IRR (Fixed Rate Provider)
// ========================================
echo "--- Test 2: Convert 50 GBP to IRR (Fixed Rate) ---\n";
try {
    $fixedProvider = new FixedRateProvider();
    $converter = new CurrencyConverter($fixedProvider);
    $result = $converter->convert(50, 'GBP', 'IRR');
    
    echo "Amount: {$result->amount} {$result->from}\n";
    echo "Result: " . number_format($result->result, 2) . " {$result->to}\n";
    echo "Rate: {$result->rate}\n";
    echo "From Cache: " . ($result->fromCache ? 'Yes' : 'No') . "\n";
    echo "String: {$result}\n\n";
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n\n";
}

// ========================================
// Test 3: Convert USD to EUR again (Should use cache)
// ========================================
echo "--- Test 3: Convert 100 USD to EUR again (Cached) ---\n";
try {
    $fixedProvider = new FixedRateProvider();
    $converter = new CurrencyConverter($fixedProvider);
    $result = $converter->convert(100, 'USD', 'EUR');
    
    echo "Amount: {$result->amount} {$result->from}\n";
    echo "Result: {$result->result} {$result->to}\n";
    echo "Rate: {$result->rate}\n";
    echo "From Cache: " . ($result->fromCache ? 'Yes' : 'No') . "\n";
    echo "String: {$result}\n\n";
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n\n";
}

// ========================================
// Test 4: API Provider (with warning if no key)
// ========================================
echo "--- Test 4: API Provider (requires valid API key) ---\n";
echo "[INFO] To use the API provider, get a free API key from:\n";
echo "       https://www.exchangerate-api.com/\n";
echo "[INFO] Then set: CurrencyConfig::setApiKey('YOUR_API_KEY')\n\n";

try {
    // This will fail without a valid API key
    $apiProvider = new ExchangeRateHostProvider();
    $converter = new CurrencyConverter($apiProvider);
    
    $result = $converter->convert(100, 'USD', 'EUR');
    echo "API Rate - 100 USD to EUR:\n";
    echo "Result: {$result->result} {$result->to}\n";
    echo "Rate: {$result->rate}\n";
    echo "String: {$result}\n\n";
    
} catch (Exception $e) {
    echo "[EXPECTED WITHOUT KEY] " . $e->getMessage() . "\n\n";
}

// ========================================
// Test 5: Get Supported Currencies
// ========================================
echo "--- Test 5: Supported Currencies ---\n";
$converter = new CurrencyConverter();
$currencies = $converter->getSupportedCurrencies();
echo "Supported currencies: " . implode(', ', array_slice($currencies, 0, 10));
if (count($currencies) > 10) {
    echo " ... (" . count($currencies) . " total)";
}
echo "\n\n";

// ========================================
// Test 6: Get Exchange Rate
// ========================================
echo "--- Test 6: Get Exchange Rate ---\n";
try {
    $rate = $converter->getRate('USD', 'EUR');
    echo "USD to EUR rate: {$rate}\n\n";
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n\n";
}

// ========================================
// Test 7: Invalid Currency Code
// ========================================
echo "--- Test 7: Invalid Currency Code ---\n";
try {
    $result = $converter->convert(100, 'INVALID', 'EUR');
    echo "Result: {$result}\n\n";
} catch (Exception $e) {
    echo "[EXPECTED ERROR] " . $e->getMessage() . "\n\n";
}

// ========================================
// Summary
// ========================================
echo "========================================\n";
echo "   Demo Complete!\n";
echo "========================================\n";
echo "\nNote: First conversions fetch from API and cache the results.\n";
echo "Subsequent conversions use cached rates (valid for 1 hour by default).\n";
echo "Use FixedRateProvider for offline/testing scenarios.\n";
