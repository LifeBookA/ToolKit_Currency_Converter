# ماژول مبدل ارز لحظه‌ای (Currency Converter)

## 📖 معرفی

یک ماژول قدرتمند و انعطاف‌پذیر برای تبدیل ارز در PHP با پشتیبانی از کش فایلی. این ماژول امکانات زیر را فراهم می‌کند:

- **نرخ‌های لحظه‌ای** از طریق API (exchangerate-api.com V6)
- **ارائه‌دهنده نرخ ثابت** برای تست و حالت آفلاین
- **کش فایلی JSON** برای کاهش درخواست‌های API و افزایش عملکرد
- **چندین ارائه‌دهنده** با قابلیت تغییر آسان از طریق پیکربندی
- **مدیریت استثنا** برای مدیریت خطاهای قوی
- **سازگار با PSR-4** برای بارگذاری خودکار

### ویژگی‌های کلیدی

✅ **۳۷ کلاس PHP** با مستندات کامل  
✅ **کش فایلی JSON** با پشتیبانی از TTL  
✅ **بازگشت به کش قدیمی** هنگام قطعی API  
✅ **محاسبه نرخ متقاطع** برای هر جفت ارز  
✅ **اشیاء مقدار** برای نتایج نوع‌امن  
✅ **توابع کمکی** برای اعتبارسنجی کد ارز  
✅ **تبدیل دسته‌جمعی** برای بهینه‌سازی  
✅ **پشتیبانی از ارزهای دیجیتال** (BTC, ETH و ...)  
✅ **سیستم هشدار نرخ**  
✅ **رابط خط فرمان (CLI)**  
✅ **داشبورد وب**  
✅ **پشتیبانی چندزبانه** (۵ زبان)

## 📁 ساختار پروژه

```
Toolkit/
├── src/
│   ├── Autoloader.php              # بارگذار خودکار PSR-4
│   ├── Bootstrap.php               # کلاس راه‌اندازی
│   └── Currency/
│       ├── Alerts/                 # سیستم هشدار نرخ
│       │   ├── RateAlert.php
│       │   └── RateAlertManager.php
│       ├── Batch/                  # تبدیل دسته‌جمعی
│       │   └── BatchCurrencyConverter.php
│       ├── Cache/                  # سیستم‌های کش
│       │   ├── CacheInterface.php
│       │   ├── FileCacheManager.php
│       │   └── MemoryCacheManager.php
│       ├── CLI/                    # رابط خط فرمان
│       │   └── CurrencyCommand.php
│       ├── Config/                 # پیکربندی
│       │   └── CurrencyConfig.php
│       ├── Contracts/              # اینترفیس‌ها
│       │   ├── CurrencyConverterInterface.php
│       │   └── ExchangeRateProviderInterface.php
│       ├── Daemon/                 # حالت پس‌زمینه
│       │   └── CurrencyDaemon.php
│       ├── Exceptions/             # کلاس‌های استثنا
│       │   ├── CurrencyException.php
│       │   ├── ApiException.php
│       │   ├── CacheException.php
│       │   └── InvalidCurrencyException.php
│       ├── Export/                 # خروجی‌ها
│       │   ├── CsvExporter.php
│       │   └── PdfReportGenerator.php
│       ├── Helpers/                # توابع کمکی
│       │   └── CurrencyHelper.php
│       ├── Historical/             # نرخ‌های تاریخی
│       │   └── HistoricalRateManager.php
│       ├── I18n/                   # چندزبانه
│       │   └── Translator.php
│       ├── Log/                    # لاگینگ
│       │   └── SimpleLogger.php
│       ├── Provider/               # ارائه‌دهندگان نرخ
│       │   ├── ExchangeRateHostProvider.php
│       │   ├── FixedRateProvider.php
│       │   ├── EuropeanCentralBankProvider.php
│       │   ├── CryptoProvider.php
│       │   └── ProviderFactory.php
│       ├── Result/                 # اشیاء نتیجه
│       │   └── ConversionResult.php
│       ├── Security/               # امنیت
│       │   ├── ApiSigner.php
│       │   └── RateLimiter.php
│       ├── Web/                    # داشبورد وب
│       │   └── WebDashboard.php
│       └── CurrencyConverter.php   # کلاس اصلی مبدل
├── examples/
│   ├── currency_demo.php           # مثال‌های استفاده
│   ├── currency.php                # ابزار CLI
│   └── dashboard.php               # داشبورد وب
├── tests/                          # مجموعه تست
│   ├── index.php                   # اجرای تست‌ها
│   ├── auto/                       # تست‌های خودکار
│   └── visual/                     # تست‌های گرافیکی
├── docs/                           # مستندات
│   ├── architecture.md
│   ├── testing.md
│   ├── custom-providers.md
│   └── CHANGELOG.md
├── cache/currency/                 # پوشه کش
├── data/                           # داده‌های تاریخی و محدودیت نرخ
└── README.md
```

