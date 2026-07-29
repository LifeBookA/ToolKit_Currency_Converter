<?php

namespace Toolkit\Currency\Exceptions;

/**
 * Exception for Invalid Currency errors
 * 
 * Thrown when an invalid or unsupported currency code is provided
 * 
 * @package Toolkit\Currency\Exceptions
 */
class InvalidCurrencyException extends CurrencyException
{
    /**
     * Constructor
     * 
     * @param string $currency The invalid currency code
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous Previous exception if any
     */
    public function __construct(
        string $currency = '',
        string $message = "Invalid currency code",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $fullMessage = $currency ? "{$message}: {$currency}" : $message;
        parent::__construct($fullMessage, $code, $previous);
    }
}
