<?php

namespace Toolkit\Currency\Cache;

/**
 * Interface for Cache implementations
 * 
 * Defines the contract for cache operations
 * 
 * @package Toolkit\Currency\Cache
 */
interface CacheInterface
{
    /**
     * Get a value from cache
     * 
     * @param string $key The cache key
     * @return mixed The cached value or null if not found/expired
     */
    public function get(string $key): mixed;

    /**
     * Set a value in cache
     * 
     * @param string $key The cache key
     * @param mixed $value The value to cache
     * @param int $ttl Time to live in seconds
     * @return void
     */
    public function set(string $key, mixed $value, int $ttl): void;

    /**
     * Delete a value from cache
     * 
     * @param string $key The cache key
     * @return void
     */
    public function delete(string $key): void;

    /**
     * Clear all cache
     * 
     * @return void
     */
    public function clear(): void;

    /**
     * Check if a key exists and is not expired
     * 
     * @param string $key The cache key
     * @return bool True if exists and not expired
     */
    public function has(string $key): bool;
}
