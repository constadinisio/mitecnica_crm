<?php
declare(strict_types=1);

if (!function_exists('url')) {
    function url(string $path = '/'): string {
        $cfg = require dirname(__DIR__, 2) . '/config/app.php';
        if ($path === '' || $path === '/') return $cfg['url'] . '/';
        return $cfg['url'] . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string {
        return '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('current_path')) {
    function current_path(): string {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $q = strpos($uri, '?');
        return $q === false ? $uri : substr($uri, 0, $q);
    }
}

if (!function_exists('is_active_route')) {
    function is_active_route(?string $pattern): bool {
        if (!$pattern) return false;
        return (bool) preg_match('#' . $pattern . '#', current_path());
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path, int $status = 302): void {
        header('Location: ' . (str_starts_with($path, 'http') ? $path : $path), true, $status);
        exit;
    }
}

if (!function_exists('back')) {
    function back(string $fallback = '/'): void {
        redirect($_SERVER['HTTP_REFERER'] ?? $fallback);
    }
}
