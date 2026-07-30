<?php
/**
 * Test: Provider Factory and All Providers
 * Capability: Multiple Exchange Rate Providers
 */

require_once __DIR__ . '/../../src/Bootstrap.php';

use Toolkit\Currency\Provider\ProviderFactory;
use Toolkit\Currency\Provider\FixedRateProvider;
use Toolkit\Currency\Provider\EuropeanCentralBankProvider;
use Toolkit\Currency\Provider\CryptoProvider;
use Toolkit\Currency\Cache\MemoryCacheManager;
use Toolkit\Currency\CurrencyConverter;
use Toolkit\Currency\Config\CurrencyConfig;

class ProviderFactoryTest {
    public function run(): array {
        $results = [];
        
        // Test 1: FixedRateProvider via Factory
        try {
            CurrencyConfig::setProvider('fixed');
            $provider = ProviderFactory::create();
            $rate = $provider->fetchRate('USD', 'EUR');
            
            $results[] = [
                'name' => 'FixedRateProvider via Factory',
                'passed' => $rate > 0,
                'message' => "Fixed rate USD/EUR: {$rate}"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'FixedRateProvider via Factory',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 2: EuropeanCentralBankProvider
        try {
            $ecbProvider = new EuropeanCentralBankProvider();
            $rate = $ecbProvider->fetchRate('USD', 'EUR');
            
            $results[] = [
                'name' => 'EuropeanCentralBankProvider',
                'passed' => $rate > 0,
                'message' => "ECB rate USD/EUR: {$rate}"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'EuropeanCentralBankProvider',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 3: CryptoProvider - BTC to USD
        try {
            $cryptoProvider = new CryptoProvider();
            $rate = $cryptoProvider->fetchRate('BTC', 'USD');
            
            $results[] = [
                'name' => 'CryptoProvider - BTC to USD',
                'passed' => $rate > 1000, // BTC should be worth more than $1000
                'message' => "Crypto rate BTC/USD: {$rate}"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'CryptoProvider - BTC to USD',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 4: CryptoProvider - ETH to EUR
        try {
            $cryptoProvider = new CryptoProvider();
            $rate = $cryptoProvider->fetchRate('ETH', 'EUR');
            
            $results[] = [
                'name' => 'CryptoProvider - ETH to EUR',
                'passed' => $rate > 100, // ETH should be worth more than €100
                'message' => "Crypto rate ETH/EUR: {$rate}"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'CryptoProvider - ETH to EUR',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        return $results;
    }
}
