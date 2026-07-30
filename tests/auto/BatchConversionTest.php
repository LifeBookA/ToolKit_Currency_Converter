<?php
/**
 * Test: Batch Conversion
 * Capability: Multiple conversions in one call
 */

require_once __DIR__ . '/../../src/Bootstrap.php';

\Toolkit\Bootstrap::init();

use Toolkit\Currency\Batch\BatchCurrencyConverter;
use Toolkit\Currency\CurrencyConverter;
use Toolkit\Currency\Provider\FixedRateProvider;
use Toolkit\Currency\Cache\MemoryCacheManager;

class BatchConversionTest {
    public function run(): array {
        $results = [];
        
        // Create main converter first
        $mainConverter = new CurrencyConverter(
            new FixedRateProvider(),
            new MemoryCacheManager()
        );
        
        $batchConverter = new BatchCurrencyConverter($mainConverter);
        
        // Test 1: Batch convert multiple amounts using convertBatch method
        try {
            $amounts = [10, 50, 100, 500];
            $batchResults = $batchConverter->convertBatch($amounts, 'USD', 'EUR');
            
            $allValid = count($batchResults) === count($amounts);
            foreach ($batchResults as $result) {
                if ($result->result <= 0) {
                    $allValid = false;
                    break;
                }
            }
            
            $results[] = [
                'name' => 'Batch convert multiple amounts',
                'passed' => $allValid,
                'message' => "Converted " . count($batchResults) . " amounts successfully"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Batch convert multiple amounts',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 2: Batch convert to multiple currencies
        try {
            $currencies = ['EUR', 'GBP', 'IRR'];
            $batchResults = $batchConverter->convertToMultiple(100, 'USD', $currencies);
            
            $allValid = count($batchResults) === count($currencies);
            foreach ($batchResults as $result) {
                if ($result->result <= 0) {
                    $allValid = false;
                    break;
                }
            }
            
            $results[] = [
                'name' => 'Batch convert to multiple currencies',
                'passed' => $allValid,
                'message' => "Converted to " . count($batchResults) . " currencies successfully"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Batch convert to multiple currencies',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        return $results;
    }
}
