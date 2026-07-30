<?php
/**
 * Test: Security Features (HMAC, Rate Limiter)
 * Capability: Advanced Security
 */

require_once __DIR__ . '/../../src/Bootstrap.php';

use Toolkit\Currency\Security\ApiSigner;
use Toolkit\Currency\Security\RateLimiter;
use Toolkit\Currency\Config\CurrencyConfig;

class SecurityFeaturesTest {
    public function run(): array {
        $results = [];
        
        // Test 1: HMAC API Signer
        try {
            $signer = new ApiSigner('test_secret_key');
            $data = ['amount' => 100, 'from' => 'USD', 'to' => 'EUR'];
            $signature = $signer->sign($data);
            $isValid = $signer->verify($data, $signature);
            
            $results[] = [
                'name' => 'HMAC API Signer - Sign and Verify',
                'passed' => $isValid && strlen($signature) > 0,
                'message' => "Signature generated: " . substr($signature, 0, 20) . "..."
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'HMAC API Signer - Sign and Verify',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 2: Rate Limiter - Allow requests under limit
        try {
            $limiter = new RateLimiter(5, 60); // 5 requests per 60 seconds
            $userId = 'rate_test_' . time();
            
            $allowed = true;
            for ($i = 0; $i < 3; $i++) {
                if (!$limiter->isAllowed($userId)) {
                    $allowed = false;
                    break;
                }
            }
            
            $remaining = $limiter->getRemainingRequests($userId);
            
            $results[] = [
                'name' => 'Rate Limiter - Allow requests under limit',
                'passed' => $allowed && $remaining >= 0,
                'message' => "3 requests allowed, {$remaining} remaining"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Rate Limiter - Allow requests under limit',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 3: Rate Limiter - Block requests over limit
        try {
            $limiter = new RateLimiter(2, 60); // 2 requests per 60 seconds
            $userId = 'rate_limit_test_' . time();
            
            // Make 2 requests (should be allowed)
            $limiter->recordRequest($userId);
            $limiter->recordRequest($userId);
            
            // Third request should be blocked
            $isBlocked = !$limiter->isAllowed($userId);
            
            $results[] = [
                'name' => 'Rate Limiter - Block requests over limit',
                'passed' => $isBlocked,
                'message' => "Third request correctly blocked"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Rate Limiter - Block requests over limit',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        return $results;
    }
}
