<?php
/**
 * Test: Historical Rates and SVG Chart Generation
 * Capability: Historical Data Analysis
 */

require_once __DIR__ . '/../../src/Bootstrap.php';

use Toolkit\Currency\Historical\HistoricalRateManager;
use Toolkit\Currency\Config\CurrencyConfig;

class HistoricalRatesTest {
    public function run(): array {
        $results = [];
        
        // Ensure historical directory exists
        $histDir = CurrencyConfig::getCacheDir() . '/historical';
        if (!is_dir($histDir)) {
            mkdir($histDir, 0755, true);
        }
        
        // Test 1: Save historical rate
        try {
            $manager = new HistoricalRateManager();
            $success = $manager->saveRate('USD', 'EUR', 0.85, time());
            
            $results[] = [
                'name' => 'Save historical rate',
                'passed' => $success,
                'message' => "Historical rate saved successfully"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Save historical rate',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 2: Get historical rates
        try {
            $manager = new HistoricalRateManager();
            // Save some test data
            $manager->saveRate('USD', 'EUR', 0.84, time() - 86400);
            $manager->saveRate('USD', 'EUR', 0.85, time() - 43200);
            $manager->saveRate('USD', 'EUR', 0.86, time());
            
            $rates = $manager->getHistoricalRates('USD', 'EUR', 3);
            
            $results[] = [
                'name' => 'Get historical rates',
                'passed' => count($rates) >= 1,
                'message' => "Retrieved " . count($rates) . " historical rates"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Get historical rates',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 3: Generate SVG chart
        try {
            $manager = new HistoricalRateManager();
            $svg = $manager->generateChart('USD', 'EUR', 500, 300);
            
            $isValidSvg = strpos($svg, '<svg') !== false && 
                         strpos($svg, '</svg>') !== false &&
                         strlen($svg) > 100;
            
            $results[] = [
                'name' => 'Generate SVG chart',
                'passed' => $isValidSvg,
                'message' => "SVG chart generated (" . strlen($svg) . " bytes)"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Generate SVG chart',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        return $results;
    }
}
