<?php

namespace Toolkit\Currency\CLI;

use Toolkit\Currency\CurrencyConverter;
use Toolkit\Currency\Config\CurrencyConfig;
use Toolkit\Currency\Helpers\CurrencyHelper;
use Toolkit\Currency\Exceptions\CurrencyException;
use Toolkit\Currency\Exceptions\InvalidCurrencyException;

/**
 * Simple CLI Command for Currency Converter
 *
 * Native PHP command-line interface without external dependencies
 * Provides basic currency conversion commands
 *
 * @package Toolkit\Currency\CLI
 */
class CurrencyCommand
{
    /**
     * Currency converter instance
     */
    protected CurrencyConverter $converter;

    /**
     * CLI arguments
     */
    protected array $argv;

    /**
     * Available commands
     */
    protected array $commands = [
        'convert' => 'Convert amount between currencies',
        'rate' => 'Get exchange rate between two currencies',
        'list' => 'List supported currencies',
        'help' => 'Show this help message',
    ];

    /**
     * Constructor
     *
     * @param CurrencyConverter|null $converter Optional converter instance
     */
    public function __construct(?CurrencyConverter $converter = null)
    {
        $this->converter = $converter ?? new CurrencyConverter();
        $this->argv = $_SERVER['argv'] ?? [];
    }

    /**
     * Run the CLI command
     *
     * @return void
     */
    public function run(): void
    {
        if (count($this->argv) < 2) {
            $this->showHelp();
            return;
        }

        $command = $this->argv[1];

        switch ($command) {
            case 'convert':
                $this->handleConvert();
                break;
            case 'rate':
                $this->handleRate();
                break;
            case 'list':
                $this->handleList();
                break;
            case 'help':
            case '--help':
            case '-h':
                $this->showHelp();
                break;
            default:
                $this->error("Unknown command: $command");
                $this->showHelp();
                break;
        }
    }

    /**
     * Handle convert command
     *
     * Usage: php currency.php convert <amount> <from> <to>
     *
     * @return void
     */
    protected function handleConvert(): void
    {
        if (count($this->argv) < 5) {
            $this->error("Usage: currency.php convert <amount> <from> <to>");
            return;
        }

        $amount = (float)$this->argv[2];
        $from = CurrencyHelper::normalizeCurrencyCode($this->argv[3]);
        $to = CurrencyHelper::normalizeCurrencyCode($this->argv[4]);

        try {
            $result = $this->converter->convert($amount, $from, $to);
            
            $this->output(sprintf(
                "%s %s = %s %s (Rate: %s)",
                CurrencyHelper::formatAmount($result->amount),
                $result->from,
                CurrencyHelper::formatAmount($result->result),
                $result->to,
                CurrencyHelper::formatAmount($result->rate, 6)
            ));

            if ($result->fromCache) {
                $this->output("(Result from cache)", 'info');
            }
        } catch (InvalidCurrencyException $e) {
            $this->error("Invalid currency code: " . $e->getMessage());
        } catch (CurrencyException $e) {
            $this->error("Conversion error: " . $e->getMessage());
        }
    }

    /**
     * Handle rate command
     *
     * Usage: php currency.php rate <from> <to>
     *
     * @return void
     */
    protected function handleRate(): void
    {
        if (count($this->argv) < 4) {
            $this->error("Usage: currency.php rate <from> <to>");
            return;
        }

        $from = CurrencyHelper::normalizeCurrencyCode($this->argv[2]);
        $to = CurrencyHelper::normalizeCurrencyCode($this->argv[3]);

        try {
            $rate = $this->converter->getRate($from, $to);
            
            $this->output(sprintf(
                "1 %s = %s %s",
                $from,
                CurrencyHelper::formatAmount($rate, 6),
                $to
            ));
        } catch (InvalidCurrencyException $e) {
            $this->error("Invalid currency code: " . $e->getMessage());
        } catch (CurrencyException $e) {
            $this->error("Rate error: " . $e->getMessage());
        }
    }

    /**
     * Handle list command
     *
     * Usage: php currency.php list
     *
     * @return void
     */
    protected function handleList(): void
    {
        $currencies = $this->converter->getSupportedCurrencies();
        
        $this->output("Supported Currencies (" . count($currencies) . "):");
        $this->output(str_repeat('-', 40));
        
        // Display in columns
        $columns = 5;
        $chunks = array_chunk($currencies, $columns);
        
        foreach ($chunks as $chunk) {
            $line = implode(' | ', array_map(fn($c) => str_pad($c, 6), $chunk));
            $this->output($line);
        }
    }

    /**
     * Show help message
     *
     * @return void
     */
    protected function showHelp(): void
    {
        $this->output("Currency Converter CLI", 'header');
        $this->output(str_repeat('=', 40));
        $this->output("");
        $this->output("Usage: php currency.php <command> [options]");
        $this->output("");
        $this->output("Available Commands:");
        
        foreach ($this->commands as $cmd => $desc) {
            $this->output(sprintf("  %-10s %s", $cmd, $desc));
        }
        
        $this->output("");
        $this->output("Examples:");
        $this->output("  php currency.php convert 100 USD EUR");
        $this->output("  php currency.php rate GBP IRR");
        $this->output("  php currency.php list");
        $this->output("");
    }

    /**
     * Output message
     *
     * @param string $message Message to output
     * @param string $type Message type (info, error, header)
     * @return void
     */
    protected function output(string $message, string $type = 'normal'): void
    {
        switch ($type) {
            case 'error':
                echo "\033[31m❌ $message\033[0m\n";
                break;
            case 'info':
                echo "\033[33mℹ️  $message\033[0m\n";
                break;
            case 'success':
                echo "\033[32m✅ $message\033[0m\n";
                break;
            case 'header':
                echo "\033[1m\033[36m$message\033[0m\n";
                break;
            default:
                echo "$message\n";
                break;
        }
    }

    /**
     * Output error message
     *
     * @param string $message Error message
     * @return void
     */
    protected function error(string $message): void
    {
        $this->output($message, 'error');
    }
}
