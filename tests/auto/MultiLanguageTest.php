<?php
/**
 * Test: Multi-language Support (i18n)
 * Capability: Internationalization
 */

require_once __DIR__ . '/../../src/Bootstrap.php';

use Toolkit\Currency\I18n\Translator;

class MultiLanguageTest {
    public function run(): array {
        $results = [];
        
        // Test 1: English translations
        try {
            $translator = new Translator('en');
            $hello = $translator->trans('hello');
            $conversion_success = $translator->trans('conversion_success');
            
            $results[] = [
                'name' => 'English translations',
                'passed' => strlen($hello) > 0 && strlen($conversion_success) > 0,
                'message' => "EN: '{$hello}', '{$conversion_success}'"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'English translations',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 2: Persian translations
        try {
            $translator = new Translator('fa');
            $hello = $translator->trans('hello');
            $conversion_success = $translator->trans('conversion_success');
            
            $results[] = [
                'name' => 'Persian translations',
                'passed' => strlen($hello) > 0 && strlen($conversion_success) > 0,
                'message' => "FA: '{$hello}', '{$conversion_success}'"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Persian translations',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 3: Arabic translations
        try {
            $translator = new Translator('ar');
            $hello = $translator->trans('hello');
            
            $results[] = [
                'name' => 'Arabic translations',
                'passed' => strlen($hello) > 0,
                'message' => "AR: '{$hello}'"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Arabic translations',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        // Test 4: Locale switching
        try {
            $translator = new Translator('en');
            $en_hello = $translator->trans('hello');
            
            $switched1 = $translator->setLocale('fa');
            $fa_hello = $translator->trans('invalid_currency'); // Use a key with different translations
            
            $switched2 = $translator->setLocale('en');
            $en_hello2 = $translator->trans('invalid_currency'); // Use same key for consistency
            
            // Check that both switches were successful and translations are different
            $results[] = [
                'name' => 'Locale switching',
                'passed' => $switched1 && $switched2 && $en_hello2 !== $fa_hello,
                'message' => "Switched EN -> FA -> EN successfully (EN='{$en_hello2}', FA='{$fa_hello}')"
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Locale switching',
                'passed' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
        
        return $results;
    }
}
