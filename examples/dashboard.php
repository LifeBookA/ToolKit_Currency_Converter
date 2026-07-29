#!/usr/bin/env php
<?php

/**
 * Currency Converter Web Dashboard Entry Point
 *
 * Native PHP web interface without external dependencies
 *
 * Usage:
 *   php -S localhost:8000 examples/dashboard.php
 *   Then open http://localhost:8000 in browser
 */

// Register autoloader directly
require_once __DIR__ . '/../src/Autoloader.php';
\Toolkit\Autoloader::register();

use Toolkit\Currency\Web\WebDashboard;

// Run the dashboard
$dashboard = new WebDashboard();
$dashboard->run();
