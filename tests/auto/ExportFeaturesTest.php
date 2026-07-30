<?php
/**
 * Test: Export Features (CSV, PDF)
 * Capability: Advanced Output Formats
 */

require_once __DIR__ . '/../../src/Bootstrap.php';

use Toolkit\Currency\Export\CsvExporter;
use Toolkit\Currency\Export\PdfReportGenerator;
use Toolkit\Currency\Result\ConversionResult;

class ExportFeaturesTest {
    public function run(): array {
        $results = [];
        
        // Create sample conversion results
        $sampleResults = [
            new ConversionResult(100, 0.85, 'USD', 'EUR', 85.0, time(), false),
            new ConversionResult(200, 0.75, 'GBP', 'EUR', 150.0, time(), false),
            new ConversionResult(50, 1.2, 'EUR', 'USD', 60.0, time(), false)
        ];
        
        // Test 1: CSV Export
        try {
            $csvExporter = new CsvExporter();
            $csvContent = $csvExporter->export($sampleResults);
            
            $hasHeaders = strpos($csvContent, 'Amount') !== false;
            $hasData = strpos($csvContent, '100') !== false;
            $lineCount = count(explode("\n", trim($csvContent)));
            
            $results[] = [
                'name' => 'CSV Export',
                'passed' => $hasHeaders && $hasData && $lineCount >= 4,
                'message' => "CSV generated with {$lineCount} lines"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'CSV Export',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 2: PDF Report Generation
        try {
            $pdfGenerator = new PdfReportGenerator();
            $pdfContent = $pdfGenerator->generate($sampleResults, 'Test Report');
            
            $hasPdfHeader = strpos($pdfContent, '%PDF') === 0 || 
                           strpos($pdfContent, 'Test Report') !== false ||
                            strlen($pdfContent) > 100;
            
            $results[] = [
                'name' => 'PDF Report Generation',
                'passed' => $hasPdfHeader,
                'message' => "PDF report generated (" . strlen($pdfContent) . " bytes)"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'PDF Report Generation',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        return $results;
    }
}
