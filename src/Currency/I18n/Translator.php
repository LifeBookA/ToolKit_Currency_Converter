<?php

namespace Toolkit\Currency\I18n;

/**
 * Multi-language Message Translator
 * 
 * Provides internationalization support for currency converter messages
 * Supports multiple languages with fallback to English
 * 
 * @package Toolkit\Currency\I18n
 */
class Translator
{
    /**
     * Current language
     */
    protected string $locale = 'en';

    /**
     * Available translations
     */
    protected array $translations = [
        'en' => [
            // Conversion messages
            'conversion_success' => '{amount} {from} = {result} {to}',
            'rate_fetched' => 'Exchange rate: 1 {from} = {rate} {to}',
            'cached_rate' => 'Using cached rate (saved {seconds} seconds ago)',
            
            // Error messages
            'invalid_currency' => 'Invalid currency code: {code}',
            'api_error' => 'API error: {message}',
            'cache_error' => 'Cache error: {message}',
            'provider_error' => 'Provider error: {message}',
            'unsupported_currency' => 'Currency {code} is not supported',
            
            // Info messages
            'cache_hit' => 'Rate retrieved from cache',
            'cache_miss' => 'Rate fetched from provider',
            'batch_converted' => 'Converted {count} amounts',
            'alert_triggered' => 'Alert: {pair} reached {rate}',
            
            // Currency names
            'currency_USD' => 'US Dollar',
            'currency_EUR' => 'Euro',
            'currency_GBP' => 'British Pound',
            'currency_JPY' => 'Japanese Yen',
            'currency_IRR' => 'Iranian Rial',
            'currency_BTC' => 'Bitcoin',
            'currency_ETH' => 'Ethereum',
        ],
        
        'fa' => [
            // Conversion messages
            'conversion_success' => '{amount} {from} = {result} {to}',
            'rate_fetched' => 'نرخ ارز: ۱ {from} = {rate} {to}',
            'cached_rate' => 'استفاده از نرخ ذخیره‌شده ({seconds} ثانیه پیش)',
            
            // Error messages
            'invalid_currency' => 'کد ارز نامعتبر: {code}',
            'api_error' => 'خطای API: {message}',
            'cache_error' => 'خطای کش: {message}',
            'provider_error' => 'خطای ارائه‌دهنده: {message}',
            'unsupported_currency' => 'ارز {code} پشتیبانی نمی‌شود',
            
            // Info messages
            'cache_hit' => 'نرخ از کش دریافت شد',
            'cache_miss' => 'نرخ از ارائه‌دهنده دریافت شد',
            'batch_converted' => '{count} مقدار تبدیل شد',
            'alert_triggered' => 'هشدار: {pair} به {rate} رسید',
            
            // Currency names
            'currency_USD' => 'دلار آمریکا',
            'currency_EUR' => 'یورو',
            'currency_GBP' => 'پوند انگلیس',
            'currency_JPY' => 'ین ژاپن',
            'currency_IRR' => 'ریال ایران',
            'currency_BTC' => 'بیت‌کوین',
            'currency_ETH' => 'اتریوم',
        ],
        
        'ar' => [
            // Conversion messages
            'conversion_success' => '{amount} {from} = {result} {to}',
            'rate_fetched' => 'سعر الصرف: 1 {from} = {rate} {to}',
            'cached_rate' => 'استخدام السعر المخزن (قبل {seconds} ثانية)',
            
            // Error messages
            'invalid_currency' => 'رمز العملة غير صالح: {code}',
            'api_error' => 'خطأ في API: {message}',
            'cache_error' => 'خطأ في الذاكرة المؤقتة: {message}',
            'provider_error' => 'خطأ في المزود: {message}',
            'unsupported_currency' => 'العملة {code} غير مدعومة',
            
            // Info messages
            'cache_hit' => 'تم الحصول على السعر من الذاكرة المؤقتة',
            'cache_miss' => 'تم الحصول على السعر من المزود',
            'batch_converted' => 'تم تحويل {count} مبالغ',
            'alert_triggered' => 'تنبيه: {pair} وصل إلى {rate}',
            
            // Currency names
            'currency_USD' => 'الدولار الأمريكي',
            'currency_EUR' => 'اليورو',
            'currency_GBP' => 'الجنيه الإسترليني',
            'currency_JPY' => 'الين الياباني',
            'currency_IRR' => 'الريال الإيراني',
            'currency_BTC' => 'بيتكوين',
            'currency_ETH' => 'إيثيريوم',
        ],
        
        'de' => [
            // Conversion messages
            'conversion_success' => '{amount} {from} = {result} {to}',
            'rate_fetched' => 'Wechselkurs: 1 {from} = {rate} {to}',
            'cached_rate' => 'Verwende gespeicherten Kurs (vor {seconds} Sekunden)',
            
            // Error messages
            'invalid_currency' => 'Ungültiger Währungscode: {code}',
            'api_error' => 'API-Fehler: {message}',
            'cache_error' => 'Cache-Fehler: {message}',
            'provider_error' => 'Anbieterfehler: {message}',
            'unsupported_currency' => 'Währung {code} wird nicht unterstützt',
            
            // Info messages
            'cache_hit' => 'Kurs aus Cache abgerufen',
            'cache_miss' => 'Kurs vom Anbieter abgerufen',
            'batch_converted' => '{count} Beträge umgerechnet',
            'alert_triggered' => 'Warnung: {pair} erreicht {rate}',
            
            // Currency names
            'currency_USD' => 'US-Dollar',
            'currency_EUR' => 'Euro',
            'currency_GBP' => 'Britisches Pfund',
            'currency_JPY' => 'Japanischer Yen',
            'currency_IRR' => 'Iranischer Rial',
            'currency_BTC' => 'Bitcoin',
            'currency_ETH' => 'Ethereum',
        ],
        
        'fr' => [
            // Conversion messages
            'conversion_success' => '{amount} {from} = {result} {to}',
            'rate_fetched' => 'Taux de change: 1 {from} = {rate} {to}',
            'cached_rate' => 'Utilisation du taux en cache (il y a {seconds} secondes)',
            
            // Error messages
            'invalid_currency' => 'Code devise invalide: {code}',
            'api_error' => 'Erreur API: {message}',
            'cache_error' => 'Erreur de cache: {message}',
            'provider_error' => 'Erreur du fournisseur: {message}',
            'unsupported_currency' => 'La devise {code} n\'est pas supportée',
            
            // Info messages
            'cache_hit' => 'Taux récupéré du cache',
            'cache_miss' => 'Taux récupéré du fournisseur',
            'batch_converted' => '{count} montants convertis',
            'alert_triggered' => 'Alerte: {pair} atteint {rate}',
            
            // Currency names
            'currency_USD' => 'Dollar américain',
            'currency_EUR' => 'Euro',
            'currency_GBP' => 'Livre sterling',
            'currency_JPY' => 'Yen japonais',
            'currency_IRR' => 'Rial iranien',
            'currency_BTC' => 'Bitcoin',
            'currency_ETH' => 'Ethereum',
        ],
    ];

