#!/usr/bin/env php
<?php
/**
 * Currency Converter - Comprehensive Test Suite
 * 
 * A complete test suite for all Currency Converter functionality.
 * Runs without any external dependencies (no Composer, no PHPUnit).
 * 
 * @package Toolkit\Currency\Tests
 * @version 1.2.0
 */

// Bootstrap the application
require_once __DIR__ . '/../src/Autoloader.php';
\Toolkit\Autoloader::register();

use Toolkit\Currency\CurrencyConverter;
use Toolkit\Currency\Config\CurrencyConfig;
use Toolkit\Currency\Provider\FixedRateProvider;
use Toolkit\Currency\Provider\EuropeanCentralBankProvider;
use Toolkit\Currency\Provider\CryptoProvider;
use Toolkit\Currency\Cache\FileCacheManager;
use Toolkit\Currency\Helpers\CurrencyHelper;
use Toolkit\Currency\Exceptions\InvalidCurrencyException;

// Colors for terminal output
function colorize(string $text, string $color): string {
    return PHP_SAPI === 'cli' ? "\033[{$color}m{$text}\033[0m" : $text;
}

$passed = 0;
$failed = 0;
$total = 0;

function test(string $name, callable $test): void {
    global $passed, $failed, $total;
    $total++;
    
    try {
        $test();
        $passed++;
        echo colorize("  ✓ ", '32') . "$name\n";
    } catch (\Throwable $e) {
        $failed++;
        echo colorize("  ✗ ", '31') . "$name\n";
        echo "    Error: " . colorize($e->getMessage(), '31') . "\n";
    }
}

function assertTrue(bool $condition, string $msg = ''): void {
    if (!$condition) throw new \Exception($msg ?: 'Assertion failed');
}

function assertEquals($expected, $actual, string $msg = ''): void {
    if ($expected !== $actual) {
        throw new \Exception($msg ?: "Expected: " . var_export($expected, true) . ", Got: " . var_export($actual, true));
    }
}

function assertNotNull($value, string $msg = ''): void {
    if ($value === null) throw new \Exception($msg ?: 'Expected non-null value');
}

echo "\n";
echo "==============================================\n";
echo "  Currency Converter v1.2.0 - Test Suite\n";
echo "==============================================\n";
echo "\n";

// ===== Core Tests =====
echo colorize("🧪 Testing: Currency Helper", '36') . "\n";
echo str_repeat('-', 50) . "\n";

test('Normalize currency code to uppercase', function() {
    assertEquals('USD', CurrencyHelper::normalizeCurrencyCode('usd'));
});

test('Normalize currency code with spaces', function() {
    assertEquals('EUR', CurrencyHelper::normalizeCurrencyCode(' eu r '));
});

test('Validate valid currency code', function() {
    assertTrue(CurrencyHelper::isValidCurrencyCode('USD'));
});

test('Reject invalid currency code (short)', function() {
    assertTrue(!CurrencyHelper::isValidCurrencyCode('US'));
});

test('Format amount', function() {
    assertEquals('1,234.57', CurrencyHelper::formatAmount(1234.567, 2));
});

test('Build cache key', function() {
    assertEquals('USD_EUR', CurrencyHelper::buildCacheKey('USD', 'EUR'));
});

echo "\n";

// ===== Fixed Rate Provider Tests =====
echo colorize("🧪 Testing: Fixed Rate Provider", '36') . "\n";
echo str_repeat('-', 50) . "\n";

$fixedProvider = new FixedRateProvider();

test('Fetch USD base rate', function() use ($fixedProvider) {
    assertEquals(1.0, $fixedProvider->fetchRate('USD', 'USD'));
});

test('Fetch EUR rate', function() use ($fixedProvider) {
    assertEquals(0.85, $fixedProvider->fetchRate('USD', 'EUR'));
});

test('Fetch GBP rate', function() use ($fixedProvider) {
    assertEquals(0.75, $fixedProvider->fetchRate('USD', 'GBP'));
});

test('Throw exception for unsupported currency', function() use ($fixedProvider) {
    try {
        $fixedProvider->fetchRate('USD', 'XYZ');
        throw new \Exception('Expected InvalidCurrencyException');
    } catch (InvalidCurrencyException $e) {
        // Expected
    }
});

echo "\n";

// ===== Currency Converter Tests =====
echo colorize("🧪 Testing: Currency Converter", '36') . "\n";
echo str_repeat('-', 50) . "\n";

$converter = new CurrencyConverter($fixedProvider);

