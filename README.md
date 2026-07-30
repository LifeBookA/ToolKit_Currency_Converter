# Currency Converter Module

## 📖 Overview

A powerful and flexible currency conversion module for PHP with built-in file-based caching support. This module provides:

- **Real-time exchange rates** via API (exchangerate-api.com V6)
- **Fixed rate provider** for testing/offline scenarios
- **File-based caching** to reduce API calls and improve performance
- **Multiple providers** with easy switching via configuration
- **Exception handling** for robust error management
- **PSR-4 compliant** autoloading

### Key Features

✅ **18 PHP classes** with full documentation  
✅ **File-based JSON cache** with TTL support  
✅ **Stale cache fallback** when API is unavailable  
✅ **Cross-rate calculation** for any currency pair  
✅ **Value objects** for type-safe results  
✅ **Helper utilities** for currency code validation  

## 📁 Project Structure

```
Toolkit/
├── src/
│   ├── Autoloader.php              # PSR-4 autoloader
│   ├── Bootstrap.php               # Initialization class
│   └── Currency/
│       ├── Contracts/
│       │   ├── CurrencyConverterInterface.php
│       │   └── ExchangeRateProviderInterface.php
│       ├── Provider/
│       │   ├── ExchangeRateHostProvider.php  # API-based provider (V6)
│       │   ├── FixedRateProvider.php         # Fixed rates for testing
│       │   └── ProviderFactory.php           # Provider factory
│       ├── Cache/
│       │   ├── CacheInterface.php
│       │   └── FileCacheManager.php          # JSON file cache
│       ├── Exceptions/
│       │   ├── CurrencyException.php         # Base exception
│       │   ├── ApiException.php              # API errors
│       │   ├── CacheException.php            # Cache errors
│       │   └── InvalidCurrencyException.php  # Invalid currency
│       ├── Config/
│       │   └── CurrencyConfig.php            # Configuration
│       ├── Helpers/
│       │   └── CurrencyHelper.php            # Utility functions
│       ├── Result/
│       │   └── ConversionResult.php          # Result object
│       └── CurrencyConverter.php             # Main converter class
├── examples/
│   └── currency_demo.php           # Usage examples
├── docs/
│   └── currency.md                 # Detailed documentation
├── cache/currency/                 # Cache directory
└── README.md
```

## 🚀 Quick Start

### Installation

No Composer required! Simply include the Bootstrap file:

```php
require_once 'path/to/Toolkit/src/Bootstrap.php';

use Toolkit\Bootstrap;
use Toolkit\Currency\CurrencyConverter;

// Initialize
Bootstrap::init();

// Create converter
$converter = new CurrencyConverter();

// Convert 100 USD to EUR
$result = $converter->convert(100, 'USD', 'EUR');
echo $result; // Output: 100.00 USD = XX.XX EUR (rate: 0.XXXXXX)
```

### Basic Usage

```php
<?php

require_once 'src/Bootstrap.php';

use Toolkit\Bootstrap;
use Toolkit\Currency\CurrencyConverter;

Bootstrap::init();

$converter = new CurrencyConverter();

// Convert currency
$result = $converter->convert(100, 'USD', 'EUR');
echo "Amount: {$result->amount} {$result->from}\n";
echo "Result: {$result->result} {$result->to}\n";
echo "Rate: {$result->rate}\n";
echo "From Cache: " . ($result->fromCache ? 'Yes' : 'No') . "\n";

// Get exchange rate only
$rate = $converter->getRate('GBP', 'JPY');
echo "GBP to JPY rate: {$rate}\n";

// Get supported currencies
$currencies = $converter->getSupportedCurrencies();
print_r($currencies);
```

## ⚙️ Configuration

All settings are managed via `CurrencyConfig`:

```php
use Toolkit\Currency\Config\CurrencyConfig;

// Cache settings
CurrencyConfig::$cacheDir = '/path/to/cache';
CurrencyConfig::$cacheTtl = 3600; // 1 hour

// Default currencies
CurrencyConfig::$defaultFrom = 'USD';
CurrencyConfig::$defaultTo = 'EUR';

// Provider selection ('api' or 'fixed')
CurrencyConfig::$provider = 'api';

// API settings (V6 endpoint)
CurrencyConfig::$apiUrl = 'https://v6.exchangerate-api.com/v6/';
CurrencyConfig::$apiKey = 'YOUR_API_KEY_HERE'; // Get free key from exchangerate-api.com
CurrencyConfig::$apiTimeout = 5;

// Fixed rates (for FixedRateProvider)
CurrencyConfig::$fixedRates = [
    'USD' => 1.0,
    'EUR' => 0.85,
    'GBP' => 0.75,
    'IRR' => 42000.0,
];
```

### Getting an API Key

