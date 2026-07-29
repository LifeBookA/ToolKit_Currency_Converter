<?php

namespace Toolkit\Currency\Cache;

use Toolkit\Currency\Exceptions\CacheException;

/**
 * Memory Cache Manager
 *
 * Implements in-memory caching using PHP static variables
 * Perfect for single-request scenarios and testing
 * No external dependencies required
 *
 * @package Toolkit\Currency\Cache
 */
class MemoryCacheManager implements CacheInterface
{
    /**
     * Static storage for cache data
     */
    protected static array $storage = [];

    /**
     * Get value from cache
     *
     * @param string $key Cache key
     * @return mixed|null Cached value or null if not found/expired
     */
    public function get(string $key): mixed
    {
        if (!isset(self::$storage[$key])) {
            return null;
        }

        $item = self::$storage[$key];
        
        // Check expiration
        if (isset($item['expiry']) && $item['expiry'] < time()) {
            $this->delete($key);
            return null;
        }

        return $item['value'];
    }

    /**
     * Set value in cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds
     * @return void
     */
    public function set(string $key, $value, int $ttl = 3600): void
    {
        self::$storage[$key] = [
            'value' => $value,
            'expiry' => time() + $ttl
        ];
    }

    /**
     * Delete value from cache
     *
     * @param string $key Cache key
     * @return void
     */
    public function delete(string $key): void
    {
        unset(self::$storage[$key]);
    }

    /**
     * Clear all cache
     *
     * @return void
     */
    public function clear(): void
    {
        self::$storage = [];
    }

    /**
     * Check if key exists in cache
     *
     * @param string $key Cache key
     * @return bool True if exists and not expired
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Get all cached keys
     *
     * @return array List of cache keys
     */
    public function getKeys(): array
    {
        return array_keys(self::$storage);
    }
}
