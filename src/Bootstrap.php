<?php

namespace Toolkit;

/**
 * Bootstrap class for initializing the Toolkit
 *
 * Sets up autoloader and initializes core components
 *
 * @package Toolkit
 */
class Bootstrap
{
    /**
     * Initialize the Toolkit
     *
     * @return bool True if initialization was successful
     */
    public static function init(): bool
    {
        // First load the Autoloader class directly
        require_once __DIR__ . '/Autoloader.php';
        
        // Register the autoloader
        Autoloader::register();

        return true;
    }

    /**
     * Shutdown the Toolkit
     *
     * @return void
     */
    public static function shutdown(): void
    {
        // Unregister the autoloader
        Autoloader::unregister();
    }
}
