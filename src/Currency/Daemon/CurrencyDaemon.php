<?php

namespace Toolkit\Currency\Daemon;

use Toolkit\Currency\CurrencyConverter;
use Toolkit\Currency\Config\CurrencyConfig;
use Toolkit\Currency\Provider\ProviderFactory;
use Toolkit\Currency\Cache\FileCacheManager;
use Toolkit\Currency\Historical\HistoricalRateManager;
use Toolkit\Currency\Alerts\RateAlertManager;
use Toolkit\Currency\Log\SimpleLogger;

/**
 * Daemon Mode برای بروزرسانی خودکار نرخ‌ها و بررسی هشدارها
 * 
 * اسکریپتی که به صورت پس‌زمینه اجرا شده و به صورت دوره‌ای نرخ‌ها را آپدیت می‌کند
 * 
 * @package Toolkit\Currency\Daemon
 * @author Toolkit Team
 * @version 1.2.0
 */
class CurrencyDaemon
{
    /**
     * فاصله زمانی بین هر بروزرسانی (ثانیه)
     */
    private int $interval;

    /**
     * لیست جفت‌ارزهای مورد نظارت
     */
    private array $watchPairs;

    /**
     * مبدل ارز
     */
    private CurrencyConverter $converter;

    /**
     * مدیریت نرخ‌های تاریخی
     */
    private HistoricalRateManager $historicalManager;

    /**
     * مدیریت هشدارها
     */
    private RateAlertManager $alertManager;

    /**
     * سیستم لاگ
     */
    private SimpleLogger $logger;

    /**
     * وضعیت اجرای daemon
     */
    private bool $running = false;

    /**
     * PID فرآیند
     */
    private ?int $pid = null;

    /**
     * فایل PID
     */
    private string $pidFile;

    /**
     * فایل لاگ
     */
    private string $logFile;

