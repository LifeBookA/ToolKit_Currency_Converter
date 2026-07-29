<?php

namespace Toolkit\Currency\Web;

use Toolkit\Currency\CurrencyConverter;
use Toolkit\Currency\Config\CurrencyConfig;
use Toolkit\Currency\Helpers\CurrencyHelper;
use Toolkit\Currency\Exceptions\CurrencyException;
use Toolkit\Currency\Exceptions\InvalidCurrencyException;

/**
 * Simple Web Dashboard for Currency Converter
 *
 * Native PHP web interface without external dependencies
 * Provides a basic HTML interface for currency conversion
 *
 * @package Toolkit\Currency\Web
 */
class WebDashboard
{
    /**
     * Currency converter instance
     */
    protected CurrencyConverter $converter;

    /**
     * Request data
     */
    protected array $request;

    /**
     * Response messages
     */
    protected array $messages = [];

    /**
     * Constructor
     *
     * @param CurrencyConverter|null $converter Optional converter instance
     */
    public function __construct(?CurrencyConverter $converter = null)
    {
        $this->converter = $converter ?? new CurrencyConverter();
        $this->request = array_merge($_GET, $_POST);
    }

    /**
     * Run the dashboard
     *
     * @return void
     */
    public function run(): void
    {
        // Handle form submission
        if ($this->request) {
            $this->handleRequest();
        }

        // Render the dashboard
        $this->render();
    }

    /**
     * Handle HTTP request
     *
     * @return void
     */
    protected function handleRequest(): void
    {
        $action = $this->request['action'] ?? '';

        switch ($action) {
            case 'convert':
                $this->handleConvert();
                break;
            case 'rate':
                $this->handleRate();
                break;
            default:
                // No action specified
                break;
        }
    }

