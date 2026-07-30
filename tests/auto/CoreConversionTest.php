<?php
/**
 * Test: Core Currency Conversion Logic
 * Capability: Basic Currency Conversion
 */

require_once __DIR__ . '/../../src/Bootstrap.php';

use Toolkit\Currency\CurrencyConverter;
use Toolkit\Currency\Provider\FixedRateProvider;
use Toolkit\Currency\Cache\MemoryCacheManager;

class CoreConversionTest {
    public function run(): array {
        $results = [];
        
        // Test 1: Basic USD to EUR conversion
        $converter = new CurrencyConverter(
            new FixedRateProvider(),
            new MemoryCacheManager()
        );
        
        try {
            $result = $converter->convert(100, 'USD', 'EUR');
            $expectedRate = 0.85;
            $expectedResult = 85.0;
            
            $results[] = [
                'name' => 'Basic USD to EUR conversion',
                'passed' => abs($result->rate - $expectedRate) < 0.001 && 
                           abs($result->result - $expectedResult) < 0.01,
                'message' => "Converted 100 USD to {$result->result} EUR (rate: {$result->rate})"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Basic USD to EUR conversion',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 2: GBP to IRR conversion
        try {
            $result = $converter->convert(50, 'GBP', 'IRR');
            $expectedRate = 42000 / 0.75; // Approximate cross-rate
            $results[] = [
                'name' => 'GBP to IRR conversion',
                'passed' => $result->result > 0,
                'message' => "Converted 50 GBP to {$result->result} IRR"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'GBP to IRR conversion',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 3: Same currency conversion
        try {
            $result = $converter->convert(100, 'USD', 'USD');
            $results[] = [
                'name' => 'Same currency conversion (USD to USD)',
                'passed' => abs($result->rate - 1.0) < 0.001 && 
                           abs($result->result - 100.0) < 0.01,
                'message' => "Converted 100 USD to {$result->result} USD (rate: {$result->rate})"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Same currency conversion',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        return $results;
    }
}
