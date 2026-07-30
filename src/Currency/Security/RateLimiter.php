<?php

namespace Toolkit\Currency\Security;

/**
 * محدودکننده نرخ مبتنی بر فایل
 * 
 * جلوگیری از ارسال درخواست‌های بیش از حد به API با استفاده از ذخیره‌سازی در فایل
 * 
 * @package Toolkit\Currency\Security
 * @author Toolkit Team
 * @version 1.2.0
 */
class RateLimiter
{
    /**
     * دایرکتوری ذخیره‌سازی داده‌های rate limiting
     */
    private string $dataDir;

    /**
     * حداکثر تعداد درخواست‌ها در هر پنجره زمانی
     */
    private int $maxRequests;

    /**
     * طول پنجره زمانی بر حسب ثانیه
     */
    private int $windowSeconds;

    /**
     * کش داخلی برای عملکرد بهتر
     */
    private array $cache = [];

    /**
     * سازنده کلاس
     * 
     * @param int $maxRequests حداکثر تعداد درخواست (پیش‌فرض: 60)
     * @param int $windowSeconds طول پنجره زمانی به ثانیه (پیش‌فرض: 60)
     * @param string|null $dataDir مسیر دایرکتوری داده‌ها
     */
    public function __construct(
        int $maxRequests = 60,
        int $windowSeconds = 60,
        ?string $dataDir = null
    ) {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->dataDir = $dataDir ?? __DIR__ . '/../../../data/rate_limits';
        
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }

    /**
     * بررسی اینکه آیا درخواست مجاز است یا خیر
     * 
     * @param string $identifier شناسه کاربر/IP/کلید
     * @return bool true اگر مجاز باشد، false اگر محدود شده باشد
     */
    public function isAllowed(string $identifier): bool
    {
        $key = $this->normalizeKey($identifier);
        $now = time();
        $windowStart = $now - $this->windowSeconds;
        
        // بررسی کش
        if (isset($this->cache[$key])) {
            $requests = $this->cache[$key];
            // فیلتر کردن درخواست‌های قدیمی
            $requests = array_filter($requests, fn($time) => $time > $windowStart);
            $this->cache[$key] = $requests;
            
            if (count($requests) >= $this->maxRequests) {
                return false;
            }
            
            $requests[] = $now;
            $this->cache[$key] = $requests;
            $this->persistRequests($key, $requests);
            return true;
        }
        
        // بارگذاری از فایل
        $requests = $this->loadRequests($key);
        $requests = array_filter($requests, fn($time) => $time > $windowStart);
        
        if (count($requests) >= $this->maxRequests) {
            $this->cache[$key] = $requests;
            return false;
        }
        
        $requests[] = $now;
        $this->cache[$key] = $requests;
        $this->persistRequests($key, $requests);
        return true;
    }

    /**
     * دریافت تعداد درخواست‌های باقی‌مانده در پنجره جاری
     * 
     * @param string $identifier شناسه کاربر/IP/کلید
     * @return int تعداد درخواست‌های مجاز باقی‌مانده
     */
    public function getRemainingRequests(string $identifier): int
    {
        $key = $this->normalizeKey($identifier);
        $now = time();
        $windowStart = $now - $this->windowSeconds;
        
        $requests = $this->cache[$key] ?? $this->loadRequests($key);
        $requests = array_filter($requests, fn($time) => $time > $windowStart);
        
        return max(0, $this->maxRequests - count($requests));
    }

    /**
     * دریافت زمان باقی‌مانده تا ریست شدن محدودیت (به ثانیه)
     * 
     * @param string $identifier شناسه کاربر/IP/کلید
     * @return int ثانیه‌های باقی‌مانده
     */
    public function getResetTime(string $identifier): int
    {
        $key = $this->normalizeKey($identifier);
        $now = time();
        $windowStart = $now - $this->windowSeconds;
        
        $requests = $this->cache[$key] ?? $this->loadRequests($key);
        $requests = array_filter($requests, fn($time) => $time > $windowStart);
        
        if (empty($requests)) {
            return 0;
        }
        
        $oldestRequest = min($requests);
        return max(0, ($oldestRequest + $this->windowSeconds) - $now);
    }

    /**
     * صبر کردن تا زمانی که درخواست مجاز شود
     * 
     * @param string $identifier شناسه کاربر/IP/کلید
     * @param int $maxWaitTime حداکثر زمان انتظار به ثانیه (0 = بی‌نهایت)
     * @return bool true اگر موفق شد، false اگر timeout رخ داد
     */
    public function waitIfLimited(string $identifier, int $maxWaitTime = 0): bool
    {
        $startTime = time();
        
        while (!$this->isAllowed($identifier)) {
            $elapsed = time() - $startTime;
            
            if ($maxWaitTime > 0 && $elapsed >= $maxWaitTime) {
                return false;
            }
            
            $resetTime = $this->getResetTime($identifier);
            $sleepTime = min($resetTime + 1, $maxWaitTime > 0 ? $maxWaitTime - $elapsed : $resetTime + 1);
            
            if ($sleepTime > 0) {
                usleep(min($sleepTime, 10) * 1000000); // حداکثر 10 ثانیه در هر بار
            }
        }
        
        return true;
    }

    /**
     * پاک کردن تاریخچه یک شناسه خاص
     * 
     * @param string $identifier شناسه کاربر/IP/کلید
     * @return bool موفقیت عملیات
     */
    public function reset(string $identifier): bool
    {
        $key = $this->normalizeKey($identifier);
        $filename = $this->getFilename($key);
        
        unset($this->cache[$key]);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }

    /**
     * پاک کردن تمام تاریخچه‌ها
     * 
     * @return int تعداد فایل‌های حذف‌شده
     */
    public function resetAll(): int
    {
        $count = 0;
        $files = glob($this->dataDir . '/*.dat');
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $count++;
            }
        }
        
        $this->cache = [];
        return $count;
    }

    /**
     * نرمال‌سازی کلید برای استفاده در نام فایل
     */
    private function normalizeKey(string $key): string
    {
        return hash('sha256', $key);
    }

    /**
     * دریافت نام فایل برای یک کلید
     */
    private function getFilename(string $key): string
    {
        return $this->dataDir . "/{$key}.dat";
    }

    /**
     * بارگذاری درخواست‌ها از فایل
     * 
     * @return array آرایه‌ای از timestampها
     */
    private function loadRequests(string $key): array
    {
        $filename = $this->getFilename($key);
        
        if (!file_exists($filename)) {
            return [];
        }
        
        $content = file_get_contents($filename);
        if ($content === false) {
            return [];
        }
        
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    /**
     * ذخیره درخواست‌ها در فایل
     * 
     * @param array $requests آرایه‌ای از timestampها
     */
    private function persistRequests(string $key, array $requests): void
    {
        $filename = $this->getFilename($key);
        $content = json_encode(array_values($requests));
        
        file_put_contents($filename, $content, LOCK_EX);
    }

    /**
     * دریافت اطلاعات وضعیت برای یک شناسه
     * 
     * @param string $identifier شناسه کاربر/IP/کلید
     * @return array شامل max_requests, remaining, reset_time, window_seconds
     */
    public function getStatus(string $identifier): array
    {
        return [
            'max_requests' => $this->maxRequests,
            'remaining' => $this->getRemainingRequests($identifier),
            'reset_time' => $this->getResetTime($identifier),
            'window_seconds' => $this->windowSeconds,
            'limited' => $this->getRemainingRequests($identifier) === 0
        ];
    }
}
