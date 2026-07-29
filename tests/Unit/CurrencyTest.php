<?php

namespace Toolkit\Currency\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Toolkit\Currency\Config\CurrencyConfig;
use Toolkit\Currency\Helpers\CurrencyHelper;
use Toolkit\Currency\Result\ConversionResult;
use Toolkit\Currency\Exceptions\InvalidCurrencyException;

/**
 * Unit Tests for Currency Helper and Result classes
 */
class CurrencyHelperTest extends TestCase
{
    public function testNormalizeCurrencyCode(): void
    {
        $this->assertEquals('USD', CurrencyHelper::normalizeCurrencyCode('usd'));
        $this->assertEquals('EUR', CurrencyHelper::normalizeCurrencyCode(' eur '));
        $this->assertEquals('GBP', CurrencyHelper::normalizeCurrencyCode('GbP'));
    }

    public function testIsValidCurrencyCode(): void
    {
        $this->assertTrue(CurrencyHelper::isValidCurrencyCode('USD'));
        $this->assertTrue(CurrencyHelper::isValidCurrencyCode('eur'));
        $this->assertFalse(CurrencyHelper::isValidCurrencyCode('US'));
        $this->assertFalse(CurrencyHelper::isValidCurrencyCode('USDD'));
        $this->assertFalse(CurrencyHelper::isValidCurrencyCode('123'));
        $this->assertFalse(CurrencyHelper::isValidCurrencyCode(''));
    }

    public function testFormatAmount(): void
    {
        $this->assertEquals('100.00', CurrencyHelper::formatAmount(100));
        $this->assertEquals('1,234.56', CurrencyHelper::formatAmount(1234.56));
        $this->assertEquals('100', CurrencyHelper::formatAmount(100, 0));
    }

    public function testBuildCacheKey(): void
    {
        $this->assertEquals('USD_EUR', CurrencyHelper::buildCacheKey('USD', 'EUR'));
        $this->assertEquals('GBP_IRR', CurrencyHelper::buildCacheKey('gbp', 'irr'));
    }
}

class ConversionResultTest extends TestCase
{
    public function testConversionResultCreation(): void
    {
        $result = new ConversionResult(100.0, 0.85, 'USD', 'EUR', time(), false);

        $this->assertEquals(100.0, $result->amount);
        $this->assertEquals(0.85, $result->rate);
        $this->assertEquals('USD', $result->from);
        $this->assertEquals('EUR', $result->to);
        $this->assertEquals(85.0, $result->result);
        $this->assertFalse($result->fromCache);
    }

    public function testConversionResultToArray(): void
    {
        $result = new ConversionResult(50.0, 42000.0, 'USD', 'IRR', time(), true);
        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(50.0, $array['amount']);
        $this->assertEquals(42000.0, $array['rate']);
        $this->assertEquals('USD', $array['from']);
        $this->assertEquals('IRR', $array['to']);
        $this->assertEquals(2100000.0, $array['result']);
        $this->assertTrue($array['fromCache']);
    }

    public function testConversionResultToString(): void
    {
        $result = new ConversionResult(100.0, 0.85, 'USD', 'EUR', time(), false);
        $str = (string)$result;

        $this->assertStringContainsString('USD', $str);
        $this->assertStringContainsString('EUR', $str);
        $this->assertStringContainsString('85.00', $str);
    }

    public function testSameCurrencyConversion(): void
    {
        $result = new ConversionResult(100.0, 1.0, 'USD', 'USD', time(), false);
        $this->assertEquals(100.0, $result->result);
        $this->assertEquals(1.0, $result->rate);
    }
}

class FixedRateProviderTest extends TestCase
{
    public function testFixedRateFetch(): void
    {
        $provider = new \Toolkit\Currency\Provider\FixedRateProvider();
        
        // Test with default rates from config
        $rate = $provider->fetchRate('USD', 'EUR');
        $this->assertIsFloat($rate);
        $this->assertGreaterThan(0, $rate);
    }

    public function testFixedRateInvalidCurrency(): void
    {
        $provider = new \Toolkit\Currency\Provider\FixedRateProvider();
        
        $this->expectException(InvalidCurrencyException::class);
        $provider->fetchRate('USD', 'XXX');
    }

    public function testFixedRateSameCurrency(): void
    {
        $provider = new \Toolkit\Currency\Provider\FixedRateProvider();
        $rate = $provider->fetchRate('USD', 'USD');
        $this->assertEquals(1.0, $rate);
    }

    public function testGetSupportedCurrencies(): void
    {
        $provider = new \Toolkit\Currency\Provider\FixedRateProvider();
        $currencies = $provider->getSupportedCurrencies();
        
        $this->assertIsArray($currencies);
        $this->assertContains('USD', $currencies);
        $this->assertContains('EUR', $currencies);
    }
}
