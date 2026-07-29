<?php

namespace Toolkit;

/**
 * Autoloader class for Toolkit project
 * 
 * Automatically loads classes based on PSR-4 standard
 * 
 * @package Toolkit
 */
class Autoloader
{
    /**
     * Base directory for the Toolkit namespace
     */
    private const BASE_DIR = __DIR__;

    /**
     * Register the autoloader with SPL
     * 
     * @return void
     */
    public static function register(): void
    {
        spl_autoload_register([self::class, 'autoload']);
    }

    /**
     * Unregister the autoloader from SPL
     * 
     * @return void
     */
    public static function unregister(): void
    {
        spl_autoload_unregister([self::class, 'autoload']);
    }

    /**
     * Autoload a class by its fully qualified name
     * 
     * @param string $class The fully qualified class name
     * @return bool True if the class was loaded, false otherwise
     */
    public static function autoload(string $class): bool
    {
        // Only handle Toolkit namespace
        $prefix = 'Toolkit\\';
        $len = strlen($prefix);
        
        if (strncmp($prefix, $class, $len) !== 0) {
            return false;
        }

        // Get the relative class name
        $relativeClass = substr($class, $len);

        // Replace namespace separators with directory separators
        $file = self::BASE_DIR . '/' . str_replace('\\', '/', $relativeClass) . '.php';

        // If the file exists, require it
        if (file_exists($file)) {
            require $file;
            return true;
        }

        return false;
    }
}
