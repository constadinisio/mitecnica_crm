<?php
declare(strict_types=1);

/**
 * Router for PHP built-in server (`php -S localhost:8080 -t public public/router.php`).
 * Serves static assets directly and routes everything else through index.php.
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$file = __DIR__ . $uri;

if ($uri !== '/' && file_exists($file) && is_file($file)) {
    return false; // let built-in server handle the static file
}

require __DIR__ . '/index.php';
