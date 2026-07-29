<?php

namespace Toolkit\Currency\Exceptions;

/**
 * Exception for API-related errors
 * 
 * Thrown when there are issues with external API communication
 * 
 * @package Toolkit\Currency\Exceptions
 */
class ApiException extends CurrencyException
{
    /**
     * Constructor
     * 
     * @param string $message The exception message
     * @param int $code The HTTP status code if available
     * @param \Throwable|null $previous Previous exception if any
     */
    public function __construct(
        string $message = "API request failed",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