## 🚀 شروع سریع

### نصب

بدون نیاز به Composer! فایل Bootstrap را شامل کنید:

```php
require_once 'path/to/Toolkit/src/Bootstrap.php';

use Toolkit\Bootstrap;
use Toolkit\Currency\CurrencyConverter;

// راه‌اندازی
Bootstrap::init();

// ایجاد مبدل
$converter = new CurrencyConverter();

// تبدیل ۱۰۰ دلار به یورو
$result = $converter->convert(100, 'USD', 'EUR');
echo $result; // خروجی: 100.00 USD = XX.XX EUR (rate: 0.XXXXXX)
```

### استفاده پایه

```php
<?php

require_once 'src/Bootstrap.php';

use Toolkit\Bootstrap;
use Toolkit\Currency\CurrencyConverter;

Bootstrap::init();

$converter = new CurrencyConverter();

// تبدیل ارز
$result = $converter->convert(100, 'USD', 'EUR');
echo "مبلغ: {$result->amount} {$result->from}\n";
echo "نتیجه: {$result->result} {$result->to}\n";
echo "نرخ: {$result->rate}\n";
echo "از کش: " . ($result->fromCache ? 'بله' : 'خیر') . "\n";

// دریافت فقط نرخ ارز
$rate = $converter->getRate('GBP', 'JPY');
echo "نرخ GBP به JPY: {$rate}\n";

// دریافت ارزهای پشتیبانی‌شده
$currencies = $converter->getSupportedCurrencies();
print_r($currencies);
```

## ⚙️ پیکربندی

تمام تنظیمات از طریق `CurrencyConfig` مدیریت می‌شوند:

```php
use Toolkit\Currency\Config\CurrencyConfig;

// تنظیمات کش
CurrencyConfig::$cacheDir = '/path/to/cache';
CurrencyConfig::$cacheTtl = 3600; // ۱ ساعت

// ارزهای پیش‌فرض
CurrencyConfig::$defaultFrom = 'USD';
CurrencyConfig::$defaultTo = 'EUR';

// انتخاب ارائه‌دهنده ('api', 'fixed', 'ecb', 'crypto')
CurrencyConfig::$provider = 'api';

// تنظیمات API (نسخه V6)
CurrencyConfig::$apiUrl = 'https://v6.exchangerate-api.com/v6/';
CurrencyConfig::$apiKey = 'کلید-API-شما'; // کلید رایگان از exchangerate-api.com دریافت کنید
CurrencyConfig::$apiTimeout = 5;

// نرخ‌های ثابت (برای FixedRateProvider)
CurrencyConfig::$fixedRates = [
    'USD' => 1.0,
    'EUR' => 0.85,
    'GBP' => 0.75,
    'IRR' => 42000.0,
];
```

### دریافت کلید API

