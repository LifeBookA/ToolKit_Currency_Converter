# Currency Converter Module - Detailed Documentation

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Core Components](#core-components)
3. [Design Patterns](#design-patterns)
4. [API Reference](#api-reference)
5. [Advanced Usage](#advanced-usage)
6. [Troubleshooting](#troubleshooting)

---

## Architecture Overview

The Currency Converter module follows a clean, modular architecture with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────┐
│                  CurrencyConverter                       │
│  (Main facade, orchestrates conversion operations)       │
└──────────────┬──────────────────────────────────────────┘
               │
        ┌──────┴──────┐
        │             │
        ▼             ▼
┌──────────────┐  ┌──────────────┐
│  Provider    │  │    Cache     │
│  Interface   │  │   Interface  │
└──────┬───────┘  └──────┬───────┘
       │                 │
  ┌────┴────┐       ┌────┴────┐
  │         │       │         │
  ▼         ▼       ▼         ▼
API      Fixed   File     (Future:
Provider Provider Manager   Redis, etc.)
```

### Key Principles

- **Dependency Injection**: All dependencies are injected via constructor
- **Interface-based Design**: Loose coupling through interfaces
- **Single Responsibility**: Each class has one well-defined purpose
- **Open/Closed Principle**: Easy to extend without modifying existing code

---

## Core Components

### 1. Contracts (Interfaces)

#### CurrencyConverterInterface

Defines the public API for currency conversion:

```php
interface CurrencyConverterInterface
{
    public function convert(float $amount, string $from, string $to): ConversionResult;
    public function getRate(string $from, string $to): float;
    public function getSupportedCurrencies(): array;
}
```

#### ExchangeRateProviderInterface

Defines how exchange rates are fetched:

```php
interface ExchangeRateProviderInterface
{
    public function fetchRate(string $from, string $to): float;
    public function fetchRates(string $base): array;
}
```

#### CacheInterface

Defines cache operations:

```php
interface CacheInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl): void;
    public function delete(string $key): void;
    public function clear(): void;
    public function has(string $key): bool;
}
```

### 2. Providers

#### ExchangeRateHostProvider

Fetches real-time rates from exchangerate.host API:

**Features:**
- cURL-based HTTP client
- 5-second timeout (configurable)
- SSL verification enabled
- Cross-rate calculation for non-USD bases
- JSON response parsing
- Comprehensive error handling

**Usage:**
```php
$provider = new ExchangeRateHostProvider(
    'https://api.exchangerate.host/latest',
    5 // timeout seconds
);
```

#### FixedRateProvider

Provides static exchange rates for testing/offline use:

**Features:**
- Predefined rates in CurrencyConfig
- Cross-rate calculation
- Fast (no network calls)
- Deterministic results

**Usage:**
```php
$provider = new FixedRateProvider([
    'USD' => 1.0,
    'EUR' => 0.85,
    'GBP' => 0.75,
]);
```

### 3. Cache

#### FileCacheManager

Implements file-based caching with JSON storage:

**Features:**
- Automatic directory creation
- File locking for concurrent access
- TTL-based expiration
- Stale cache fallback
- Atomic writes with flock()

**File Structure:**
```
cache/currency/
├── USD_EUR.json
├── GBP_JPY.json
└── ...
```

**File Content:**
```json
{
    "value": 0.85,
    "expiry": 1765123456
}
```

### 4. Configuration

#### CurrencyConfig

Central configuration management:

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$cacheDir` | string | `../../../cache/currency` | Cache directory path |
| `$cacheTtl` | int | `3600` | Cache TTL in seconds |
| `$defaultFrom` | string | `'USD'` | Default source currency |
| `$defaultTo` | string | `'EUR'` | Default target currency |
| `$provider` | string | `'api'` | Provider type ('api' or 'fixed') |
| `$apiUrl` | string | `'https://api.exchangerate.host/latest'` | API endpoint |
| `$apiTimeout` | int | `5` | API timeout in seconds |
| `$fixedRates` | array | `[...]` | Fixed exchange rates |

### 5. Result Object

#### ConversionResult

Immutable value object for conversion results:

```php
$result = $converter->convert(100, 'USD', 'EUR');

// Properties (all readonly)
$result->amount;      // float: Original amount
$result->rate;        // float: Exchange rate used
$result->from;        // string: Source currency
$result->to;          // string: Target currency
$result->result;      // float: Converted amount
$result->timestamp;   // int: Unix timestamp
$result->fromCache;   // bool: Whether rate was cached

// Methods
$result->toArray();   // array: Convert to associative array
$result->__toString();// string: Human-readable format
```

---

## Design Patterns

### 1. Factory Pattern

`ProviderFactory` creates appropriate provider based on configuration:

```php
class ProviderFactory
{
    public static function create(?string $type = null, array $options = []): ExchangeRateProviderInterface
    {
        $type = $type ?? CurrencyConfig::getProvider();
        
        switch ($type) {
            case 'api':
                return new ExchangeRateHostProvider(...);
            case 'fixed':
                return new FixedRateProvider(...);
            default:
                throw new CurrencyException("Invalid provider type");
        }
    }
}
```

### 2. Strategy Pattern

Different providers implement the same interface, allowing runtime switching:

```php
// Use API provider
$converter = new CurrencyConverter(new ExchangeRateHostProvider());

// Switch to fixed provider
$converter = new CurrencyConverter(new FixedRateProvider());
```

### 3. Repository Pattern

Cache acts as a repository for exchange rates:

```php
// Abstract storage mechanism
interface CacheInterface { ... }

// Concrete implementation
class FileCacheManager implements CacheInterface { ... }

// Future implementations possible
class RedisCacheManager implements CacheInterface { ... }
```

### 4. Value Object Pattern

`ConversionResult` is an immutable value object:

- All properties are `readonly`
- No setters, only constructor
- Equality based on values, not identity

---

## API Reference

### CurrencyConverter

#### Constructor

```php
public function __construct(
    ?ExchangeRateProviderInterface $provider = null,
    ?CacheInterface $cache = null,
    array $config = []
)
```

**Parameters:**
- `$provider`: Custom rate provider (optional, uses factory if null)
- `$cache`: Custom cache manager (optional, uses FileCacheManager if null)
- `$config`: Configuration overrides (optional)

#### convert()

```php
public function convert(float $amount, string $from, string $to): ConversionResult
```

**Parameters:**
- `$amount`: Amount to convert
- `$from`: Source currency code (3 letters)
- `$to`: Target currency code (3 letters)

**Returns:** `ConversionResult` object

**Throws:** `InvalidCurrencyException`, `ApiException`, `CurrencyException`

#### getRate()

```php
public function getRate(string $from, string $to): float
```

**Parameters:**
- `$from`: Source currency code
- `$to`: Target currency code

**Returns:** Exchange rate as float

**Throws:** `InvalidCurrencyException`, `ApiException`, `CurrencyException`

#### getSupportedCurrencies()

```php
public function getSupportedCurrencies(): array
```

**Returns:** Array of supported currency codes

---

## Advanced Usage

### Custom Provider Implementation

Create your own provider by implementing the interface:

```php
use Toolkit\Currency\Contracts\ExchangeRateProviderInterface;
use Toolkit\Currency\Exceptions\ApiException;

class MyCustomProvider implements ExchangeRateProviderInterface
{
    public function fetchRate(string $from, string $to): float
    {
        // Your custom logic here
        // e.g., database lookup, another API, etc.
        
        if (!$rateFound) {
            throw new ApiException("Rate not found");
        }
        
        return $rate;
    }
    
    public function fetchRates(string $base): array
    {
        // Return all rates for base currency
        return [...];
    }
}

// Use it
$converter = new CurrencyConverter(new MyCustomProvider());
```

### Custom Cache Implementation

Implement alternative caching strategies:

```php
use Toolkit\Currency\Cache\CacheInterface;

class MemoryCache implements CacheInterface
{
    private array $storage = [];
    
    public function get(string $key): mixed
    {
        return $this->storage[$key]['value'] ?? null;
    }
    
    public function set(string $key, mixed $value, int $ttl): void
    {
        $this->storage[$key] = [
            'value' => $value,
            'expiry' => time() + $ttl,
        ];
    }
    
    // Implement other methods...
}
```

### Batch Conversions

Efficiently convert multiple amounts:

```php
class BatchConverter
{
    private CurrencyConverter $converter;
    
    public function __construct(CurrencyConverter $converter)
    {
        $this->converter = $converter;
    }
    
    public function convertBatch(array $conversions): array
    {
        $results = [];
        
        foreach ($conversions as [$amount, $from, $to]) {
            try {
                $results[] = $this->converter->convert($amount, $from, $to);
            } catch (Exception $e) {
                $results[] = ['error' => $e->getMessage()];
            }
        }
        
        return $results;
    }
}

// Usage
$batch = new BatchConverter(new CurrencyConverter());
$results = $batch->convertBatch([
    [100, 'USD', 'EUR'],
    [50, 'GBP', 'JPY'],
    [1000, 'EUR', 'IRR'],
]);
```

### Rate Monitoring

Track rate changes over time:

```php
class RateMonitor
{
    private CurrencyConverter $converter;
    private string $logFile;
    
    public function __construct(CurrencyConverter $converter, string $logFile)
    {
        $this->converter = $converter;
        $this->logFile = $logFile;
    }
    
    public function logRate(string $from, string $to): void
    {
        $rate = $this->converter->getRate($from, $to);
        $entry = sprintf(
            "[%s] %s/%s: %.6f\n",
            date('Y-m-d H:i:s'),
            $from,
            $to,
            $rate
        );
        file_put_contents($this->logFile, $entry, FILE_APPEND);
    }
}
```

---

## Troubleshooting

### Common Issues

#### 1. "Invalid currency code" Error

**Cause:** Currency code is not exactly 3 uppercase letters

**Solution:**
```php
// Invalid
$converter->convert(100, 'usd', 'eur');  // lowercase
$converter->convert(100, 'US', 'EURO');  // wrong length

// Valid
$converter->convert(100, 'USD', 'EUR');
```

#### 2. API Timeout Errors

**Cause:** Network issues or slow API response

**Solutions:**
- Increase timeout: `CurrencyConfig::$apiTimeout = 10;`
- Use fixed provider for testing
- Implement retry logic

#### 3. Cache Write Errors

**Cause:** Directory permissions or disk space

**Solutions:**
```bash
# Check permissions
ls -la cache/currency/

# Fix permissions
chmod 755 cache/currency/
chown www-data:www-data cache/currency/
```

#### 4. "Currency not supported" Error

**Cause:** Currency not available in fixed rates or API

**Solution:**
- For API: Check if currency exists in API response
- For Fixed: Add to `CurrencyConfig::$fixedRates`

### Debug Mode

Enable debugging for troubleshooting:

```php
// Enable error logging
ini_set('error_log', '/path/to/debug.log');
ini_set('log_errors', 1);

// Log converter operations
class DebugConverter extends CurrencyConverter
{
    public function getRate(string $from, string $to): float
    {
        error_log("Getting rate: {$from} -> {$to}");
        $rate = parent::getRate($from, $to);
        error_log("Rate obtained: {$rate}");
        return $rate;
    }
}
```

### Performance Tips

1. **Use caching**: Reduces API calls significantly
2. **Batch requests**: Get multiple rates in one go when possible
3. **Appropriate TTL**: Balance freshness vs. performance
4. **Fixed provider for tests**: Faster and more reliable than API

---

**Version**: 1.0.0  
**Last Updated**: 2024  
**PHP Version**: 8.2+
