<?php

namespace Toolkit\Currency\Cache;

use Toolkit\Currency\Exceptions\CacheException;

/**
 * Redis Cache Manager
 * 
 * Implements cache operations using Redis for distributed caching
 * Suitable for production environments with multiple servers
 * 
 * @package Toolkit\Currency\Cache
 */
class RedisCacheManager implements CacheInterface
{
    /**
     * Redis connection instance
     */
    protected \Redis $redis;

    /**
     * Key prefix for all cache entries
     */
    protected string $prefix = 'currency:';

    /**
     * Default TTL in seconds
     */
    protected int $defaultTtl = 3600;

    /**
     * Constructor
     * 
     * @param string $host Redis server host
     * @param int $port Redis server port
     * @param int $timeout Connection timeout in seconds
     * @param string|null $password Redis password (optional)
     * @param int $database Redis database number
     * @throws CacheException If connection fails
     */
    public function __construct(
        string $host = '127.0.0.1',
        int $port = 6379,
        float $timeout = 2.5,
        ?string $password = null,
        int $database = 0
    ) {
        try {
            $this->redis = new \Redis();
            
            if (!$this->redis->connect($host, $port, $timeout)) {
                throw new CacheException("Failed to connect to Redis at {$host}:{$port}");
            }

            // Authenticate if password provided
            if ($password !== null && $password !== '') {
                if (!$this->redis->auth($password)) {
                    throw new CacheException("Failed to authenticate with Redis");
                }
            }

            // Select database
            if (!$this->redis->select($database)) {
                throw new CacheException("Failed to select Redis database {$database}");
            }

        } catch (\RedisException $e) {
            throw new CacheException("Redis connection error: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Set the key prefix
     * 
     * @param string $prefix The prefix to use
     * @return void
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = rtrim($prefix, ':') . ':';
    }

    /**
     * Get the key prefix
     * 
     * @return string The current prefix
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Build full cache key with prefix
     * 
     * @param string $key The base key
     * @return string The prefixed key
     */
    protected function buildKey(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key): mixed
    {
        try {
            $fullKey = $this->buildKey($key);
            $value = $this->redis->get($fullKey);
            
            if ($value === false) {
                return null;
            }

            // Try to unserialize
            $data = @unserialize($value);
            return $data !== false ? $data : $value;
            
        } catch (\RedisException $e) {
            throw new CacheException("Failed to get cache key '{$key}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, int $ttl): void
    {
        try {
            $fullKey = $this->buildKey($key);
            $ttl = $ttl > 0 ? $ttl : $this->defaultTtl;

            // Serialize the value
            $serialized = is_string($value) ? $value : serialize($value);

            if (!$this->redis->setex($fullKey, $ttl, $serialized)) {
                throw new CacheException("Failed to set cache key '{$key}'");
            }

        } catch (\RedisException $e) {
            throw new CacheException("Failed to set cache key '{$key}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): void
    {
        try {
            $fullKey = $this->buildKey($key);
            $this->redis->del($fullKey);
        } catch (\RedisException $e) {
            throw new CacheException("Failed to delete cache key '{$key}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        try {
            // Find all keys with our prefix
            $pattern = $this->prefix . '*';
            $keys = $this->redis->keys($pattern);
            
            if (!empty($keys)) {
                $this->redis->del($keys);
            }
        } catch (\RedisException $e) {
            throw new CacheException("Failed to clear cache: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        try {
            $fullKey = $this->buildKey($key);
            return $this->redis->exists($fullKey) > 0;
        } catch (\RedisException $e) {
            throw new CacheException("Failed to check cache key '{$key}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the Redis instance
     * 
     * @return \Redis The Redis connection
     */
    public function getRedis(): \Redis
    {
        return $this->redis;
    }

    /**
     * Set default TTL
     * 
     * @param int $ttl Default TTL in seconds
     * @return void
     */
    public function setDefaultTtl(int $ttl): void
    {
        $this->defaultTtl = $ttl;
    }

    /**
     * Get default TTL
     * 
     * @return int Default TTL in seconds
     */
    public function getDefaultTtl(): int
    {
        return $this->defaultTtl;
    }

    /**
     * Close the Redis connection
     * 
     * @return void
     */
    public function close(): void
    {
        $this->redis->close();
    }

    /**
     * Test the connection
     * 
     * @return bool True if connection is working
     */
    public function ping(): bool
    {
        try {
            return $this->redis->ping() === true || $this->redis->ping() === '+PONG';
        } catch (\RedisException $e) {
            return false;
        }
    }
}
