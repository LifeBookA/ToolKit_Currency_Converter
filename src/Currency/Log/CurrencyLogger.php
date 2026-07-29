<?php

namespace Toolkit\Currency\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Toolkit\Currency\Exceptions\CacheException;

/**
 * PSR-3 Compatible Logger for Currency Converter
 * 
 * Implements file-based logging following PSR-3 standard
 * Can be replaced with any PSR-3 compatible logger (Monolog, etc.)
 * 
 * @package Toolkit\Currency\Log
 */
class CurrencyLogger implements LoggerInterface
{
    /**
     * Log file path
     */
    protected string $logFile;

    /**
     * Minimum log level to record
     */
    protected string $minLevel = LogLevel::DEBUG;

    /**
     * Log level hierarchy
     */
    protected array $levels = [
        LogLevel::DEBUG     => 100,
        LogLevel::INFO      => 200,
        LogLevel::NOTICE    => 250,
        LogLevel::WARNING   => 300,
        LogLevel::ERROR     => 400,
        LogLevel::CRITICAL  => 500,
        LogLevel::ALERT     => 550,
        LogLevel::EMERGENCY => 600,
    ];

    /**
     * Constructor
     * 
     * @param string|null $logFile Path to log file (default: cache/currency/currency.log)
     * @param string $minLevel Minimum log level to record
     * @throws CacheException If log file cannot be created
     */
    public function __construct(?string $logFile = null, string $minLevel = LogLevel::DEBUG)
    {
        if ($logFile === null) {
            $logDir = dirname(__DIR__) . '/../../cache/currency';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            $logFile = $logDir . '/currency.log';
        }

        $this->logFile = $logFile;
        $this->minLevel = $minLevel;

        // Ensure log file exists and is writable
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0755, true)) {
                throw new CacheException("Failed to create log directory: {$logDir}");
            }
        }

        if (file_exists($logFile) && !is_writable($logFile)) {
            throw new CacheException("Log file is not writable: {$logFile}");
        }

        if (!file_exists($logFile) && !is_writable($logDir)) {
            throw new CacheException("Cannot create log file, directory not writable: {$logDir}");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function log($level, $message, array $context = []): void
    {
        // Check if this level should be logged
        if ($this->shouldLog($level)) {
            $formattedMessage = $this->formatMessage($level, $message, $context);
            $this->writeToFile($formattedMessage);
        }
    }

    /**
     * Check if a log level should be recorded
     * 
     * @param string $level The log level to check
     * @return bool True if should log
     */
    protected function shouldLog(string $level): bool
    {
        return ($this->levels[$level] ?? 0) >= ($this->levels[$this->minLevel] ?? 0);
    }

    /**
     * Format a log message
     * 
     * @param string $level Log level
     * @param mixed $message The message
     * @param array $context Context data
     * @return string Formatted log line
     */
    protected function formatMessage(string $level, mixed $message, array $context): string
    {
        $timestamp = date('Y-m-d H:i:s');
        
        // Replace placeholders in message
        if (is_string($message)) {
            foreach ($context as $key => $value) {
                if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                    $message = str_replace('{' . $key . '}', (string)$value, $message);
                }
            }
        } else {
            $message = json_encode($message);
        }

        // Add context if not empty
        $contextStr = '';
        if (!empty($context)) {
            $contextStr = ' ' . json_encode($context);
        }

        return "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;
    }

    /**
     * Write message to log file
     * 
     * @param string $message Formatted message
     * @return void
     */
    protected function writeToFile(string $message): void
    {
        $result = file_put_contents($this->logFile, $message, FILE_APPEND | LOCK_EX);
        if ($result === false) {
            // Silently fail - don't throw exception during logging
            // to avoid infinite loops
            error_log("Failed to write to log file: {$this->logFile}");
        }
    }

    /**
     * Set minimum log level
     * 
     * @param string $level Minimum level to log
     * @return void
     */
    public function setMinLevel(string $level): void
    {
        $this->minLevel = $level;
    }

    /**
     * Get minimum log level
     * 
     * @return string Current minimum level
     */
    public function getMinLevel(): string
    {
        return $this->minLevel;
    }

    /**
     * Get the log file path
     * 
     * @return string Log file path
     */
    public function getLogFile(): string
    {
        return $this->logFile;
    }

    /**
     * Clear the log file
     * 
     * @return void
     */
    public function clear(): void
    {
        file_put_contents($this->logFile, '');
    }

    /**
     * Get recent log entries
     * 
     * @param int $lines Number of lines to retrieve
     * @return array Array of log lines
     */
    public function getRecent(int $lines = 50): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $file = new \SplFileObject($this->logFile);
        $file->seek(PHP_INT_MAX);
        $total = $file->key();

        $start = max(0, $total - $lines);
        $result = [];

        for ($i = $start; $i <= $total; $i++) {
            $file->seek($i);
            $line = $file->current();
            if ($line !== null && trim($line) !== '') {
                $result[] = trim($line);
            }
        }

        return $result;
    }
}
