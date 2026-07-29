<?php

namespace Toolkit\Currency\Result;

/**
 * Conversion Result value object
 * 
 * Represents the result of a currency conversion operation
 * 
 * @package Toolkit\Currency\Result
 */
class ConversionResult
{
    /**
     * Original amount
     */
    public readonly float $amount;

    /**
     * Exchange rate used
     */
    public readonly float $rate;

    /**
     * Source currency code
     */
    public readonly string $from;

    /**
     * Target currency code
     */
    public readonly string $to;

    /**
     * Converted result amount
     */
    public readonly float $result;

    /**
     * Timestamp of the conversion
     */
    public readonly int $timestamp;

    /**
     * Whether the rate was retrieved from cache
     */
    public readonly bool $fromCache;

    /**
     * Constructor
     * 
     * @param float $amount Original amount
     * @param float $rate Exchange rate
     * @param string $from Source currency
     * @param string $to Target currency
     * @param int $timestamp Timestamp
     * @param bool $fromCache Whether from cache
     */
    public function __construct(
        float $amount,
        float $rate,
        string $from,
        string $to,
        int $timestamp,
        bool $fromCache = false
    ) {
        $this->amount = $amount;
        $this->rate = $rate;
        $this->from = $from;
        $this->to = $to;
        $this->result = $amount * $rate;
        $this->timestamp = $timestamp;
        $this->fromCache = $fromCache;
    }

    /**
     * Convert to array
     * 
     * @return array Associative array representation
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'rate' => $this->rate,
            'from' => $this->from,
            'to' => $this->to,
            'result' => $this->result,
            'timestamp' => $this->timestamp,
            'fromCache' => $this->fromCache,
        ];
    }

    /**
     * String representation
     * 
     * @return string Formatted string
     */
    public function __toString(): string
    {
        $cacheInfo = $this->fromCache ? ' (from cache)' : '';
        return sprintf(
            '%s %s = %s %s (rate: %s)%s',
            number_format($this->amount, 2),
            $this->from,
            number_format($this->result, 2),
            $this->to,
            number_format($this->rate, 6),
            $cacheInfo
        );
    }
}