    /**
     * Constructor
     * 
     * @param string $locale Default locale
     */
    public function __construct(string $locale = 'en')
    {
        if (isset($this->translations[$locale])) {
            $this->locale = $locale;
        }
    }

    /**
     * Translate a message
     * 
     * @param string $key Translation key
     * @param array $params Parameters to replace in message
     * @return string Translated message
     */
    public function trans(string $key, array $params = []): string
    {
        // Try current locale
        $message = $this->translations[$this->locale][$key] ?? null;
        
        // Fallback to English
        if ($message === null && $this->locale !== 'en') {
            $message = $this->translations['en'][$key] ?? null;
        }
        
        // If still not found, return key
        if ($message === null) {
            return $key;
        }
        
        // Replace parameters
        foreach ($params as $paramKey => $value) {
            $message = str_replace('{' . $paramKey . '}', (string)$value, $message);
        }
        
        return $message;
    }

    /**
     * Get currency name in current locale
     * 
     * @param string $code Currency code
     * @return string Currency name
     */
    public function getCurrencyName(string $code): string
    {
        return $this->trans('currency_' . strtoupper($code));
    }

    /**
     * Set the current locale
     * 
     * @param string $locale Locale code
     * @return bool True if locale is available
     */
    public function setLocale(string $locale): bool
    {
        if (isset($this->translations[$locale])) {
            $this->locale = $locale;
            return true;
        }
        return false;
    }

    /**
     * Get current locale
     * 
     * @return string Current locale
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Get available locales
     * 
     * @return array Array of locale codes
     */
    public function getAvailableLocales(): array
    {
        return array_keys($this->translations);
    }

    /**
     * Add custom translation
     * 
     * @param string $locale Locale code
     * @param string $key Translation key
     * @param string $message Translation message
     * @return void
     */
    public function addTranslation(string $locale, string $key, string $message): void
    {
        if (!isset($this->translations[$locale])) {
            $this->translations[$locale] = [];
        }
        $this->translations[$locale][$key] = $message;
    }

    /**
     * Format a conversion result message
     * 
     * @param float $amount Original amount
     * @param string $from Source currency
     * @param float $result Converted amount
     * @param string $to Target currency
     * @param bool $fromCache Whether rate was from cache
     * @param int|null $cacheAge Age of cache in seconds
     * @return string Formatted message
     */
    public function formatConversionResult(
        float $amount,
        string $from,
        float $result,
        string $to,
        bool $fromCache = false,
        ?int $cacheAge = null
    ): string {
        $message = $this->trans('conversion_success', [
            'amount' => number_format($amount, 2),
            'from' => $from,
            'result' => number_format($result, 2),
            'to' => $to,
        ]);
        
        if ($fromCache && $cacheAge !== null) {
            $message .= ' - ' . $this->trans('cached_rate', ['seconds' => $cacheAge]);
        }
        
        return $message;
    }
}
