<?php

namespace Toolkit\Currency\Log;

/**
 * Simple File Logger
 *
 * Lightweight file-based logging without external dependencies
 * Replaces PSR-3 logger with native PHP implementation
 *
 * @package Toolkit\Currency\Log
 */
class SimpleLogger
{
    /**
     * Log levels
     */
    public const EMERGENCY = 0;
    public const ALERT = 1;
    public const CRITICAL = 2;
    public const ERROR = 3;
    public const WARNING = 4;
    public const NOTICE = 5;
    public const INFO = 6;
    public const DEBUG = 7;

    /**
     * Log file path
     */
    protected string $logFile;

    /**
     * Minimum log level to record
     */
    protected int $minLevel;

    /**
     * Constructor
     *
     * @param string $logFile Path to log file
     * @param int $minLevel Minimum log level (default: DEBUG)
     */
    public function __construct(string $logFile, int $minLevel = self::DEBUG)
    {
        $this->logFile = $logFile;
        $this->minLevel = $minLevel;

        // Ensure log directory exists
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Log a message
     *
     * @param int $level Log level
     * @param string $message Log message
     * @param array $context Context data
     * @return void
     */
    protected function log(int $level, string $message, array $context = []): void
    {
        if ($level > $this->minLevel) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $levelName = $this->getLevelName($level);
        
        // Replace context placeholders
        foreach ($context as $key => $value) {
            $placeholder = '{' . $key . '}';
            $message = str_replace($placeholder, (string)$value, $message);
        }

        $logEntry = sprintf(
            "[%s] %s: %s\n",
            $timestamp,
            $levelName,
            $message
        );

        // Write to log file with file locking
        file_put_contents(
            $this->logFile,
            $logEntry,
            FILE_APPEND | LOCK_EX
        );
    }

    /**
     * Get level name
     *
     * @param int $level Log level
     * @return string Level name
     */
    protected function getLevelName(int $level): string
    {
        $levels = [
            self::EMERGENCY => 'EMERGENCY',
            self::ALERT => 'ALERT',
            self::CRITICAL => 'CRITICAL',
            self::ERROR => 'ERROR',
            self::WARNING => 'WARNING',
            self::NOTICE => 'NOTICE',
            self::INFO => 'INFO',
            self::DEBUG => 'DEBUG',
        ];

        return $levels[$level] ?? 'UNKNOWN';
    }

    /**
     * Log emergency message
     *
     * @param string $message Log message
     * @param array $context Context data
     * @return void
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Log alert message
     *
     * @param string $message Log message
     * @param array $context Context data
     * @return void
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log(self::ALERT, $message, $context);
    }

    /**
     * Log critical message
     *
     * @param string $message Log message
     * @param array $context Context data
     * @return void
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Log error message
     *
     * @param string $message Log message
     * @param array $context Context data
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Log warning message
     *
     * @param string $message Log message
     * @param array $context Context data
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Log notice message
     *
     * @param string $message Log message
     * @param array $context Context data
     * @return void
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * Log info message
     *
     * @param string $message Log message
     * @param array $context Context data
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Log debug message
     *
     * @param string $message Log message
     * @param array $context Context data
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Clear log file
     *
     * @return void
     */
    public function clear(): void
    {
        file_put_contents($this->logFile, '');
    }
}