۱. به سایت [https://www.exchangerate-api.com/](https://www.exchangerate-api.com/) مراجعه کنید
۲. ثبت‌نام رایگان انجام دهید
۳. کلید API خود را از داشبورد دریافت کنید
۴. در پیکربندی تنظیم کنید: `CurrencyConfig::setApiKey('your-key-here');`

طرح رایگان شامل:
- ۱۵۰۰ درخواست API در ماه
- بروزرسانی روزانه نرخ‌ها
- تمام ارزهای جهان

## 🔌 ارائه‌دهندگان نرخ

### ارائه‌دهنده API (پیش‌فرض)

دریافت نرخ‌های لحظه‌ای از exchangerate-api.com V6:

```php
use Toolkit\Currency\Provider\ExchangeRateHostProvider;
use Toolkit\Currency\CurrencyConverter;
use Toolkit\Currency\Config\CurrencyConfig;

// ابتدا کلید API را تنظیم کنید
CurrencyConfig::setApiKey('your-free-api-key');

$provider = new ExchangeRateHostProvider();
$converter = new CurrencyConverter($provider);
```

### ارائه‌دهنده بانک مرکزی اروپا (رایگان)

بدون نیاز به کلید API:

```php
use Toolkit\Currency\Provider\EuropeanCentralBankProvider;
use Toolkit\Currency\CurrencyConverter;

$provider = new EuropeanCentralBankProvider();
$converter = new CurrencyConverter($provider);

$result = $converter->convert(100, 'USD', 'EUR');
echo $result; // نرخ‌های رایگان از ECB
```

### ارائه‌دهنده ارزهای دیجیتال

پشتیبانی از BTC, ETH و بیش از ۲۰ ارز دیجیتال:

```php
use Toolkit\Currency\Provider\CryptoProvider;
use Toolkit\Currency\CurrencyConverter;

$cryptoProvider = new CryptoProvider();
$converter = new CurrencyConverter($cryptoProvider);

// تبدیل BTC به USD
$result = $converter->convert(1, 'BTC', 'USD');
echo "1 BTC = {$result->result} USD\n";

// تبدیل ETH به EUR
$result = $converter->convert(10, 'ETH', 'EUR');
echo "10 ETH = {$result->result} EUR\n";
```

### ارائه‌دهنده نرخ ثابت

برای تست یا استفاده آفلاین:

```php
use Toolkit\Currency\Provider\FixedRateProvider;
use Toolkit\Currency\CurrencyConverter;

$provider = new FixedRateProvider();
$converter = new CurrencyConverter($provider);

$result = $converter->convert(100, 'USD', 'EUR');
echo $result; // از نرخ‌های ثابت پیکربندی استفاده می‌کند
```

### کارخانه ارائه‌دهنده

به‌طور خودکار بر اساس پیکربندی ارائه‌دهنده ایجاد می‌کند:

```php
use Toolkit\Currency\Provider\ProviderFactory;

// از CurrencyConfig::$provider استفاده می‌کند
$provider = ProviderFactory::create();

// اجبار به ارائه‌دهنده خاص
$apiProvider = ProviderFactory::create('api');
$fixedProvider = ProviderFactory::create('fixed');
$ecbProvider = ProviderFactory::create('ecb');
$cryptoProvider = ProviderFactory::create('crypto');
```

## 💾 کش

ماژول از کش فایلی برای ذخیره نرخ‌های ارز استفاده می‌کند:

- **موقعیت**: پوشه `cache/currency/`
- **فرمت**: فایل‌های JSON (`{FROM_TO}.json`)
- **TTL**: قابل تنظیم (پیش‌فرض: ۳۶۰۰ ثانیه)
- **بازگشت**: استفاده از کش قدیمی اگر API شکست بخورد

### فرمت فایل کش

```json
{
    "value": 0.85,
    "expiry": 1765123456
}
```

### مدیریت دستی کش

```php
use Toolkit\Currency\Cache\FileCacheManager;

$cache = new FileCacheManager();

// بررسی وجود کلید
if ($cache->has('USD_EUR')) {
    echo "نرخ در کش موجود است!";
}

// دریافت مقدار کش‌شده
$rate = $cache->get('USD_EUR');

// تنظیم مقدار کش
$cache->set('USD_EUR', 0.85, 3600);

// حذف کلید خاص
$cache->delete('USD_EUR');

// پاک کردن تمام کش
$cache->clear();
```

### کش حافظه (برای عملکرد بالا)

```php
use Toolkit\Currency\Cache\MemoryCacheManager;
use Toolkit\Currency\CurrencyConverter;

$cache = new MemoryCacheManager();
$converter = new CurrencyConverter(null, $cache);

// تبدیل‌های فوق‌سریع بدون عملیات فایل
```

## 🎯 ویژگی‌ها

### شیء نتیجه تبدیل

```php
$result = $converter->convert(100, 'USD', 'EUR');

// دسترسی به خواص
echo $result->amount;     // 100.0
echo $result->rate;       // 0.85
echo $result->from;       // 'USD'
echo $result->to;         // 'EUR'
echo $result->result;     // 85.0
echo $result->timestamp;  // زمان یونیکس
echo $result->fromCache;  // true/false

// تبدیل به آرایه
$data = $result->toArray();

// نمایش رشته‌ای
echo $result; // "100.00 USD = 85.00 EUR (rate: 0.850000)"
```

### توابع کمکی

```php
use Toolkit\Currency\Helpers\CurrencyHelper;

// نرمال‌سازی کد ارز
$code = CurrencyHelper::normalizeCurrencyCode(' usd '); // 'USD'

// اعتبارسنجی کد ارز
CurrencyHelper::isValidCurrencyCode('USD'); // true
CurrencyHelper::isValidCurrencyCode('XX');  // false

// فرمت مبلغ
CurrencyHelper::formatAmount(1234.567, 2); // "1,234.57"

// ساخت کلید کش
CurrencyHelper::buildCacheKey('USD', 'EUR'); // "USD_EUR"
```

### تبدیل دسته‌جمعی

```php
use Toolkit\Currency\Batch\BatchCurrencyConverter;

$batch = new BatchCurrencyConverter();
$results = $batch->convertBatch([
    ['amount' => 100, 'from' => 'USD', 'to' => 'EUR'],
    ['amount' => 50, 'from' => 'GBP', 'to' => 'IRR'],
    ['amount' => 200, 'from' => 'USD', 'to' => 'JPY'],
]);

foreach ($results as $result) {
    echo $result . "\n";
}
```

### نرخ‌های تاریخی

```php
use Toolkit\Currency\Historical\HistoricalRateManager;

$historical = new HistoricalRateManager();

// ذخیره نرخ تاریخی
$historical->saveRate('USD', 'EUR', 0.85, strtotime('2024-01-01'));

// دریافت نرخ‌های تاریخی
$rates = $historical->getRates('USD', 'EUR');

// تولید نمودار SVG
$svgChart = $historical->generateSvgChart('USD', 'EUR');
echo $svgChart; // خروجی SVG برای نمایش نمودار
```

### خروجی‌های پیشرفته

#### خروجی CSV

```php
use Toolkit\Currency\Export\CsvExporter;

$exporter = new CsvExporter();
$csv = $exporter->export($conversionResults);
file_put_contents('report.csv', $csv);
```

#### گزارش PDF

```php
use Toolkit\Currency\Export\PdfReportGenerator;

$pdfGen = new PdfReportGenerator();
$pdfContent = $pdfGen->generate($conversionResults, 'گزارش تبدیل ارز');
file_put_contents('report.pdf', $pdfContent);
```

## ⚠️ مدیریت استثنا

```php
use Toolkit\Currency\Exceptions\CurrencyException;
use Toolkit\Currency\Exceptions\ApiException;
use Toolkit\Currency\Exceptions\CacheException;
use Toolkit\Currency\Exceptions\InvalidCurrencyException;

try {
    $result = $converter->convert(100, 'INVALID', 'EUR');
} catch (InvalidCurrencyException $e) {
    echo "ارز نامعتبر: " . $e->getMessage();
} catch (ApiException $e) {
    echo "خطای API: " . $e->getMessage();
} catch (CacheException $e) {
    echo "خطای کش: " . $e->getMessage();
} catch (CurrencyException $e) {
    echo "خطای عمومی: " . $e->getMessage();
}
```

## 🔒 امنیت

### امضای دیجیتال API (HMAC)

```php
use Toolkit\Currency\Security\ApiSigner;

$signer = new ApiSigner('secret-key');

// امضای درخواست
$signature = $signer->sign('GET', '/api/rates', ['base' => 'USD'], time());

// تأیید امضا
$isValid = $signer->verify($signature, 'GET', '/api/rates', $data, $timestamp);
```

### محدودکننده نرخ (Rate Limiter)

```php
use Toolkit\Currency\Security\RateLimiter;

$limiter = new RateLimiter(100, 3600); // ۱۰۰ درخواست در ساعت

if ($limiter->isAllowed('user-id')) {
    // اجازه انجام درخواست
    echo "درخواست مجاز است. {$limiter->getRemainingRequests()} درخواست باقی‌مانده.";
} else {
    // مسدود شدن به دلیل превышение حد
    echo "درخواست مسدود شد. لطفاً بعداً تلاش کنید.";
}
```

## 🛠️ ابزار خط فرمان (CLI)

```bash
# تبدیل ارز
php examples/currency.php convert 100 USD EUR

# دریافت نرخ ارز
php examples/currency.php rate GBP IRR

# لیست ارزهای پشتیبانی‌شده
php examples/currency.php list

# تبدیل دسته‌جمعی
php examples/currency.php batch

# مدیریت هشدارها
php examples/currency.php alert add USD EUR above 0.90
php examples/currency.php alert list
php examples/currency.php alert remove 1

# راهنما
php examples/currency.php help
```

## 🌐 داشبورد وب

```bash
# راه‌اندازی سرور داخلی PHP
php -S localhost:8000 examples/dashboard.php

# مرورگر را در آدرس http://localhost:8000 باز کنید
```

ویژگی‌ها:
- تبدیل لحظه‌ای
- نمودارهای تعاملی (SVG بومی)
- تم تیره/روشن
- مدیریت هشدارها
- پشتیبانی چندزبانه

## 🌍 پشتیبانی چندزبانه

پیام‌ها و خطاها به ۵ زبان:

```php
use Toolkit\Currency\I18n\Translator;

// تنظیم زبان
Translator::setLocale('fa'); // فارسی
Translator::setLocale('ar'); // عربی
Translator::setLocale('en'); // انگلیسی (پیش‌فرض)

// ترجمه پیام‌ها
echo Translator::trans('conversion_success');
echo Translator::trans('invalid_currency');
```

## 📊 لاگینگ (PSR-3 Style)

```php
use Toolkit\Currency\Log\SimpleLogger;

$logger = new SimpleLogger('/path/to/logs/currency.log');

$logger->debug('پیام دیباگ');
$logger->info('پیام اطلاعات');
$logger->warning('پیام هشدار');
$logger->error('پیام خطا');
```

## 🧪 اجرای دمو و تست

### اجرای دمو

```bash
cd /workspace
php examples/currency_demo.php
```

دمو موارد زیر را نشان می‌دهد:
۱. تبدیل USD به EUR (API، اولین فراخوانی)
۲. تبدیل GBP به IRR (API)
۳. تبدیل مجدد USD به EUR (از کش)
۴. استفاده از FixedRateProvider
۵. لیست ارزهای پشتیبانی‌شده
۶. دریافت نرخ ارز
۷. مدیریت کدهای ارز نامعتبر

### اجرای تست‌ها

```bash
# اجرای تعاملی با منو
php tests/index.php

# اجرای تمام تست‌های خودکار
php tests/index.php A

# اجرای تمام تست‌های گرافیکی
php tests/index.php V

# اجرای یک تست خاص (مثلاً تست شماره ۱)
php tests/index.php 1
```

## 📝 مثال‌ها

### تبدیل‌های چندگانه

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

### ارائه‌دهنده و کش سفارشی

```php
use Toolkit\Currency\Provider\ExchangeRateHostProvider;
use Toolkit\Currency\Cache\FileCacheManager;

$provider = new ExchangeRateHostProvider('https://custom.api.com', 10);
$cache = new FileCacheManager('/custom/cache/path');

$converter = new CurrencyConverter($provider, $cache, [
    'cacheTtl' => 7200, // ۲ ساعت
]);
```

### حالت آفلاین

```php
use Toolkit\Currency\Config\CurrencyConfig;
use Toolkit\Currency\Provider\FixedRateProvider;

// تغییر به نرخ‌های ثابت
CurrencyConfig::$provider = 'fixed';

$provider = new FixedRateProvider();
$converter = new CurrencyConverter($provider);

// بدون اتصال به اینترنت کار می‌کند
$result = $converter->convert(100, 'USD', 'EUR');
```

### سیستم هشدار نرخ

```php
use Toolkit\Currency\Alerts\RateAlert;
use Toolkit\Currency\Alerts\RateAlertManager;

// ایجاد هشدار
$alert = RateAlert::create('USD', 'EUR', 'above', 0.90);
$alert->setEmail('user@example.com');

// ذخیره هشدار
$manager = new RateAlertManager();
$manager->addAlert($alert);

// بررسی همه هشدارها
$triggeredAlerts = $manager->checkAlerts();
foreach ($triggeredAlerts as $triggered) {
    echo "هشدار فعال شد: {$triggered->getMessage()}\n";
}
```

### حالت Daemon (پس‌زمینه)

```bash
# اجرای اسکریپت در پس‌زمینه برای بروزرسانی خودکار
php examples/currency.php daemon start

# توقف daemon
php examples/currency.php daemon stop

# مشاهده وضعیت
php examples/currency.php daemon status
```

## 📄 مجوز

این ماژول بخشی از پروژه Toolkit است.

## 🤝 مشارکت

از ارسال مشکلات و درخواست‌های بهبود استقبال می‌کنیم!

---

**تیم توسعه**: Toolkit  
**نسخه**: 1.2.1  
**نسخه PHP**: 8.2+  
**زبان مستندات**: فارسی

---

## 🆕 ویژگی‌های جدید در نسخه 1.2.1

### ✅ اصلاحات و بهبودها
- رفع خطای کش حافظه در تست‌ها
- بهبود تشخیص محیط CLI و وب
- اصلاح مدیریت Locale در چندزبانه
- بهینه‌سازی تولید گزارش PDF
- بروزرسانی مستندات به فارسی

### 📊 آمار پروژه
- **۳۷ فایل PHP** کامل
- **۳۵ کلاس** و **۴ اینترفیس**
- **۲۸ تست خودکار** با موفقیت ۱۰۰٪
- **۱۲ تست گرافیکی** تعاملی
- **~۴۵۰۰+ خط کد**
- **بدون وابستگی خارجی** (بدون Composer)
