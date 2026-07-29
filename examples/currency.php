#!/usr/bin/env php
<?php

/**
 * Currency Converter CLI Entry Point
 *
 * Native PHP command-line interface without external dependencies
 *
 * Usage:
 *   php currency.php convert 100 USD EUR
 *   php currency.php rate GBP IRR
 *   php currency.php list
 *   php currency.php help
 */

// Register autoloader directly
require_once __DIR__ . '/../src/Autoloader.php';
\Toolkit\Autoloader::register();

use Toolkit\Currency\CLI\CurrencyCommand;

// Run the CLI command
$command = new CurrencyCommand();
$command->run();