1. Visit [https://www.exchangerate-api.com/](https://www.exchangerate-api.com/)
2. Sign up for a free account
3. Get your API key from the dashboard
4. Set it in your config: `CurrencyConfig::setApiKey('your-key-here');`

The free tier includes:
- 1,500 API requests/month
- Daily rate updates
- All world currencies

## 🔌 Providers

### API Provider (Default)

Fetches real-time rates from exchangerate-api.com V6:

```php
use Toolkit\Currency\Provider\ExchangeRateHostProvider;
use Toolkit\Currency\CurrencyConverter;
use Toolkit\Currency\Config\CurrencyConfig;

// Set your API key first
CurrencyConfig::setApiKey('your-free-api-key');

$provider = new ExchangeRateHostProvider();
$converter = new CurrencyConverter($provider);
```

### Fixed Rate Provider

For testing or offline use:

```php
use Toolkit\Currency\Provider\FixedRateProvider;
use Toolkit\Currency\CurrencyConverter;

$provider = new FixedRateProvider();
$converter = new CurrencyConverter($provider);

$result = $converter->convert(100, 'USD', 'EUR');
echo $result; // Uses fixed rates from config
```

### Provider Factory

Automatically creates provider based on config:

```php
use Toolkit\Currency\Provider\ProviderFactory;

// Uses CurrencyConfig::$provider
$provider = ProviderFactory::create();

// Force specific provider
$apiProvider = ProviderFactory::create('api');
$fixedProvider = ProviderFactory::create('fixed');
```

## 💾 Caching

The module uses file-based caching to store exchange rates:

- **Location**: `cache/currency/` directory
- **Format**: JSON files (`{FROM_TO}.json`)
- **TTL**: Configurable (default: 3600 seconds)
- **Fallback**: Uses stale cache if API fails

### Cache File Format

```json
{
    "value": 0.85,
    "expiry": 1765123456
}
```

### Manual Cache Management

```php
use Toolkit\Currency\Cache\FileCacheManager;

$cache = new FileCacheManager();

// Check if key exists
if ($cache->has('USD_EUR')) {
    echo "Rate is cached!";
}

// Get cached value
$rate = $cache->get('USD_EUR');

// Set cache value
$cache->set('USD_EUR', 0.85, 3600);

// Delete specific key
$cache->delete('USD_EUR');

// Clear all cache
$cache->clear();
```

## 🎯 Features

### Conversion Result Object

```php
$result = $converter->convert(100, 'USD', 'EUR');

// Access properties
echo $result->amount;     // 100.0
echo $result->rate;       // 0.85
echo $result->from;       // 'USD'
echo $result->to;         // 'EUR'
echo $result->result;     // 85.0
echo $result->timestamp;  // Unix timestamp
echo $result->fromCache;  // true/false

// Convert to array
$data = $result->toArray();

// String representation
echo $result; // "100.00 USD = 85.00 EUR (rate: 0.850000)"
```

### Helper Functions

```php
use Toolkit\Currency\Helpers\CurrencyHelper;

// Normalize currency code
$code = CurrencyHelper::normalizeCurrencyCode(' usd '); // 'USD'

// Validate currency code
CurrencyHelper::isValidCurrencyCode('USD'); // true
CurrencyHelper::isValidCurrencyCode('XX');  // false

// Format amount
CurrencyHelper::formatAmount(1234.567, 2); // "1,234.57"

// Build cache key
CurrencyHelper::buildCacheKey('USD', 'EUR'); // "USD_EUR"
```

## ⚠️ Exception Handling

```php
use Toolkit\Currency\Exceptions\CurrencyException;
use Toolkit\Currency\Exceptions\ApiException;
use Toolkit\Currency\Exceptions\CacheException;
use Toolkit\Currency\Exceptions\InvalidCurrencyException;

try {
    $result = $converter->convert(100, 'INVALID', 'EUR');
} catch (InvalidCurrencyException $e) {
    echo "Invalid currency: " . $e->getMessage();
} catch (ApiException $e) {
    echo "API error: " . $e->getMessage();
} catch (CacheException $e) {
    echo "Cache error: " . $e->getMessage();
} catch (CurrencyException $e) {
    echo "General error: " . $e->getMessage();
}
```

## 🧪 Running the Demo

```bash
cd /workspace
php examples/currency_demo.php
```

The demo will show:
1. Converting USD to EUR (API, first call)
2. Converting GBP to IRR (API)
3. Converting USD to EUR again (from cache)
4. Using FixedRateProvider
5. Listing supported currencies
6. Getting exchange rates
7. Handling invalid currency codes

## 📝 Examples

### Multiple Conversions

```php
$converter = new CurrencyConverter();

$conversions = [
    [100, 'USD', 'EUR'],
    [50, 'GBP', 'JPY'],
    [1000, 'EUR', 'IRR'],
];

foreach ($conversions as [$amount, $from, $to]) {
    $result = $converter->convert($amount, $from, $to);
    echo "{$amount} {$from} = {$result->result} {$to}\n";
}
```

### Custom Provider and Cache

```php
use Toolkit\Currency\Provider\ExchangeRateHostProvider;
use Toolkit\Currency\Cache\FileCacheManager;

$provider = new ExchangeRateHostProvider('https://custom.api.com', 10);
$cache = new FileCacheManager('/custom/cache/path');

$converter = new CurrencyConverter($provider, $cache, [
    'cacheTtl' => 7200, // 2 hours
]);
```

### Offline Mode

```php
use Toolkit\Currency\Config\CurrencyConfig;
use Toolkit\Currency\Provider\FixedRateProvider;

// Switch to fixed rates
CurrencyConfig::$provider = 'fixed';

$provider = new FixedRateProvider();
$converter = new CurrencyConverter($provider);

// Works without internet connection
$result = $converter->convert(100, 'USD', 'EUR');
```

## 🔒 Security Notes

- Currency codes are validated (must be 3 uppercase letters)
- Cache keys are sanitized to prevent directory traversal
- File locking prevents concurrent write issues
- SSL verification enabled for API requests

## 📄 License

This module is part of the Toolkit project.

## 🤝 Contributing

Feel free to submit issues and enhancement requests!

---

**Author**: Toolkit Team  
**Version**: 1.1.1  
**PHP Version**: 8.2+

---

## 🆕 New Features in v1.1.1

### Batch Conversion
Convert multiple amounts efficiently with a single rate fetch:

```php
use Toolkit\Currency\Batch\BatchCurrencyConverter;

$batch = new BatchCurrencyConverter();
$results = $batch->convertMultiple([
    ['amount' => 100, 'from' => 'USD', 'to' => 'EUR'],
    ['amount' => 50, 'from' => 'GBP', 'to' => 'IRR'],
    ['amount' => 200, 'from' => 'USD', 'to' => 'JPY'],
]);

foreach ($results as $result) {
    echo $result . "\n";
}
```

### Cryptocurrency Provider
Support for Bitcoin, Ethereum, and 20+ cryptocurrencies:

```php
use Toolkit\Currency\Provider\CryptoProvider;
use Toolkit\Currency\CurrencyConverter;

$cryptoProvider = new CryptoProvider();
$converter = new CurrencyConverter($cryptoProvider);

// Convert BTC to USD
$result = $converter->convert(1, 'BTC', 'USD');
echo "1 BTC = {$result->result} USD\n";

// Convert ETH to EUR
$result = $converter->convert(10, 'ETH', 'EUR');
echo "10 ETH = {$result->result} EUR\n";
```

### Rate Alerts
Set alerts for specific exchange rates:

```php
use Toolkit\Currency\Alerts\RateAlert;
use Toolkit\Currency\Alerts\RateAlertManager;

// Create an alert
$alert = RateAlert::create('USD', 'EUR', 'above', 0.90);
$alert->setEmail('user@example.com');

// Save alert
$manager = new RateAlertManager();
$manager->addAlert($alert);

// Check all alerts
$triggeredAlerts = $manager->checkAlerts();
foreach ($triggeredAlerts as $triggered) {
    echo "Alert triggered: {$triggered->getMessage()}\n";
}
```

### CLI Commands
Full-featured command-line interface:

```bash
# Convert currency
php examples/currency.php convert 100 USD EUR

# Get exchange rate
php examples/currency.php rate GBP IRR

# List supported currencies
php examples/currency.php list

# Batch conversion
php examples/currency.php batch

# Manage alerts
php examples/currency.php alert add USD EUR above 0.90
php examples/currency.php alert list
php examples/currency.php alert remove 1

# Help
php examples/currency.php help
```

### Web Dashboard
Beautiful web interface for currency conversion:

```bash
# Start the built-in PHP server
php -S localhost:8000 examples/dashboard.php

# Open browser at http://localhost:8000
```

Features:
- Real-time conversion
- Interactive charts (Chart.js from CDN)
- Dark/Light theme toggle
- Alert management
- Multi-language support

### Multi-language Support (i18n)
Messages and errors in 5 languages:

```php
use Toolkit\Currency\I18n\Translator;

// Set language
Translator::setLocale('fa'); // Persian
Translator::setLocale('ar'); // Arabic
Translator::setLocale('fr'); // French
Translator::setLocale('de'); // German
Translator::setLocale('en'); // English (default)

// Translate messages
echo Translator::trans('conversion_success');
echo Translator::trans('invalid_currency');
```

### Memory Cache
High-performance in-memory caching:

```php
use Toolkit\Currency\Cache\MemoryCacheManager;
use Toolkit\Currency\CurrencyConverter;

$cache = new MemoryCacheManager();
$converter = new CurrencyConverter(null, $cache);

// Ultra-fast conversions without file I/O
```

### PSR-3 Style Logging
Professional logging without external dependencies:

```php
use Toolkit\Currency\Log\SimpleLogger;

$logger = new SimpleLogger('/path/to/logs/currency.log');

$logger->debug('Debug message');
$logger->info('Information message');
$logger->warning('Warning message');
$logger->error('Error message');
```
