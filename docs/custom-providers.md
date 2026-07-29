# 🚀 راهنمای جامع افزودن Provider جدید به Currency Converter

## فهرست مطالب
1. [معماری سیستم](#معماری-سیستم)
2. [چرا EuropeanCentralBankProvider اضافه شد؟](#چرا-europeancentralbankprovider-اضافه-شد)
3. [مراحل افزودن Provider جدید](#مراحل-افزودن-provider-جدید)
4. [مثال عملی: افزودن CryptoProvider](#مثال-عملی-افزودن-cryptoprovider)
5. [تست و اعتبارسنجی](#تست-و-اعتبارسنجی)
6. [به‌روزرسانی مستندات](#به‌روزرسانی-مستندات)

---

## معماری سیستم

### ساختار فعلی Providers

```
┌─────────────────────────────────────┐
│   ExchangeRateProviderInterface     │
│  - fetchRate(string, string): float │
│  - fetchRates(string): array        │
└──────────────┬──────────────────────┘
               │
       ┌───────┴───────┐
       │               │
       ▼               ▼
┌─────────────┐  ┌──────────────────┐
│ FixedRate   │  │ ExchangeRateHost │
│ Provider    │  │ Provider         │
│             │  │ (API Key)        │
└─────────────┘  └──────────────────┘
       │
       ▼
┌──────────────────────┐
│ EuropeanCentralBank  │
│ Provider             │
│ (FREE, No Key)       │
└──────────────────────┘
```

### مزایای معماری مبتنی بر Interface

✅ **Loose Coupling**: کلاس‌ها به هم وابسته نیستند  
✅ **Open/Closed**: گسترش بدون تغییر کد موجود  
✅ **Testability**: تست آسان با Mock providers  
✅ **Flexibility**: تعویض provider در زمان اجرا  

---

## چرا EuropeanCentralBankProvider اضافه شد؟

### مشکلات APIهای پولی/نیازمند کلید

| مشکل | تأثیر | راه‌حل ECB |
|------|-------|-----------|
| نیاز به API Key | ثبت‌نام اجباری | ❌ بدون نیاز به کلید |
| محدودیت درخواست | ۱۵۰۰ در ماه رایگان | ✅ نامحدود (استفاده منصفانه) |
| قطعی سرویس | عدم دسترسی | ✅ پشتیبان خودکار |
| هزینه | پرداخت برای حجم بالا | ✅ کاملاً رایگان |

### ویژگی‌های EuropeanCentralBankProvider

```php
use Toolkit\Currency\Provider\EuropeanCentralBankProvider;

// کاملاً رایگان، بدون نیاز به تنظیمات
$provider = new EuropeanCentralBankProvider();
$converter = new CurrencyConverter($provider);

$result = $converter->convert(100, 'USD', 'EUR');
echo $result; // 100.00 USD = XX.XX EUR
```

**مزایا:**
- 🆓 رایگان ۱۰۰٪
- 🔑 بدون نیاز به API Key
- 🌍 پشتیبانی از ۱۷۰+ ارز
- ⚡ آپدیت روزانه
- 🛡️ پایدار و قابل اعتماد

---

## مراحل افزودن Provider جدید

### مرحله ۱: ایجاد کلاس Provider

#### الزامات فنی

1. **پیاده‌سازی Interface**
```php
use Toolkit\Currency\Contracts\ExchangeRateProviderInterface;

class MyCustomProvider implements ExchangeRateProviderInterface
{
    // ...
}
```

2. **متدهای مورد نیاز**
```php
public function fetchRate(string $from, string $to): float
public function fetchRates(string $base): array
```

3. **مدیریت خطاها**
```php
use Toolkit\Currency\Exceptions\ApiException;
use Toolkit\Currency\Exceptions\InvalidCurrencyException;
```

### مرحله ۲: به‌روزرسانی ProviderFactory

```php
// src/Currency/Provider/ProviderFactory.php

case 'my_custom':
    return new MyCustomProvider(
        $options['param1'] ?? null,
        $options['param2'] ?? null
    );
```

### مرحله ۳: افزودن به CurrencyConfig (اختیاری)

```php
// src/Currency/Config/CurrencyConfig.php

public static string $myCustomApiUrl = 'https://api.example.com';
public static string $myCustomKey = 'optional_key';
```

### مرحله ۴: نوشتن تست

```php
// examples/test_my_provider.php

$provider = new MyCustomProvider();
$converter = new CurrencyConverter($provider);

$result = $converter->convert(100, 'USD', 'EUR');
assert($result->result > 0);
```

---

## مثال عملی: افزودن CryptoProvider

### سناریو
می‌خواهیم یک Provider برای ارزهای دیجیتال (Bitcoin, Ethereum, etc.) اضافه کنیم.

### گام ۱: ایجاد فایل

```bash
touch src/Currency/Provider/CryptoProvider.php
```

### گام ۲: پیاده‌سازی کامل

```php
<?php

namespace Toolkit\Currency\Provider;

use Toolkit\Currency\Contracts\ExchangeRateProviderInterface;
use Toolkit\Currency\Exceptions\ApiException;
use Toolkit\Currency\Exceptions\InvalidCurrencyException;
use Toolkit\Currency\Helpers\CurrencyHelper;

/**
 * Cryptocurrency Provider
 * 
 * Fetches crypto rates from CoinGecko API (FREE)
 * 
 * @package Toolkit\Currency\Provider
 */
class CryptoProvider implements ExchangeRateProviderInterface
{
    private const API_URL = 'https://api.coingecko.com/api/v3/simple/price';
    private int $timeout;

    public function __construct(?int $timeout = null)
    {
        $this->timeout = $timeout ?? 10;
    }

    public function fetchRate(string $from, string $to): float
    {
        // Normalize currency codes
        $from = strtoupper($from);
        $to = strtoupper($to);

        // Map to CoinGecko IDs
        $cryptoIds = [
            'BTC' => 'bitcoin',
            'ETH' => 'ethereum',
            'USDT' => 'tether',
            'BNB' => 'binancecoin',
        ];

        $fiatIds = [
            'USD' => 'usd',
            'EUR' => 'eur',
            'GBP' => 'gbp',
            'IRR' => 'irr',
        ];

        $fromId = $cryptoIds[$from] ?? strtolower($from);
        $toId = $fiatIds[$to] ?? strtolower($to);

        // Build URL
        $url = sprintf(
            '%s?ids=%s&vs_currencies=%s',
            self::API_URL,
            $fromId,
            $toId
        );

        // cURL request
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => 'Toolkit-Currency-Converter',
        ]);

        $response = curl_exec($ch);
        
        if ($response === false) {
            throw new ApiException("cURL error: " . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new ApiException("HTTP error: {$httpCode}");
        }

        $data = json_decode($response, true);
        
        if (!isset($data[$fromId][$toId])) {
            throw new InvalidCurrencyException($from, "Crypto rate not found");
        }

        return (float) $data[$fromId][$toId];
    }

    public function fetchRates(string $base): array
    {
        // Implementation for fetching all rates
        // Similar to fetchRate but returns array
        return [$base => $this->fetchRate($base, 'USD')];
    }
}
```

### گام ۳: به‌روزرسانی Factory

```php
// ProviderFactory.php

case 'crypto':
    return new CryptoProvider(
        $options['timeout'] ?? null
    );
```

### گام ۴: تست

```php
// examples/crypto_demo.php

use Toolkit\Currency\Provider\CryptoProvider;
use Toolkit\Currency\CurrencyConverter;

$provider = new CryptoProvider();
$converter = new CurrencyConverter($provider);

// Bitcoin to USD
$result = $converter->convert(1, 'BTC', 'USD');
echo "1 BTC = {$result->result} USD\n";

// Ethereum to EUR
$result = $converter->convert(10, 'ETH', 'EUR');
echo "10 ETH = {$result->result} EUR\n";
```

---

## تست و اعتبارسنجی

### چک‌لیست تست

- [ ] **تست واحد**: هر متد جداگانه تست شود
- [ ] **تست خطا**: مدیریت استثناها بررسی شود
- [ ] **تست کش**: عملکرد caching تأیید شود
- [ ] **تست کارایی**: زمان پاسخ < 5 ثانیه
- [ ] **تست همزمانی**: عدم تداخل درخواست‌ها

### نمونه تست خودکار

```php
<?php

require_once 'src/Bootstrap.php';

use Toolkit\Currency\Provider\EuropeanCentralBankProvider;
use Toolkit\Currency\CurrencyConverter;

echo "Running Provider Tests...\n";

$provider = new EuropeanCentralBankProvider();
$converter = new CurrencyConverter($provider);

// Test 1: Basic conversion
try {
    $result = $converter->convert(100, 'USD', 'EUR');
    assert($result->result > 0, "Result should be positive");
    echo "✓ Basic conversion passed\n";
} catch (Exception $e) {
    echo "✗ Basic conversion failed: {$e->getMessage()}\n";
}

// Test 2: Same currency
try {
    $rate = $converter->getRate('EUR', 'EUR');
    assert($rate === 1.0, "Same currency rate should be 1.0");
    echo "✓ Same currency test passed\n";
} catch (Exception $e) {
    echo "✗ Same currency test failed: {$e->getMessage()}\n";
}

// Test 3: Invalid currency
try {
    $converter->convert(100, 'INVALID', 'EUR');
    echo "✗ Invalid currency test failed (should throw exception)\n";
} catch (InvalidCurrencyException $e) {
    echo "✓ Invalid currency test passed\n";
}

echo "\nAll tests completed!\n";
```

---

## به‌روزرسانی مستندات

### README.md

بخش Providers را به‌روز کنید:

```markdown
## 🔌 Providers

### Available Providers

1. **ExchangeRateHostProvider** - Real-time API (requires key)
2. **FixedRateProvider** - Static rates for testing
3. **EuropeanCentralBankProvider** - FREE, no key required ✨ NEW
4. **CryptoProvider** - Cryptocurrency rates (example)
```

### docs/currency.md

افزودن بخش جدید:

```markdown
## Creating Custom Providers

You can create your own provider by implementing the 
`ExchangeRateProviderInterface`. See `docs/custom-providers.md` 
for a complete guide.
```

---

## جمع‌بندی

### قابلیت‌های فعلی پروژه

| ویژگی | وضعیت | توضیحات |
|-------|-------|---------|
| File Cache | ✅ | JSON-based با TTL |
| API Provider | ✅ | exchangerate-api.com V6 |
| Fixed Provider | ✅ | برای تست |
| ECB Provider | ✅ | رایگان، بدون کلید |
| Exception Handling | ✅ | ۴ کلاس استثنا |
| Helper Functions | ✅ | اعتبارسنجی و فرمت |
| Value Objects | ✅ | ConversionResult |
| Factory Pattern | ✅ | ProviderFactory |
| Documentation | ✅ | README + docs |

### پیشنهادات برای توسعه آینده

1. **Redis Cache**: برای عملکرد بهتر در محیط production
2. **Batch Conversions**: تبدیل چندین مقدار به صورت گروهی
3. **Historical Rates**: دریافت نرخ‌های تاریخی
4. **PSR-3 Logging**: لاگینگ استاندارد
5. **PHPUnit Tests**: مجموعه تست کامل
6. **WebSocket Support**: آپدیت لحظه‌ای نرخ‌ها

---

**نسخه**: 1.1.0  
**آخرین به‌روزرسانی**: 2026  
**تعداد Providers**: ۴ عدد  
