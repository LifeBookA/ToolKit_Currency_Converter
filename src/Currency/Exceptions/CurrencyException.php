<?php

namespace Toolkit\Currency\Exceptions;

/**
 * Base exception class for Currency module
 * 
 * @package Toolkit\Currency\Exceptions
 */
class CurrencyException extends \Exception
{
    /**
     * Constructor
     * 
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous Previous exception if any
     */
    public function __construct(
        string $message = "Currency operation failed",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
