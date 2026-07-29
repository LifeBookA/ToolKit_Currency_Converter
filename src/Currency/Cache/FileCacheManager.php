<?php

namespace Toolkit\Currency\Cache;

use Toolkit\Currency\Config\CurrencyConfig;
use Toolkit\Currency\Exceptions\CacheException;

/**
 * File-based cache manager
 * 
 * Implements caching using JSON files in a directory
 * 
 * @package Toolkit\Currency\Cache
 */
class FileCacheManager implements CacheInterface
{
    /**
     * Cache directory path
     */
    private string $cacheDir;

    /**
     * Constructor
     * 
     * @param string|null $cacheDir Optional custom cache directory
     * @throws CacheException If cache directory cannot be created
     */
    public function __construct(?string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?? CurrencyConfig::getCacheDir();
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            if (!@mkdir($this->cacheDir, 0755, true)) {
                throw new CacheException("Cannot create cache directory: {$this->cacheDir}");
            }
        }
        
        // Ensure directory is writable
        if (!is_writable($this->cacheDir)) {
            throw new CacheException("Cache directory is not writable: {$this->cacheDir}");
        }
    }

    /**
     * Get the cache file path for a key
     * 
     * @param string $key The cache key
     * @return string Full path to cache file
     */
    private function getFilePath(string $key): string
    {
        // Sanitize key to prevent directory traversal
        $sanitizedKey = preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
        return $this->cacheDir . '/' . $sanitizedKey . '.json';
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key): mixed
    {
        $filePath = $this->getFilePath($key);
        
        if (!file_exists($filePath)) {
            return null;
        }

        $content = @file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        $data = @json_decode($content, true);
        if (!is_array($data) || !isset($data['value']) || !isset($data['expiry'])) {
            // Invalid cache file, delete it
            @unlink($filePath);
            return null;
        }

        // Check if expired
        if ($data['expiry'] < time()) {
            // Expired, delete and return null
            @unlink($filePath);
            return null;
        }

        return $data['value'];
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, int $ttl): void
    {
        $filePath = $this->getFilePath($key);
        
        $data = [
            'value' => $value,
            'expiry' => time() + $ttl,
        ];

        $json = json_encode($data);
        if ($json === false) {
            throw new CacheException("Failed to encode cache data");
        }

        // Use file locking for safe concurrent writes
        $handle = @fopen($filePath, 'c');
        if ($handle === false) {
            throw new CacheException("Failed to open cache file: {$filePath}");
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new CacheException("Failed to acquire lock on cache file");
            }

            // Truncate and write
            ftruncate($handle, 0);
            if (@fwrite($handle, $json) === false) {
                throw new CacheException("Failed to write to cache file");
            }

            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): void
    {
        $filePath = $this->getFilePath($key);
        
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        $files = glob($this->cacheDir . '/*.json');
        if ($files !== false) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Get the cache directory path
     * 
     * @return string
     */
    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }
}
