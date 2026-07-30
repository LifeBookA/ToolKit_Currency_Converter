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
            Translator::setLocale('en');
            $hello = Translator::translate('hello');
            $conversion_success = Translator::translate('conversion_success');
            
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
            Translator::setLocale('fa');
            $hello = Translator::translate('hello');
            $conversion_success = Translator::translate('conversion_success');
            
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
            Translator::setLocale('ar');
            $hello = Translator::translate('hello');
            
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
            Translator::setLocale('en');
            $en_hello = Translator::translate('hello');
            
            Translator::setLocale('fa');
            $fa_hello = Translator::translate('hello');
            
            Translator::setLocale('en');
            $en_hello2 = Translator::translate('hello');
            
            $results[] = [
                'name' => 'Locale switching',
                'passed' => $en_hello === $en_hello2 && $en_hello !== $fa_hello,
                'message' => "Switched EN -> FA -> EN successfully"
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
