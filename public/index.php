<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Override Docker environment variables before Laravel loads
$_ENV['DB_HOST'] = $_SERVER['DB_HOST'] ?? '172.19.0.1';
putenv('DB_HOST=' . $_ENV['DB_HOST']);

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
