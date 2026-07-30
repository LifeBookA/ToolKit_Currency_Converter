<?php
/**
 * Test: Rate Alerts and Daemon Mode
 * Capability: Automated Monitoring
 */

require_once __DIR__ . '/../../src/Bootstrap.php';

use Toolkit\Currency\Alerts\RateAlert;
use Toolkit\Currency\Alerts\RateAlertManager;
use Toolkit\Currency\Config\CurrencyConfig;

class AlertsAndDaemonTest {
    public function run(): array {
        $results = [];
        
        // Ensure alerts directory exists
        $alertDir = CurrencyConfig::getCacheDir() . '/alerts';
        if (!is_dir($alertDir)) {
            mkdir($alertDir, 0755, true);
        }
        
        // Test 1: Create Rate Alert
        try {
            $alert = new RateAlert('USD', 'EUR', 0.90, '>=');
            
            $results[] = [
                'name' => 'Create Rate Alert',
                'passed' => $alert->getFrom() === 'USD' && 
                           $alert->getTo() === 'EUR' &&
                           $alert->getTargetRate() === 0.90,
                'message' => "Alert created for USD/EUR >= 0.90"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Create Rate Alert',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 2: Save and Load Alert
        try {
            $converter = new \Toolkit\Currency\CurrencyConverter();
            $manager = new RateAlertManager($converter);
            $alert = new RateAlert('GBP', 'USD', 1.20, '<=');
            $manager->addAlert($alert);
            
            $alerts = $manager->getAllAlerts();
            
            $results[] = [
                'name' => 'Save and Load Alert',
                'passed' => count($alerts) >= 1,
                'message' => "Saved and retrieved " . count($alerts) . " alert(s)"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Save and Load Alert',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 3: Check Alert Trigger
        try {
            $alert = new RateAlert('USD', 'EUR', 0.50, '>');
            $currentRate = 0.85;
            
            $isTriggered = $alert->check($currentRate);
            
            $results[] = [
                'name' => 'Check Alert Trigger',
                'passed' => $isTriggered,
                'message' => "Alert correctly triggered (0.85 > 0.50)"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Check Alert Trigger',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        return $results;
    }
}
