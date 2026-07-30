<?php

namespace Toolkit\Currency\Alerts;

use Toolkit\Currency\Contracts\CurrencyConverterInterface;

/**
 * Rate Alert Manager
 * 
 * Manages multiple rate alerts and checks them against current rates
 * 
 * @package Toolkit\Currency\Alerts
 */
class RateAlertManager
{
    /**
     * Currency converter instance
     */
    protected CurrencyConverterInterface $converter;

    /**
     * Array of active alerts
     */
    protected array $alerts = [];

    /**
     * Notification callback
     */
    protected $notificationCallback;

    /**
     * Constructor
     * 
     * @param CurrencyConverterInterface $converter Currency converter
     * @param callable|null $notificationCallback Callback for notifications
     */
    public function __construct(
        CurrencyConverterInterface $converter,
        ?callable $notificationCallback = null
    ) {
        $this->converter = $converter;
        $this->notificationCallback = $notificationCallback ?? [$this, 'defaultNotification'];
    }

    /**
     * Add a new alert
     * 
     * @param RateAlert $alert Alert to add
     * @return void
     */
    public function addAlert(RateAlert $alert): void
    {
        $key = $this->generateAlertKey($alert);
        $this->alerts[$key] = $alert;
    }

    /**
     * Remove an alert
     * 
     * @param string $from Source currency
     * @param string $to Target currency
     * @param float $targetRate Target rate
     * @return bool True if removed
     */
    public function removeAlert(string $from, string $to, float $targetRate): bool
    {
        $alert = new RateAlert($from, $to, $targetRate);
        $key = $this->generateAlertKey($alert);
        
        if (isset($this->alerts[$key])) {
            unset($this->alerts[$key]);
            return true;
        }
        
        return false;
    }

    /**
     * Check all alerts against current rates
     * 
     * @return array Array of triggered alerts
     */
    public function checkAll(): array
    {
        $triggered = [];

        foreach ($this->alerts as $key => $alert) {
            if (!$alert->isActive()) {
                continue;
            }

            try {
                $currentRate = $this->converter->getRate($alert->getFrom(), $alert->getTo());
                
                if ($alert->check($currentRate)) {
                    $alert->trigger();
                    $triggered[] = [
                        'alert' => $alert,
                        'currentRate' => $currentRate,
                        'timestamp' => time(),
                    ];

                    // Send notification
                    call_user_func(
                        $this->notificationCallback,
                        $alert,
                        $currentRate
                    );
                }
            } catch (\Exception $e) {
                // Log error but continue checking other alerts
                error_log("Failed to check alert {$key}: " . $e->getMessage());
            }
        }

        return $triggered;
    }

    /**
     * Get all active alerts
     * 
     * @return array Array of RateAlert objects
     */
    public function getAllAlerts(): array
    {
        return $this->alerts;
    }

    /**
     * Clear all alerts
     * 
     * @return void
     */
    public function clearAll(): void
    {
        $this->alerts = [];
    }

    /**
     * Get alert count
     * 
     * @return int
     */
    public function getAlertCount(): int
    {
        return count($this->alerts);
    }

    /**
     * Generate unique key for alert
     * 
     * @param RateAlert $alert Alert object
     * @return string Unique key
     */
    protected function generateAlertKey(RateAlert $alert): string
    {
        return "{$alert->getFrom()}_{$alert->getTo()}_{$alert->getTargetRate()}_{$alert->getOperator()}";
    }

    /**
     * Default notification handler
     * 
     * @param RateAlert $alert Triggered alert
     * @param float $currentRate Current rate
     * @return void
     */
    protected function defaultNotification(RateAlert $alert, float $currentRate): void
    {
        $message = sprintf(
            "ALERT: %s rate is now %.4f (target: %s %.4f)\n",
            $alert->getDescription(),
            $currentRate,
            $alert->getOperator(),
            $alert->getTargetRate()
        );
        
        error_log($message);
        
        // Could be extended to send email, SMS, webhook, etc.
    }

    /**
     * Set custom notification callback
     * 
     * @param callable $callback Notification callback
     * @return void
     */
    public function setNotificationCallback(callable $callback): void
    {
        $this->notificationCallback = $callback;
    }
}
