<?php

namespace Toolkit\Currency\Historical;

use Toolkit\Currency\Exceptions\CurrencyException;
use Toolkit\Currency\Exceptions\ApiException;
use Toolkit\Currency\Config\CurrencyConfig;

/**
 * مدیریت نرخ‌های تاریخی ارز
 * 
 * ذخیره‌سازی و بازیابی سری‌های زمانی نرخ‌های تبدیل در فایل‌های JSON
 * 
 * @package Toolkit\Currency\Historical
 * @author Toolkit Team
 * @version 1.2.0
 */
class HistoricalRateManager
{
    /**
     * دایرکتوری ذخیره‌سازی داده‌های تاریخی
     */
    private string $dataDir;

    /**
     * کش داخلی برای دسترسی سریع
     */
    private array $memoryCache = [];

    /**
     * سازنده کلاس
     * 
     * @param string|null $dataDir مسیر دایرکتوری داده‌ها (اختیاری)
     * @throws CurrencyException در صورت عدم امکان ایجاد دایرکتوری
     */
    public function __construct(?string $dataDir = null)
    {
        $this->dataDir = $dataDir ?? (CurrencyConfig::$historicalDataDir ?? __DIR__ . '/../../../data/historical');
        
        if (!is_dir($this->dataDir)) {
            if (!mkdir($this->dataDir, 0755, true)) {
                throw new CurrencyException("Unable to create historical data directory: {$this->dataDir}");
            }
        }
    }

