<?php

namespace Toolkit\Currency\Provider;

use Toolkit\Currency\Contracts\ExchangeRateProviderInterface;
use Toolkit\Currency\Config\CurrencyConfig;
use Toolkit\Currency\Exceptions\CurrencyException;

/**
 * Provider Factory
 * 
 * Creates appropriate exchange rate provider based on configuration
 * 
 * @package Toolkit\Currency\Provider
 */
class ProviderFactory
{
    /**
     * Create an exchange rate provider
     * 
     * @param string|null $type Provider type ('api', 'fixed', or 'ecb'), uses config if null
     * @param array $options Optional options for the provider
     * @return ExchangeRateProviderInterface
     * @throws CurrencyException If provider type is invalid
     */
    public static function create(?string $type = null, array $options = []): ExchangeRateProviderInterface
    {
        $type = $type ?? CurrencyConfig::getProvider();

        switch ($type) {
            case 'api':
                return new ExchangeRateHostProvider(
                    $options['apiUrl'] ?? null,
                    $options['apiKey'] ?? null,
                    $options['timeout'] ?? null
                );

            case 'fixed':
                return new FixedRateProvider(
                    $options['rates'] ?? null
                );
            
            case 'ecb':
                return new EuropeanCentralBankProvider(
                    $options['timeout'] ?? null,
                    $options['useAlternative'] ?? false
                );

            default:
                throw new CurrencyException("Invalid provider type: {$type}. Use 'api', 'fixed', or 'ecb'.");
        }
    }
}
