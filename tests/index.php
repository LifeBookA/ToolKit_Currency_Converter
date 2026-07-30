<?php
/**
 * Currency Converter Test Suite - Main Runner
 * Version 1.2.0
 */

// First load Bootstrap directly
require_once __DIR__ . '/../src/Bootstrap.php';

// Initialize
\Toolkit\Bootstrap::init();

// ANSI color codes
class Colors {
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const RESET = "\033[0m";
    const BOLD = "\033[1m";
}

$autoTests = [
    'Core Conversion' => 'auto/CoreConversionTest.php',
    'Caching System' => 'auto/CachingSystemTest.php',
    'Provider Factory' => 'auto/ProviderFactoryTest.php',
    'Batch Conversion' => 'auto/BatchConversionTest.php',
    'Security Features' => 'auto/SecurityFeaturesTest.php',
    'Historical Rates' => 'auto/HistoricalRatesTest.php',
    'Export Features' => 'auto/ExportFeaturesTest.php',
    'Alerts & Daemon' => 'auto/AlertsAndDaemonTest.php',
    'Multi-language' => 'auto/MultiLanguageTest.php',
    'Logging System' => 'auto/LoggingSystemTest.php'
];

$visualTests = [
    'Dashboard Renderer' => 'visual/DashboardRendererTest.php',
    'SVG Chart Generator' => 'visual/SvgChartTest.php',
    'PDF Report Preview' => 'visual/PdfReportTest.php',
    'CLI Interface Demo' => 'visual/CliInterfaceTest.php',
    'Web Form Renderer' => 'visual/WebFormTest.php',
    'Rate Alert UI' => 'visual/RateAlertUiTest.php',
    'Historical Data View' => 'visual/HistoricalDataViewTest.php',
    'Currency Selector UI' => 'visual/CurrencySelectorTest.php',
    'Batch Results Table' => 'visual/BatchResultsTableTest.php',
    'Multi-language UI' => 'visual/MultiLanguageUiTest.php',
    'Cache Status Dashboard' => 'visual/CacheStatusDashboardTest.php',
    'Provider Comparison UI' => 'visual/ProviderComparisonUiTest.php'
];

function printHeader() {
    echo Colors::CYAN . Colors::BOLD;
    echo "\n╔══════════════════════════════════════════════════════════╗\n";
    echo "║     CURRENCY CONVERTER v1.2.0 - TEST SUITE RUNNER      ║\n";
    echo "╚══════════════════════════════════════════════════════════╝\n";
    echo Colors::RESET . "\n";
}

function printMenu($autoTests, $visualTests) {
    echo Colors::YELLOW . "\n=== AUTOMATED TESTS (No UI) ===" . Colors::RESET . "\n";
    $i = 1;
    foreach ($autoTests as $name => $file) {
        printf("  [%d] %s\n", $i++, $name);
    }
    
    echo Colors::MAGENTA . "\n=== VISUAL TESTS (Interactive UI) ===" . Colors::RESET . "\n";
    foreach ($visualTests as $name => $file) {
        printf("  [%d] %s\n", $i++, $name);
    }
    
    echo Colors::GREEN . "\n  [A] Run ALL Automated Tests" . Colors::RESET . "\n";
    echo Colors::MAGENTA . "  [V] Run ALL Visual Tests" . Colors::RESET . "\n";
    echo Colors::RED . "  [Q] Quit" . Colors::RESET . "\n\n";
}

function runTest($testClass, $testName) {
    echo Colors::BLUE . "\n▶ Running: {$testName}" . Colors::RESET . "\n";
    echo str_repeat('-', 50) . "\n";
    
    try {
        $test = new $testClass();
        $results = $test->run();
        
        $passed = 0;
        $failed = 0;
        
        foreach ($results as $result) {
            if ($result['passed']) {
                echo Colors::GREEN . "  ✓ PASS: " . Colors::RESET . "{$result['name']}\n";
                echo "    → {$result['message']}\n";
                $passed++;
            } else {
                echo Colors::RED . "  ✗ FAIL: " . Colors::RESET . "{$result['name']}\n";
                echo "    → {$result['message']}\n";
                $failed++;
            }
        }
        
        echo "\n";
        echo Colors::BOLD . "Summary: " . Colors::GREEN . "{$passed} passed" . Colors::RESET;
        if ($failed > 0) {
            echo Colors::RED . ", {$failed} failed" . Colors::RESET;
        }
        echo "\n";
        
        return $failed === 0;
    } catch (Exception $e) {
        echo Colors::RED . "  ✗ ERROR: " . $e->getMessage() . Colors::RESET . "\n";
        return false;
    }
}

function loadTestClass($filePath) {
    if (!file_exists(__DIR__ . '/' . $filePath)) {
        return null;
    }
    
    require_once __DIR__ . '/' . $filePath;
    $className = pathinfo($filePath, PATHINFO_FILENAME);
    
    return class_exists($className) ? $className : null;
}

printHeader();
printMenu($autoTests, $visualTests);

// Detect CLI environment safely
$isCli = php_sapi_name() === 'cli';
$hasStdin = defined('STDIN');

if ($isCli && $hasStdin && isset($argv) && count($argv) > 1) {
    $selection = $argv[1];
} elseif ($isCli && $hasStdin) {
    echo Colors::BOLD . "Enter your choice: " . Colors::RESET;
    $selection = trim(fgets(STDIN));
} else {
    // Web environment - show error or default to menu
    echo Colors::RED . "This test runner must be executed from the command line (CLI).\n";
    echo "Usage: php tests/index.php [option]\n" . Colors::RESET;
    exit(1);
}

$allTests = array_merge($autoTests, $visualTests);
$testNames = array_keys($allTests);
$testFiles = array_values($allTests);

if (strtoupper($selection) === 'Q') {
    echo Colors::YELLOW . "\nGoodbye!\n" . Colors::RESET;
    exit(0);
}

if (strtoupper($selection) === 'A') {
    echo Colors::GREEN . "\nRunning ALL automated tests...\n" . Colors::RESET;
    $totalPassed = 0;
    $totalFailed = 0;
    
    foreach ($autoTests as $name => $file) {
        $className = loadTestClass($file);
        if ($className) {
            if (runTest($className, $name)) {
                $totalPassed++;
            } else {
                $totalFailed++;
            }
        }
    }
    
    echo Colors::BOLD . "\n═══════════════════════════════════════\n";
    echo "FINAL RESULT: {$totalPassed} suites passed";
    if ($totalFailed > 0) {
        echo Colors::RED . ", {$totalFailed} suites failed" . Colors::RESET;
    }
    echo Colors::BOLD . "\n═══════════════════════════════════════\n" . Colors::RESET;
    
    exit($totalFailed > 0 ? 1 : 0);
}

if (strtoupper($selection) === 'V') {
    echo Colors::MAGENTA . "\nRunning ALL visual tests...\n" . Colors::RESET;
    foreach ($visualTests as $name => $file) {
        $className = loadTestClass($file);
        if ($className) {
            runTest($className, $name);
        }
    }
    exit(0);
}

$choice = intval($selection);
$maxIndex = count($autoTests) + count($visualTests);

if ($choice < 1 || $choice > $maxIndex) {
    echo Colors::RED . "Invalid selection!\n" . Colors::RESET;
    exit(1);
}

$index = $choice - 1;
$selectedName = $testNames[$index];
$selectedFile = $testFiles[$index];

$className = loadTestClass($selectedFile);
if (!$className) {
    echo Colors::RED . "Could not load test class from {$selectedFile}\n" . Colors::RESET;
    exit(1);
}

runTest($className, $selectedName);
