<?php

namespace Toolkit\Currency\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Toolkit\Currency\CurrencyConverter;
use Toolkit\Currency\Provider\FixedRateProvider;
use Toolkit\Currency\Cache\FileCacheManager;
use Toolkit\Currency\Exceptions\InvalidCurrencyException;
use Toolkit\Currency\Exceptions\ApiException;

/**
 * Integration Tests for Currency Converter
 */
class CurrencyConverterTest extends TestCase
{
    protected CurrencyConverter $converter;
    protected FileCacheManager $cache;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache before each test
        $this->cache = new FileCacheManager();
        $this->cache->clear();
        
        // Create converter with FixedRateProvider for reliable testing
        $provider = new FixedRateProvider();
        $this->converter = new CurrencyConverter($provider, $this->cache);
    }

    protected function tearDown(): void
    {
        // Clear cache after each test
        $this->cache->clear();
        parent::tearDown();
    }

    public function testConvertUSDToEUR(): void
    {
        $result = $this->converter->convert(100.0, 'USD', 'EUR');
        
        $this->assertEquals(100.0, $result->amount);
        $this->assertEquals('USD', $result->from);
        $this->assertEquals('EUR', $result->to);
        $this->assertIsFloat($result->rate);
        $this->assertGreaterThan(0, $result->result);
    }

    public function testConvertWithCaching(): void
    {
        // First conversion (should not be from cache)
        $result1 = $this->converter->convert(100.0, 'USD', 'EUR');
        
        // Second conversion (should be from cache)
        $result2 = $this->converter->convert(100.0, 'USD', 'EUR');
        
        $this->assertEquals($result1->rate, $result2->rate);
    }

    public function testGetRate(): void
    {
        $rate = $this->converter->getRate('USD', 'EUR');
        
        $this->assertIsFloat($rate);
        $this->assertGreaterThan(0, $rate);
        $this->assertLessThan(2, $rate); // EUR should be less than USD
    }

    public function testGetSupportedCurrencies(): void
    {
        $currencies = $this->converter->getSupportedCurrencies();
        
        $this->assertIsArray($currencies);
        $this->assertNotEmpty($currencies);
        $this->assertContains('USD', $currencies);
        $this->assertContains('EUR', $currencies);
    }

    public function testInvalidSourceCurrency(): void
    {
        $this->expectException(InvalidCurrencyException::class);
        $this->converter->convert(100.0, 'INVALID', 'EUR');
    }

    public function testInvalidTargetCurrency(): void
    {
        $this->expectException(InvalidCurrencyException::class);
        $this->converter->convert(100.0, 'USD', 'XXX');
    }

    public function testSameCurrencyConversion(): void
    {
        $result = $this->converter->convert(100.0, 'USD', 'USD');
        
        $this->assertEquals(100.0, $result->amount);
        $this->assertEquals(1.0, $result->rate);
        $this->assertEquals(100.0, $result->result);
    }

    public function testConvertGBPToIRR(): void
    {
        $result = $this->converter->convert(50.0, 'GBP', 'IRR');
        
        $this->assertEquals(50.0, $result->amount);
        $this->assertEquals('GBP', $result->from);
        $this->assertEquals('IRR', $result->to);
        $this->assertGreaterThan(0, $result->result);
    }

    public function testBatchConversion(): void
    {
        $batchConverter = new \Toolkit\Currency\Batch\BatchCurrencyConverter($this->converter);
        
        $results = $batchConverter->convertBatch([10, 20, 30], 'USD', 'EUR');
        
        $this->assertCount(3, $results);
        $this->assertInstanceOf(\Toolkit\Currency\Result\ConversionResult::class, $results[0]);
        $this->assertEquals(10.0, $results[0]->amount);
        $this->assertEquals(20.0, $results[1]->amount);
        $this->assertEquals(30.0, $results[2]->amount);
    }

    public function testConvertToMultiple(): void
    {
        $batchConverter = new \Toolkit\Currency\Batch\BatchCurrencyConverter($this->converter);
        
        $results = $batchConverter->convertToMultiple(100.0, 'USD', ['EUR', 'GBP', 'IRR']);
        
        $this->assertCount(3, $results);
        $this->assertEquals('EUR', $results[0]->to);
        $this->assertEquals('GBP', $results[1]->to);
        $this->assertEquals('IRR', $results[2]->to);
    }
}

class CacheManagerTest extends TestCase
{
    protected FileCacheManager $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new FileCacheManager();
        $this->cache->clear();
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
        parent::tearDown();
    }

    public function testSetAndGet(): void
    {
        $this->cache->set('test_key', 'test_value', 3600);
        $value = $this->cache->get('test_key');
        
        $this->assertEquals('test_value', $value);
    }

    public function testHas(): void
    {
        $this->assertFalse($this->cache->has('nonexistent'));
        
        $this->cache->set('test_key', 'value', 3600);
        $this->assertTrue($this->cache->has('test_key'));
    }

    public function testDelete(): void
    {
        $this->cache->set('test_key', 'value', 3600);
        $this->assertTrue($this->cache->has('test_key'));
        
        $this->cache->delete('test_key');
        $this->assertFalse($this->cache->has('test_key'));
    }

    public function testClear(): void
    {
        $this->cache->set('key1', 'value1', 3600);
        $this->cache->set('key2', 'value2', 3600);
        
        $this->cache->clear();
        
        $this->assertFalse($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key2'));
    }

    public function testExpiration(): void
    {
        $this->cache->set('expiring_key', 'value', 1); // 1 second TTL
        $this->assertTrue($this->cache->has('expiring_key'));
        
        sleep(2); // Wait for expiration
        
        $this->assertFalse($this->cache->has('expiring_key'));
        $this->assertNull($this->cache->get('expiring_key'));
    }
}