test('Convert 100 USD to EUR', function() use ($converter) {
    $result = $converter->convert(100, 'USD', 'EUR');
    assertEquals(85.0, $result->result);
});

test('Get exchange rate', function() use ($converter) {
    assertEquals(0.85, $converter->getRate('USD', 'EUR'));
});

test('Get supported currencies', function() use ($converter) {
    $currencies = $converter->getSupportedCurrencies();
    assertTrue(count($currencies) > 0);
    assertTrue(in_array('USD', $currencies));
});

echo "\n";

// ===== Cache Tests =====
echo colorize("🧪 Testing: File Cache Manager", '36') . "\n";
echo str_repeat('-', 50) . "\n";

$cache = new FileCacheManager();
$testKey = 'TEST_' . time();

test('Set cache value', function() use ($cache, $testKey) {
    $cache->set($testKey, 123.456, 60);
});

test('Check cache exists', function() use ($cache, $testKey) {
    assertTrue($cache->has($testKey));
});

test('Get cache value', function() use ($cache, $testKey) {
    assertEquals(123.456, $cache->get($testKey));
});

test('Delete cache value', function() use ($cache, $testKey) {
    $cache->delete($testKey);
    assertTrue(!$cache->has($testKey));
});

echo "\n";

// ===== ECB Provider Tests =====
echo colorize("🧪 Testing: ECB Provider (Live API)", '36') . "\n";
echo str_repeat('-', 50) . "\n";

$ecbProvider = new EuropeanCentralBankProvider();

test('Fetch EUR to USD rate (live)', function() use ($ecbProvider) {
    $rate = $ecbProvider->fetchRate('EUR', 'USD');
    assertTrue(is_float($rate) && $rate > 0);
});

test('Fetch EUR to GBP rate (live)', function() use ($ecbProvider) {
    $rate = $ecbProvider->fetchRate('EUR', 'GBP');
    assertTrue(is_float($rate) && $rate > 0);
});

echo "\n";

// ===== Crypto Provider Tests =====
echo colorize("🧪 Testing: Crypto Provider", '36') . "\n";
echo str_repeat('-', 50) . "\n";

$cryptoProvider = new CryptoProvider();

test('Fetch BTC rate', function() use ($cryptoProvider) {
    $rate = $cryptoProvider->fetchRate('BTC', 'USD');
    assertTrue(is_float($rate) && $rate > 0);
});

test('Fetch ETH rate', function() use ($cryptoProvider) {
    $rate = $cryptoProvider->fetchRate('ETH', 'USD');
    assertTrue(is_float($rate) && $rate > 0);
});

echo "\n";

// ===== Security Tests =====
echo colorize("🧪 Testing: Security Components", '36') . "\n";
echo str_repeat('-', 50) . "\n";

use Toolkit\Currency\Security\ApiSigner;
use Toolkit\Currency\Security\RateLimiter;

$signer = new ApiSigner('test_secret_key');

test('Generate HMAC signature', function() use ($signer) {
    $sig = $signer->sign('GET', '/api/test', ['param' => 'value']);
    assertTrue(strlen($sig) === 64);
});

test('Verify valid signature', function() use ($signer) {
    $ts = time();
    $sig = $signer->sign('POST', '/api/test', ['data' => 'test'], $ts);
    assertTrue($signer->verify($sig, 'POST', '/api/test', ['data' => 'test'], $ts));
});

$limiter = new RateLimiter(10, 60);
$limiter->reset('rate_test_user');

test('Rate limiter allows first request', function() use ($limiter) {
    assertTrue($limiter->isAllowed('rate_test_user'));
});

test('Rate limiter tracks requests', function() use ($limiter) {
    $limiter->reset('rate_test_user');
    for ($i = 0; $i < 5; $i++) $limiter->isAllowed('rate_test_user');
    $remaining = $limiter->getRemainingRequests('rate_test_user');
    assertTrue($remaining >= 4 && $remaining <= 6, "Expected ~5 remaining, got $remaining");
});

echo "\n";

// ===== Summary =====
echo str_repeat('=', 50) . "\n";
echo colorize("📊 Test Summary", '1;36') . "\n";
echo str_repeat('=', 50) . "\n";
echo "Total:  $total\n";
echo colorize("  Passed: $passed", '32') . "\n";
echo colorize("  Failed: $failed", $failed > 0 ? '31' : '32') . "\n";
echo "\n";

if ($failed === 0) {
    echo colorize("🎉 All tests passed!\n", '32');
    exit(0);
} else {
    echo colorize("❌ Some tests failed!\n", '31');
    exit(1);
}
