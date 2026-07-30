<?php

namespace Toolkit\Currency\Alerts;

use Toolkit\Currency\Contracts\CurrencyConverterInterface;
use Toolkit\Currency\Helpers\CurrencyHelper;
use Toolkit\Currency\Exceptions\InvalidCurrencyException;

/**
 * Rate Alert System
 * 
 * Monitors exchange rates and triggers alerts when target rates are reached
 * Supports multiple notification strategies
 * 
 * @package Toolkit\Currency\Alerts
 */
class RateAlert
{
    /**
     * Source currency
     */
    protected string $from;

    /**
     * Target currency
     */
    protected string $to;

    /**
     * Target rate to watch
     */
    protected float $targetRate;

    /**
     * Comparison operator (>=, <=, ==)
     */
    protected string $operator;

    /**
     * Whether alert is active
     */
    protected bool $active = true;

    /**
     * Number of times alert has been triggered
     */
    protected int $triggerCount = 0;

    /**
     * Last checked rate
     */
    protected ?float $lastCheckedRate = null;

    /**
     * Last check timestamp
     */
    protected ?int $lastCheckedAt = null;

    /**
     * Constructor
     * 
     * @param string $from Source currency
     * @param string $to Target currency
     * @param float $targetRate Target rate to watch
     * @param string $operator Comparison operator (>=, <=, ==)
     */
    public function __construct(
        string $from,
        string $to,
        float $targetRate,
        string $operator = '>='
    ) {
        $this->from = CurrencyHelper::normalizeCurrencyCode($from);
        $this->to = CurrencyHelper::normalizeCurrencyCode($to);
        $this->targetRate = $targetRate;
        
        if (!in_array($operator, ['>=', '<=', '==', '>', '<'])) {
            throw new \InvalidArgumentException("Invalid operator. Use >=, <=, ==, >, or <");
        }
        $this->operator = $operator;
    }

    /**
     * Check if current rate triggers the alert
     * 
     * @param float $currentRate Current exchange rate
     * @return bool True if alert should trigger
     */
    public function check(float $currentRate): bool
    {
        $this->lastCheckedRate = $currentRate;
        $this->lastCheckedAt = time();

        return match($this->operator) {
            '>=' => $currentRate >= $this->targetRate,
            '<=' => $currentRate <= $this->targetRate,
            '>'  => $currentRate > $this->targetRate,
            '<'  => $currentRate < $this->targetRate,
            '==' => abs($currentRate - $this->targetRate) < 0.0001,
            default => false,
        };
    }

    /**
     * Increment trigger count
     * 
     * @return void
     */
    public function trigger(): void
    {
        $this->triggerCount++;
    }

    /**
     * Get from currency
     * 
     * @return string
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * Get to currency
     * 
     * @return string
     */
    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * Get target rate
     * 
     * @return float
     */
    public function getTargetRate(): float
    {
        return $this->targetRate;
    }

    /**
     * Get operator
     * 
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * Check if alert is active
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Set alert active status
     * 
     * @param bool $active Active status
     * @return void
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * Get trigger count
     * 
     * @return int
     */
    public function getTriggerCount(): int
    {
        return $this->triggerCount;
    }

    /**
     * Get last checked rate
     * 
     * @return float|null
     */
    public function getLastCheckedRate(): ?float
    {
        return $this->lastCheckedRate;
    }

    /**
     * Get last check timestamp
     * 
     * @return int|null
     */
    public function getLastCheckedAt(): ?int
    {
        return $this->lastCheckedAt;
    }

    /**
     * Get alert description
     * 
     * @return string
     */
    public function getDescription(): string
    {
        return "Alert when {$this->from}/{$this->to} {$this->operator} {$this->targetRate}";
    }
}
