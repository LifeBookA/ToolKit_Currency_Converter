<?php
/**
 * Test: PSR-3 Logging System
 * Capability: Professional Logging
 */

require_once __DIR__ . '/../../src/Bootstrap.php';

use Toolkit\Currency\Log\SimpleLogger;

class LoggingSystemTest {
    public function run(): array {
        $results = [];
        
        // Test 1: Log info message
        try {
            $logFile = sys_get_temp_dir() . '/test_logger.log';
            if (file_exists($logFile)) unlink($logFile);
            
            $logger = new SimpleLogger($logFile);
            $logger->info('Test info message', ['user' => 'test']);
            
            $content = file_get_contents($logFile);
            $hasInfo = strpos($content, 'INFO') !== false;
            $hasMessage = strpos($content, 'Test info message') !== false;
            
            $results[] = [
                'name' => 'Log info message',
                'passed' => $hasInfo && $hasMessage,
                'message' => "Info message logged successfully"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Log info message',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 2: Log error message
        try {
            $logFile = sys_get_temp_dir() . '/test_logger.log';
            $logger = new SimpleLogger($logFile);
            $logger->error('Test error message', ['code' => 500]);
            
            $content = file_get_contents($logFile);
            $hasError = strpos($content, 'ERROR') !== false;
            
            $results[] = [
                'name' => 'Log error message',
                'passed' => $hasError,
                'message' => "Error message logged successfully"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Log error message',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 3: Log warning message
        try {
            $logFile = sys_get_temp_dir() . '/test_logger_warn.log';
            if (file_exists($logFile)) unlink($logFile);
            
            $logger = new SimpleLogger($logFile);
            $logger->warning('Test warning', ['threshold' => 0.9]);
            
            $content = file_get_contents($logFile);
            $hasWarning = strpos($content, 'WARNING') !== false;
            
            $results[] = [
                'name' => 'Log warning message',
                'passed' => $hasWarning,
                'message' => "Warning message logged successfully"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Log warning message',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        return $results;
    }
}
