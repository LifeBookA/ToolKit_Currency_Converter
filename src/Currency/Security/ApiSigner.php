<?php

namespace Toolkit\Currency\Security;

/**
 * امضای دیجیتال درخواست‌های API با استفاده از HMAC
 * 
 * ایجاد و اعتبارسنجی امضاهای دیجیتال برای امنیت درخواست‌ها
 * 
 * @package Toolkit\Currency\Security
 * @author Toolkit Team
 * @version 1.2.0
 */
class ApiSigner
{
    /**
     * کلید秘密 برای امضای HMAC
     */
    private string $secretKey;

    /**
     * الگوریتم هش مورد استفاده
     */
    private string $algorithm;

    /**
     * سازنده کلاس
     * 
     * @param string $secretKey کلید秘密 برای امضا
     * @param string $algorithm الگوریتم هش (پیش‌فرض: sha256)
     */
    public function __construct(string $secretKey, string $algorithm = 'sha256')
    {
        if (!in_array($algorithm, hash_algos())) {
            throw new \InvalidArgumentException("Unsupported algorithm: {$algorithm}");
        }
        
        $this->secretKey = $secretKey;
        $this->algorithm = $algorithm;
    }

    /**
     * ایجاد امضا برای یک درخواست
     * 
     * @param string $method متد HTTP (GET, POST, etc.)
     * @param string $endpoint مسیر API
     * @param array $parameters پارامترهای درخواست
     * @param int|null $timestamp زمان درخواست (پیش‌فرض: زمان جاری)
     * @return string امضای تولیدشده به صورت hex
     */
    public function sign(
        string $method,
        string $endpoint,
        array $parameters = [],
        ?int $timestamp = null
    ): string {
        $timestamp = $timestamp ?? time();
        
        // ساخت رشته قابل امضا
        $message = $this->buildMessage($method, $endpoint, $parameters, $timestamp);
        
        // ایجاد HMAC
        return hash_hmac($this->algorithm, $message, $this->secretKey);
    }

    /**
     * اعتبارسنجی امضای یک درخواست
     * 
     * @param string $signature امضای دریافتی
     * @param string $method متد HTTP
     * @param string $endpoint مسیر API
     * @param array $parameters پارامترهای درخواست
     * @param int $timestamp زمان درخواست
     * @param int $tolerance تحمل زمانی به ثانیه برای جلوگیری از replay attacks
     * @return bool true اگر امضا معتبر باشد
     */
    public function verify(
        string $signature,
        string $method,
        string $endpoint,
        array $parameters,
        int $timestamp,
        int $tolerance = 300
    ): bool {
        // بررسی زمان درخواست برای جلوگیری از replay attacks
        $now = time();
        if (abs($now - $timestamp) > $tolerance) {
            return false;
        }
        
        // ایجاد امضای مورد انتظار
        $expectedSignature = $this->sign($method, $endpoint, $parameters, $timestamp);
        
        // مقایسه امنیتی
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * افزودن امضا به هدرهای درخواست
     * 
     * @param string $method متد HTTP
     * @param string $endpoint مسیر API
     * @param array $parameters پارامترهای درخواست
     * @return array هدرهای آماده برای ارسال
     */
    public function getAuthHeaders(
        string $method,
        string $endpoint,
        array $parameters = []
    ): array {
        $timestamp = time();
        $signature = $this->sign($method, $endpoint, $parameters, $timestamp);
        
        return [
            "X-API-Signature: {$signature}",
            "X-API-Timestamp: {$timestamp}",
            "Content-Type: application/json"
        ];
    }

    /**
     * استخراج امضا و timestamp از هدرهای درخواست
     * 
     * @param array $headers آرایه هدرها
     * @return array|null آرایه ['signature' => ..., 'timestamp' => ...] یا null اگر یافت نشد
     */
    public function extractFromHeaders(array $headers): ?array
    {
        $signature = null;
        $timestamp = null;
        
        foreach ($headers as $header) {
            if (stripos($header, 'X-API-Signature:') === 0) {
                $signature = trim(substr($header, strlen('X-API-Signature:')));
            } elseif (stripos($header, 'X-API-Timestamp:') === 0) {
                $timestamp = (int)trim(substr($header, strlen('X-API-Timestamp:')));
            }
        }
        
        if ($signature === null || $timestamp === null) {
            return null;
        }
        
        return [
            'signature' => $signature,
            'timestamp' => $timestamp
        ];
    }

    /**
     * ایجاد توکن احراز هویت موقت
     * 
     * @param string $userId شناسه کاربر
     * @param int $expiresIn مدت اعتبار به ثانیه
     * @return array شامل token و expires_at
     */
    public function createTempToken(string $userId, int $expiresIn = 3600): array
    {
        $expiresAt = time() + $expiresIn;
        $data = "{$userId}:{$expiresAt}";
        $signature = hash_hmac($this->algorithm, $data, $this->secretKey);
        
        return [
            'token' => base64_encode("{$data}:{$signature}"),
            'expires_at' => $expiresAt,
            'expires_in' => $expiresIn
        ];
    }

    /**
     * اعتبارسنجی توکن احراز هویت موقت
     * 
     * @param string $token توکن دریافتی
     * @return array|null اطلاعات کاربر اگر معتبر باشد، null در غیر این صورت
     */
    public function verifyTempToken(string $token): ?array
    {
        $decoded = base64_decode($token, true);
        if ($decoded === false) {
            return null;
        }
        
        $parts = explode(':', $decoded);
        if (count($parts) !== 3) {
            return null;
        }
        
        [$userId, $expiresAt, $signature] = $parts;
        
        // بررسی انقضا
        if (time() > (int)$expiresAt) {
            return null;
        }
        
        // بررسی امضا
        $data = "{$userId}:{$expiresAt}";
        $expectedSignature = hash_hmac($this->algorithm, $data, $this->secretKey);
        
        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }
        
        return [
            'user_id' => $userId,
            'expires_at' => (int)$expiresAt
        ];
    }

    /**
     * ساخت رشته قابل امضا
     */
    private function buildMessage(
        string $method,
        string $endpoint,
        array $parameters,
        int $timestamp
    ): string {
        // مرتب‌سازی پارامترها برای اطمینان از یکنواختی
        ksort($parameters);
        
        $paramsString = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        
        return strtoupper($method) . "\n" .
               $endpoint . "\n" .
               $paramsString . "\n" .
               $timestamp;
    }

    /**
     * تغییر کلید秘密
     * 
     * @param string $newSecretKey کلید جدید
     */
    public function setSecretKey(string $newSecretKey): void
    {
        $this->secretKey = $newSecretKey;
    }

    /**
     * دریافت الگوریتم فعلی
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }
}
