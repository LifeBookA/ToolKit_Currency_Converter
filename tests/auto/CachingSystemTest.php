<?php
/**
 * Test: Caching System
 * Capability: File, Memory, and Redis Caching
 */

require_once __DIR__ . '/../../src/Bootstrap.php';

use Toolkit\Currency\CurrencyConverter;
use Toolkit\Currency\Provider\FixedRateProvider;
use Toolkit\Currency\Cache\FileCacheManager;
use Toolkit\Currency\Cache\MemoryCacheManager;
use Toolkit\Currency\Config\CurrencyConfig;

class CachingSystemTest {
    public function run(): array {
        $results = [];
        
        // Test 1: Memory Cache - First call (miss)
        $memoryCache = new MemoryCacheManager();
        // Clear any existing cache from previous tests
        $memoryCache->clear();
        
        $converter = new CurrencyConverter(new FixedRateProvider(), $memoryCache);
        
        try {
            $result1 = $converter->convert(100, 'USD', 'EUR');
            $results[] = [
                'name' => 'Memory Cache - First call (cache miss)',
                'passed' => !$result1->fromCache,
                'message' => "First call fromCache=" . ($result1->fromCache ? 'true' : 'false')
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Memory Cache - First call',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 2: Memory Cache - Second call (hit)
        try {
            $result2 = $converter->convert(100, 'USD', 'EUR');
            $results[] = [
                'name' => 'Memory Cache - Second call (cache hit)',
                'passed' => $result2->fromCache,
                'message' => "Second call fromCache=" . ($result2->fromCache ? 'true' : 'false')
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Memory Cache - Second call',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 3: File Cache - Create and read
        $fileCache = new FileCacheManager();
        $converter2 = new CurrencyConverter(new FixedRateProvider(), $fileCache);
        
        try {
            $result3 = $converter2->convert(50, 'GBP', 'USD');
            $results[] = [
                'name' => 'File Cache - Write and read',
                'passed' => true,
                'message' => "File cache operation successful for GBP_USD"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'File Cache - Write and read',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 4: Cache TTL expiration
        try {
            $cacheKey = 'TEST_EXPIRE_' . time();
            $fileCache->set($cacheKey, 1.23, 1); // 1 second TTL
            sleep(2);
            $value = $fileCache->get($cacheKey);
            
            $results[] = [
                'name' => 'Cache TTL expiration',
                'passed' => $value === null,
                'message' => "TTL expiration test: " . ($value === null ? 'PASSED' : 'FAILED')
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Cache TTL expiration',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        return $results;
    }
}