    /**
     * ذخیره نرخ تاریخی برای یک جفت ارز
     * 
     * @param string $from ارز مبدا
     * @param string $to ارز مقصد
     * @param float $rate نرخ تبدیل
     * @param int|null $timestamp زمان ثبت (پیش‌فرض: زمان جاری)
     * @return bool موفقیت عملیات
     * @throws CurrencyException
     */
    public function saveRate(string $from, string $to, float $rate, ?int $timestamp = null): bool
    {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));
        $timestamp = $timestamp ?? time();
        
        $filename = $this->getFilename($from, $to);
        $data = $this->loadData($from, $to);
        
        // افزودن رکورد جدید
        $data[] = [
            'timestamp' => $timestamp,
            'date' => date('Y-m-d H:i:s', $timestamp),
            'rate' => $rate,
            'from' => $from,
            'to' => $to
        ];
        
        // مرتب‌سازی بر اساس زمان
        usort($data, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
        
        return $this->saveData($from, $to, $data);
    }

    /**
     * دریافت نرخ‌های تاریخی برای یک جفت ارز
     * 
     * @param string $from ارز مبدا
     * @param string $to ارز مقصد
     * @param int|null $limit حداکثر تعداد رکوردها (null = همه)
     * @param int|null $startDate زمان شروع (null = از ابتدا)
     * @param int|null $endDate زمان پایان (null = تا کنون)
     * @return array آرایه‌ای از رکوردهای تاریخی
     * @throws CurrencyException
     */
    public function getRates(
        string $from,
        string $to,
        ?int $limit = null,
        ?int $startDate = null,
        ?int $endDate = null
    ): array {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));
        
        $data = $this->loadData($from, $to);
        
        // فیلتر بر اساس بازه زمانی
        if ($startDate !== null) {
            $data = array_filter($data, fn($r) => $r['timestamp'] >= $startDate);
        }
        if ($endDate !== null) {
            $data = array_filter($data, fn($r) => $r['timestamp'] <= $endDate);
        }
        
        // مرتب‌سازی نزولی (جدیدترین اول)
        usort($data, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
        
        // محدود کردن تعداد
        if ($limit !== null && $limit > 0) {
            $data = array_slice($data, 0, $limit);
        }
        
        return array_values($data);
    }

    /**
     * دریافت آخرین نرخ ثبت‌شده
     * 
     * @param string $from ارز مبدا
     * @param string $to ارز مقصد
     * @return float|null نرخ آخرین یا null اگر داده‌ای وجود ندارد
     * @throws CurrencyException
     */
    public function getLastRate(string $from, string $to): ?float
    {
        $rates = $this->getRates($from, $to, 1);
        return !empty($rates) ? $rates[0]['rate'] : null;
    }

    /**
     * دریافت نرخ در یک تاریخ خاص (نزدیک‌ترین نرخ قبل یا بعد از تاریخ)
     * 
     * @param string $from ارز مبدا
     * @param string $to ارز مقصد
     * @param int $timestamp زمان مورد نظر
     * @param string $direction جهت جستجو ('before', 'after', 'nearest')
     * @return float|null نرخ یا null اگر یافت نشد
     * @throws CurrencyException
     */
    public function getRateAt(string $from, string $to, int $timestamp, string $direction = 'nearest'): ?float
    {
        $allRates = $this->getRates($from, $to);
        
        if (empty($allRates)) {
            return null;
        }
        
        $before = null;
        $after = null;
        
        foreach ($allRates as $rate) {
            if ($rate['timestamp'] <= $timestamp) {
                $before = $rate;
                break;
            }
            if ($rate['timestamp'] >= $timestamp && $after === null) {
                $after = $rate;
            }
        }
        
        switch ($direction) {
            case 'before':
                return $before ? $before['rate'] : null;
            case 'after':
                return $after ? $after['rate'] : null;
            case 'nearest':
            default:
                if ($before && $after) {
                    $diffBefore = abs($timestamp - $before['timestamp']);
                    $diffAfter = abs($timestamp - $after['timestamp']);
                    return ($diffBefore <= $diffAfter) ? $before['rate'] : $after['rate'];
                }
                return $before ? $before['rate'] : ($after ? $after['rate'] : null);
        }
    }

    /**
     * حذف داده‌های تاریخی قدیمی‌تر از یک زمان مشخص
     * 
     * @param int $olderThan حذف رکوردهای قدیمی‌تر از این زمان
     * @param string|null $from ارز مبدا (null = همه ارزها)
     * @param string|null $to ارز مقصد (null = همه ارزها)
     * @return int تعداد رکوردهای حذف‌شده
     * @throws CurrencyException
     */
    public function pruneOldRecords(int $olderThan, ?string $from = null, ?string $to = null): int
    {
        $deleted = 0;
        
        if ($from && $to) {
            $from = strtoupper(trim($from));
            $to = strtoupper(trim($to));
            $data = $this->loadData($from, $to);
            $originalCount = count($data);
            $data = array_filter($data, fn($r) => $r['timestamp'] >= $olderThan);
            $deleted = $originalCount - count($data);
            if ($deleted > 0) {
                $this->saveData($from, $to, array_values($data));
            }
        } else {
            // جستجو در تمام فایل‌ها
            $files = glob($this->dataDir . '/*.json');
            foreach ($files as $file) {
                $basename = basename($file, '.json');
                $parts = explode('_', $basename);
                if (count($parts) !== 2) {
                    continue;
                }
                
                $fileFrom = $parts[0];
                $fileTo = $parts[1];
                
                if ($from && $fileFrom !== $from) {
                    continue;
                }
                if ($to && $fileTo !== $to) {
                    continue;
                }
                
                $data = json_decode(file_get_contents($file), true) ?: [];
                $originalCount = count($data);
                $data = array_filter($data, fn($r) => $r['timestamp'] >= $olderThan);
                $deleted += $originalCount - count($data);
                
                if ($deleted > 0) {
                    $this->saveData($fileFrom, $fileTo, array_values($data));
                }
            }
        }
        
        return $deleted;
    }

    /**
     * تولید نمودار SVG ساده از نرخ‌های تاریخی
     * 
     * @param string $from ارز مبدا
     * @param string $to ارز مقصد
     * @param int $limit تعداد رکوردها برای نمایش
     * @param int $width عرض نمودار
     * @param int $height ارتفاع نمودار
     * @return string محتوای SVG
     * @throws CurrencyException
     */
    public function generateSvgChart(
        string $from,
        string $to,
        int $limit = 30,
        int $width = 800,
        int $height = 400
    ): string {
        $rates = $this->getRates($from, $to, $limit);
        
        if (empty($rates)) {
            return $this->generateEmptyChart($width, $height, "No data for {$from}/{$to}");
        }
        
        // معکوس کردن برای نمایش از چپ به راست (قدیمی به جدید)
        $rates = array_reverse($rates);
        
        $minRate = min(array_column($rates, 'rate'));
        $maxRate = max(array_column($rates, 'rate'));
        $range = $maxRate - $minRate;
        
        // اضافه کردن حاشیه به range
        if ($range == 0) {
            $range = $minRate * 0.1;
            $minRate -= $range / 2;
            $maxRate += $range / 2;
        } else {
            $padding = $range * 0.1;
            $minRate -= $padding;
            $maxRate += $padding;
            $range = $maxRate - $minRate;
        }
        
        $padding = 60;
        $chartWidth = $width - 2 * $padding;
        $chartHeight = $height - 2 * $padding;
        
        // ساخت نقاط polyline
        $points = [];
        foreach ($rates as $index => $rate) {
            $x = $padding + ($index / max(count($rates) - 1, 1)) * $chartWidth;
            $y = $padding + $chartHeight - (($rate['rate'] - $minRate) / $range) * $chartHeight;
            $points[] = "{$x},{$y}";
        }
        
        // ساخت گرید خطوط
        $gridLines = '';
        for ($i = 0; $i <= 5; $i++) {
            $y = $padding + ($i / 5) * $chartHeight;
            $value = $maxRate - ($i / 5) * $range;
            $gridLines .= "<line x1=\"{$padding}\" y1=\"{$y}\" x2=\"" . ($width - $padding) . "\" y2=\"{$y}\" stroke=\"#e0e0e0\" stroke-width=\"1\"/>\n";
            $gridLines .= "<text x=\"" . ($padding - 10) . "\" y=\"" . ($y + 4) . "\" text-anchor=\"end\" font-size=\"10\" fill=\"#666\">" . number_format($value, 4) . "</text>\n";
        }
        
        // ساخت نقاط روی خط
        $dots = '';
        foreach ($rates as $index => $rate) {
            $x = $padding + ($index / max(count($rates) - 1, 1)) * $chartWidth;
            $y = $padding + $chartHeight - (($rate['rate'] - $minRate) / $range) * $chartHeight;
            $dots .= "<circle cx=\"{$x}\" cy=\"{$y}\" r=\"3\" fill=\"#2563eb\" stroke=\"#fff\" stroke-width=\"1\">\n";
            $dots .= "<title>" . date('Y-m-d', $rate['timestamp']) . ": " . number_format($rate['rate'], 4) . "</title>\n";
            $dots .= "</circle>\n";
        }
        
        $polylinePoints = implode(' ', $points);
        $firstDate = date('Y-m-d', $rates[0]['timestamp']);
        $lastDate = date('Y-m-d', end($rates)['timestamp']);
        
        return <<<'SVG'
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
  <rect width="100%" height="100%" fill="#ffffff"/>
  
  <!-- Title -->
  <text x="{$width}/2" y="30" text-anchor="middle" font-size="16" font-weight="bold" fill="#333">
    {$from} to {$to} Exchange Rate History
  </text>
  
  <!-- Subtitle -->
  <text x="{$width}/2" y="48" text-anchor="middle" font-size="12" fill="#666">
    {$firstDate} to {$lastDate} ({$limit} data points)
  </text>
  
  <!-- Grid Lines -->
  {$gridLines}
  
  <!-- Chart Area Border -->
  <rect x="{$padding}" y="{$padding}" width="{$chartWidth}" height="{$chartHeight}" fill="none" stroke="#ccc" stroke-width="1"/>
  
  <!-- Line Chart -->
  <polyline points="{$polylinePoints}" fill="none" stroke="#2563eb" stroke-width="2"/>
  
  <!-- Data Points -->
  {$dots}
  
  <!-- X-axis Labels -->
  <text x="{$padding}" y="{$height - 20}" font-size="10" fill="#666">{$firstDate}</text>
  <text x="{$width - $padding}" y="{$height - 20}" font-size="10" fill="#666" text-anchor="end">{$lastDate}</text>
  
  <!-- Axis Titles -->
  <text x="{$width}/2" y="{$height - 5}" text-anchor="middle" font-size="12" fill="#333">Date</text>
  <text x="15" y="{$height}/2" text-anchor="middle" font-size="12" fill="#333" transform="rotate(-90, 15, {$height}/2)">Rate</text>
</svg>
SVG;
    }

    /**
     * تولید نمودار خالی با پیام
     */
    private function generateEmptyChart(int $width, int $height, string $message): string
    {
        return <<<'SVG'
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
  <rect width="100%" height="100%" fill="#f9fafb"/>
  <text x="{$width}/2" y="{$height}/2" text-anchor="middle" font-size="14" fill="#666">{$message}</text>
</svg>
SVG;
    }

    /**
     * دریافت نام فایل برای یک جفت ارز
     */
    private function getFilename(string $from, string $to): string
    {
        return $this->dataDir . "/{$from}_{$to}.json";
    }

    /**
     * بارگذاری داده‌ها از فایل
     * 
     * @return array
     * @throws CurrencyException
     */
    private function loadData(string $from, string $to): array
    {
        $key = "{$from}_{$to}";
        
        if (isset($this->memoryCache[$key])) {
            return $this->memoryCache[$key];
        }
        
        $filename = $this->getFilename($from, $to);
        
        if (!file_exists($filename)) {
            return [];
        }
        
        $content = file_get_contents($filename);
        if ($content === false) {
            throw new CurrencyException("Unable to read historical data file: {$filename}");
        }
        
        $data = json_decode($content, true);
        if (!is_array($data)) {
            return [];
        }
        
        $this->memoryCache[$key] = $data;
        return $data;
    }

    /**
     * ذخیره داده‌ها در فایل
     * 
     * @param array $data داده‌ها برای ذخیره
     * @return bool موفقیت عملیات
     * @throws CurrencyException
     */
    private function saveData(string $from, string $to, array $data): bool
    {
        $key = "{$from}_{$to}";
        $filename = $this->getFilename($from, $to);
        
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($content === false) {
            throw new CurrencyException("Unable to encode historical data to JSON");
        }
        
        $result = file_put_contents($filename, $content, LOCK_EX);
        
        if ($result === false) {
            throw new CurrencyException("Unable to write historical data file: {$filename}");
        }
        
        // پاک کردن کش
        unset($this->memoryCache[$key]);
        
        return true;
    }

    /**
     * دریافت لیست تمام جفت‌ارزهای دارای داده تاریخی
     * 
     * @return array آرایه‌ای از ['from' => ..., 'to' => ..., 'count' => ...]
     */
    public function getAllPairs(): array
    {
        $pairs = [];
        $files = glob($this->dataDir . '/*.json');
        
        foreach ($files as $file) {
            $basename = basename($file, '.json');
            $parts = explode('_', $basename);
            
            if (count($parts) !== 2) {
                continue;
            }
            
            $data = json_decode(file_get_contents($file), true) ?: [];
            
            $pairs[] = [
                'from' => $parts[0],
                'to' => $parts[1],
                'count' => count($data),
                'latest' => !empty($data) ? end($data)['date'] : null
            ];
        }
        
        return $pairs;
    }
}
