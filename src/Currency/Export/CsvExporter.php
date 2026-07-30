<?php

namespace Toolkit\Currency\Export;

use Toolkit\Currency\Result\ConversionResult;

/**
 * تولید خروجی‌های CSV و Excel برای داده‌های Batch
 * 
 * @package Toolkit\Currency\Export
 * @author Toolkit Team
 * @version 1.2.0
 */
class CsvExporter
{
    /**
     * جداکننده ستون‌ها
     */
    private string $delimiter;

    /**
     * enclosure برای فیلدها
     */
    private string $enclosure;

    /**
     * سازنده کلاس
     * 
     * @param string $delimiter جداکننده (پیش‌فرض: کاما)
     * @param string $enclosure کاراکتر enclosure (پیش‌فرض: دابل‌کوت)
     */
    public function __construct(string $delimiter = ',', string $enclosure = '"')
    {
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
    }

    /**
     * صادرات نتایج تبدیل به فرمت CSV
     * 
     * @param array $results آرایه‌ای از ConversionResult
     * @param bool $includeHeader شامل هدر باشد یا نه
     * @return string محتوای CSV
     */
    public function export(array $results, bool $includeHeader = true): string
    {
        $output = fopen('php://temp', 'r+');
        
        if ($includeHeader) {
            fputcsv(
                $output,
                ['Amount', 'From', 'To', 'Rate', 'Result', 'Timestamp', 'FromCache'],
                $this->delimiter,
                $this->enclosure
            );
        }
        
        foreach ($results as $result) {
            if (!$result instanceof ConversionResult) {
                continue;
            }
            
            fputcsv(
                $output,
                [
                    $result->amount,
                    $result->from,
                    $result->to,
                    $result->rate,
                    $result->result,
                    date('Y-m-d H:i:s', $result->timestamp),
                    $result->fromCache ? 'Yes' : 'No'
                ],
                $this->delimiter,
                $this->enclosure
            );
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * صادرات نرخ‌های تاریخی به CSV
     * 
     * @param array $rates آرایه‌ای از رکوردهای تاریخی
     * @return string محتوای CSV
     */
    public function exportHistoricalRates(array $rates): string
    {
        $output = fopen('php://temp', 'r+');
        
        fputcsv(
            $output,
            ['Date', 'From', 'To', 'Rate', 'Timestamp'],
            $this->delimiter,
            $this->enclosure
        );
        
        foreach ($rates as $rate) {
            fputcsv(
                $output,
                [
                    $rate['date'] ?? '',
                    $rate['from'] ?? '',
                    $rate['to'] ?? '',
                    $rate['rate'] ?? 0,
                    $rate['timestamp'] ?? 0
                ],
                $this->delimiter,
                $this->enclosure
            );
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * ذخیره خروجی CSV در فایل
     * 
     * @param string $content محتوای CSV
     * @param string $filename مسیر فایل
     * @return bool موفقیت عملیات
     */
    public function saveToFile(string $content, string $filename): bool
    {
        $result = file_put_contents($filename, $content, LOCK_EX);
        return $result !== false;
    }

    /**
     * دانلود خروجی CSV به صورت HTTP
     * 
     * @param string $content محتوای CSV
     * @param string $filename نام فایل برای دانلود
     */
    public function download(string $content, string $filename): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $content;
        exit;
    }

    /**
     * تغییر جداکننده
     */
    public function setDelimiter(string $delimiter): void
    {
        $this->delimiter = $delimiter;
    }

    /**
     * دریافت جداکننده فعلی
     */
    public function getDelimiter(): string
    {
        return $this->delimiter;
    }
}
