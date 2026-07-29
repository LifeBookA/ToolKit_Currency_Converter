<?php

namespace Toolkit\Currency\Exceptions;

/**
 * Exception for Cache-related errors
 * 
 * Thrown when there are issues with cache operations
 * 
 * @package Toolkit\Currency\Exceptions
 */
class CacheException extends CurrencyException
{
    /**
     * Constructor
     * 
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous Previous exception if any
     */
    public function __construct(
        string $message = "Cache operation failed",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
