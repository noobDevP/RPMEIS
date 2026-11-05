<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Match the origin rewrite, including its loopback exception for local development.
if (empty($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) === 'off') {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (!preg_match('/\A(?:localhost|127\.0\.0\.1|\[::1\])(?::[0-9]+)?\z/i', $host)) {
        $target = $_SERVER['REQUEST_URI'] ?? '/';
        if (!str_starts_with($target, '/') || preg_match('/[\x00-\x20\x7f]/', $target)) {
            $target = '/';
        }

        header_remove('X-Powered-By');
        header('Location: https://lawngreen-lyrebird-493319.hostingersite.com'.$target, true, 301);
        header("Content-Security-Policy: default-src 'none'; script-src 'none'; style-src 'none'; frame-ancestors 'none'; form-action 'none'; base-uri 'none'");
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: 0');
        exit;
    }
}

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