    /**
     * Handle convert action
     *
     * @return void
     */
    protected function handleConvert(): void
    {
        $amount = isset($this->request['amount']) ? (float)$this->request['amount'] : 0;
        $from = CurrencyHelper::normalizeCurrencyCode($this->request['from'] ?? '');
        $to = CurrencyHelper::normalizeCurrencyCode($this->request['to'] ?? '');

        try {
            if ($amount <= 0) {
                throw new InvalidCurrencyException('Amount must be greater than zero');
            }

            if (!CurrencyHelper::isValidCurrencyCode($from) || !CurrencyHelper::isValidCurrencyCode($to)) {
                throw new InvalidCurrencyException('Invalid currency code. Must be 3 letters.');
            }

            $result = $this->converter->convert($amount, $from, $to);

            $this->messages[] = [
                'type' => 'success',
                'content' => sprintf(
                    '<strong>%s %s</strong> = <strong>%s %s</strong> (Rate: %s)%s',
                    CurrencyHelper::formatAmount($result->amount),
                    $result->from,
                    CurrencyHelper::formatAmount($result->result),
                    $result->to,
                    CurrencyHelper::formatAmount($result->rate, 6),
                    $result->fromCache ? ' <em>(from cache)</em>' : ''
                )
            ];
        } catch (InvalidCurrencyException $e) {
            $this->messages[] = [
                'type' => 'error',
                'content' => 'Invalid input: ' . $e->getMessage()
            ];
        } catch (CurrencyException $e) {
            $this->messages[] = [
                'type' => 'error',
                'content' => 'Conversion error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Handle rate action
     *
     * @return void
     */
    protected function handleRate(): void
    {
        $from = CurrencyHelper::normalizeCurrencyCode($this->request['from'] ?? '');
        $to = CurrencyHelper::normalizeCurrencyCode($this->request['to'] ?? '');

        try {
            if (!CurrencyHelper::isValidCurrencyCode($from) || !CurrencyHelper::isValidCurrencyCode($to)) {
                throw new InvalidCurrencyException('Invalid currency code. Must be 3 letters.');
            }

            $rate = $this->converter->getRate($from, $to);

            $this->messages[] = [
                'type' => 'success',
                'content' => sprintf(
                    '<strong>1 %s</strong> = <strong>%s %s</strong>',
                    $from,
                    CurrencyHelper::formatAmount($rate, 6),
                    $to
                )
            ];
        } catch (InvalidCurrencyException $e) {
            $this->messages[] = [
                'type' => 'error',
                'content' => 'Invalid currency code: ' . $e->getMessage()
            ];
        } catch (CurrencyException $e) {
            $this->messages[] = [
                'type' => 'error',
                'content' => 'Rate error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Render the dashboard HTML
     *
     * @return void
     */
    protected function render(): void
    {
        $currencies = $this->converter->getSupportedCurrencies();
        $defaultFrom = CurrencyConfig::$defaultFrom;
        $defaultTo = CurrencyConfig::$defaultTo;

        header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Currency Converter Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5rem;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #555; font-weight: 600; }
        input[type="number"], select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input[type="number"]:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .message.success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .message.error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .info-box p { color: #666; margin-bottom: 10px; }
        .currency-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
            gap: 8px;
            margin-top: 10px;
        }
        .currency-item {
            background: #667eea;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
        }
        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
            h1 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💱 Currency Converter</h1>

        <div class="card">
            <?php foreach ($this->messages as $msg): ?>
                <div class="message <?= $msg['type'] ?>">
                    <?= $msg['content'] ?>
                </div>
            <?php endforeach; ?>

            <form method="post">
                <input type="hidden" name="action" value="convert">
                <div class="form-group">
                    <label for="amount">Amount</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0.01" 
                           value="<?= htmlspecialchars($this->request['amount'] ?? '100') ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="from">From</label>
                        <select id="from" name="from" required>
                            <?php foreach ($currencies as $currency): ?>
                                <option value="<?= $currency ?>" <?= ($currency === ($this->request['from'] ?? $defaultFrom)) ? 'selected' : '' ?>>
                                    <?= $currency ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="to">To</label>
                        <select id="to" name="to" required>
                            <?php foreach ($currencies as $currency): ?>
                                <option value="<?= $currency ?>" <?= ($currency === ($this->request['to'] ?? $defaultTo)) ? 'selected' : '' ?>>
                                    <?= $currency ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit">🔄 Convert</button>
            </form>
        </div>

        <div class="card">
            <h2>ℹ️ Information</h2>
            <div class="info-box">
                <p><strong>Provider:</strong> <?= ucfirst(CurrencyConfig::$provider) ?></p>
                <p><strong>Cache TTL:</strong> <?= CurrencyConfig::$cacheTtl ?> seconds</p>
                <p><strong>Supported Currencies:</strong> <?= count($currencies) ?></p>
                <details style="margin-top: 15px;">
                    <summary style="cursor: pointer; color: #667eea; font-weight: 600;">View All Currencies</summary>
                    <div class="currency-list">
                        <?php foreach ($currencies as $currency): ?>
                            <span class="currency-item"><?= $currency ?></span>
                        <?php endforeach; ?>
                    </div>
                </details>
            </div>
        </div>

        <div class="card">
            <h2>📊 Quick Rate Check</h2>
            <form method="post">
                <input type="hidden" name="action" value="rate">
                <div class="form-row">
                    <div class="form-group">
                        <label for="rate_from">From</label>
                        <select id="rate_from" name="from" required>
                            <?php foreach ($currencies as $currency): ?>
                                <option value="<?= $currency ?>" <?= ($currency === $defaultFrom) ? 'selected' : '' ?>>
                                    <?= $currency ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="rate_to">To</label>
                        <select id="rate_to" name="to" required>
                            <?php foreach ($currencies as $currency): ?>
                                <option value="<?= $currency ?>" <?= ($currency === $defaultTo) ? 'selected' : '' ?>>
                                    <?= $currency ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit">📈 Get Rate</button>
            </form>
        </div>
    </div>
</body>
</html>
<?php
    }
}