    /**
     * سازنده کلاس
     * 
     * @param int $interval فاصله زمانی بین بروزرسانی‌ها به ثانیه (پیش‌فرض: 3600 = 1 ساعت)
     * @param array|null $watchPairs جفت‌ارزهای مورد نظارت (null = استفاده از پیش‌فرض)
     * @param string|null $pidFile مسیر فایل PID
     * @param string|null $logFile مسیر فایل لاگ
     */
    public function __construct(
        int $interval = 3600,
        ?array $watchPairs = null,
        ?string $pidFile = null,
        ?string $logFile = null
    ) {
        $this->interval = $interval;
        $this->watchPairs = $watchPairs ?? [
            ['USD', 'EUR'],
            ['USD', 'GBP'],
            ['USD', 'IRR'],
            ['EUR', 'GBP'],
            ['GBP', 'JPY']
        ];
        
        $this->pidFile = $pidFile ?? __DIR__ . '/../../../data/daemon.pid';
        $this->logFile = $logFile ?? __DIR__ . '/../../../data/daemon.log';
        
        // راه‌اندازی کامپوننت‌ها
        $provider = ProviderFactory::create();
        $cache = new FileCacheManager();
        $this->converter = new CurrencyConverter($provider, $cache);
        $this->historicalManager = new HistoricalRateManager();
        $this->alertManager = new RateAlertManager();
        $this->logger = new SimpleLogger('CurrencyDaemon', dirname($this->logFile));
        
        // ثبت handler برای سیگنال‌ها
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
            pcntl_signal(SIGHUP, [$this, 'handleSignal']);
        }
    }

    /**
     * شروع daemon
     * 
     * @param bool $foreground اجرای در foreground (برای دیباگ)
     * @return bool موفقیت عملیات
     */
    public function start(bool $foreground = false): bool
    {
        if ($this->isRunning()) {
            $this->logger->error('Daemon is already running');
            return false;
        }
        
        if (!$foreground && function_exists('pcntl_fork')) {
            // Fork کردن به پس‌زمینه
            $pid = pcntl_fork();
            
            if ($pid === -1) {
                $this->logger->error('Failed to fork daemon process');
                return false;
            }
            
            if ($pid > 0) {
                // فرآیند والد
                $this->logger->info("Daemon started with PID {$pid}");
                echo "Daemon started with PID {$pid}\n";
                return true;
            }
            
            // فرآیند child
            $this->pid = posix_getpid();
            $this->detach();
        } else {
            // اجرای در foreground یا بدون pcntl
            $this->pid = posix_getpid();
            $this->logger->info("Daemon started in foreground mode with PID {$this->pid}");
            echo "Daemon started in foreground mode with PID {$this->pid}\n";
        }
        
        // ذخیره PID
        $this->writePidFile();
        
        $this->running = true;
        $this->run();
        
        return true;
    }

    /**
     * توقف daemon
     */
    public function stop(): bool
    {
        if (!$this->isRunning()) {
            $this->logger->warning('Daemon is not running');
            return false;
        }
        
        $pid = $this->readPidFile();
        if ($pid === null) {
            $this->logger->error('PID file not found');
            return false;
        }
        
        if (!posix_kill($pid, SIGTERM)) {
            $this->logger->error("Failed to send SIGTERM to PID {$pid}");
            return false;
        }
        
        $this->logger->info("Sent stop signal to daemon (PID: {$pid})");
        echo "Daemon stopped\n";
        
        return true;
    }

    /**
     * بررسی وضعیت daemon
     */
    public function status(): array
    {
        $pid = $this->readPidFile();
        $isRunning = $pid !== null && posix_kill($pid, 0);
        
        return [
            'running' => $isRunning,
            'pid' => $pid,
            'pid_file' => $this->pidFile,
            'log_file' => $this->logFile,
            'interval' => $this->interval,
            'watch_pairs' => count($this->watchPairs)
        ];
    }

    /**
     * اجرای حلقه اصلی daemon
     */
    private function run(): void
    {
        $this->logger->info('Daemon main loop started');
        
        while ($this->running) {
            // پردازش سیگنال‌ها
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
            
            try {
                $this->updateRates();
                $this->checkAlerts();
                
                $this->logger->info('Update cycle completed, sleeping for ' . $this->interval . ' seconds');
            } catch (\Exception $e) {
                $this->logger->error('Error in update cycle: ' . $e->getMessage());
            }
            
            // خوابیدن تا چرخه بعدی
            $sleepTime = $this->interval;
            while ($sleepTime > 0 && $this->running) {
                usleep(min(1000000, $sleepTime * 1000000));
                $sleepTime--;
                
                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }
            }
        }
        
        $this->cleanup();
    }

    /**
     * بروزرسانی نرخ‌ها برای تمام جفت‌ارزهای تحت نظارت
     */
    private function updateRates(): void
    {
        $this->logger->info('Starting rate update cycle');
        
        foreach ($this->watchPairs as $pair) {
            [$from, $to] = $pair;
            
            try {
                $result = $this->converter->convert(1, $from, $to);
                
                // ذخیره در تاریخچه
                $this->historicalManager->saveRate($from, $to, $result->rate, $result->timestamp);
                
                $this->logger->info("Updated {$from}/{$to}: {$result->rate}");
            } catch (\Exception $e) {
                $this->logger->error("Failed to update {$from}/{$to}: {$e->getMessage()}");
            }
        }
        
        $this->logger->info('Rate update cycle completed');
    }

    /**
     * بررسی هشدارهای فعال
     */
    private function checkAlerts(): void
    {
        $this->logger->info('Checking alerts');
        
        try {
            $triggeredAlerts = $this->alertManager->checkAllAlerts($this->converter);
            
            foreach ($triggeredAlerts as $alert) {
                $this->logger->info(
                    "Alert triggered: {$alert['from']}/{$alert['to']} " .
                    "at rate {$alert['target_rate']} (current: {$alert['current_rate']})"
                );
            }
            
            if (empty($triggeredAlerts)) {
                $this->logger->debug('No alerts triggered');
            }
        } catch (\Exception $e) {
            $this->logger->error('Error checking alerts: ' . $e->getMessage());
        }
    }

    /**
     * جدا کردن فرآیند از ترمینال (daemonize)
     */
    private function detach(): void
    {
        // تغییر_umask
        umask(0);
        
        // ایجاد session جدید
        if (function_exists('posix_setsid')) {
            posix_setsid();
        }
        
        // تغییر دایرکتوری به root
        chdir('/');
        
        // بستن file descriptorهای استاندارد
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);
        
        // باز کردن مجدد به /dev/null
        fopen('/dev/null', 'r');
        fopen('/dev/null', 'w');
        fopen('/dev/null', 'w');
    }

    /**
     * نوشتن فایل PID
     */
    private function writePidFile(): void
    {
        $dir = dirname($this->pidFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($this->pidFile, $this->pid, LOCK_EX);
    }

    /**
     * خواندن فایل PID
     */
    private function readPidFile(): ?int
    {
        if (!file_exists($this->pidFile)) {
            return null;
        }
        
        $content = file_get_contents($this->pidFile);
        return $content !== false ? (int)$content : null;
    }

    /**
     * حذف فایل PID
     */
    private function cleanup(): void
    {
        if (file_exists($this->pidFile)) {
            unlink($this->pidFile);
        }
        
        $this->logger->info('Daemon stopped, PID file removed');
    }

    /**
     * بررسی اینکه آیا daemon در حال اجرا است
     */
    public function isRunning(): bool
    {
        $pid = $this->readPidFile();
        return $pid !== null && posix_kill($pid, 0);
    }

    /**
     * مدیریت سیگنال‌ها
     */
    public function handleSignal(int $signal): void
    {
        switch ($signal) {
            case SIGTERM:
            case SIGINT:
                $this->logger->info('Received stop signal');
                $this->running = false;
                break;
                
            case SIGHUP:
                $this->logger->info('Received HUP signal, reloading configuration');
                // در اینجا می‌توان تنظیمات را مجدداً بارگذاری کرد
                break;
        }
    }

    /**
     * افزودن جفت ارز جدید به لیست نظارت
     */
    public function addWatchPair(string $from, string $to): void
    {
        $this->watchPairs[] = [$from, $to];
        $this->logger->info("Added watch pair: {$from}/{$to}");
    }

    /**
     * حذف جفت ارز از لیست نظارت
     */
    public function removeWatchPair(string $from, string $to): void
    {
        $this->watchPairs = array_filter(
            $this->watchPairs,
            fn($pair) => !($pair[0] === $from && $pair[1] === $to)
        );
        $this->logger->info("Removed watch pair: {$from}/{$to}");
    }

    /**
     * دریافت لاگ‌های اخیر
     * 
     * @param int $lines تعداد خطوط
     * @return array خطوط لاگ
     */
    public function getRecentLogs(int $lines = 50): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $file = new \SplFileObject($this->logFile);
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        $startLine = max(0, $totalLines - $lines);
        $logs = [];
        
        $file->seek($startLine);
        while (!$file->eof()) {
            $line = $file->current();
            if ($line !== null && trim($line) !== '') {
                $logs[] = trim($line);
            }
            $file->next();
        }
        
        return $logs;
    }
}
