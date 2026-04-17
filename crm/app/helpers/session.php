<?php
declare(strict_types=1);

if (!function_exists('session_start_if_needed')) {
    function session_start_if_needed(): void {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        $cfg = require dirname(__DIR__, 2) . '/config/app.php';
        session_name($cfg['session_name']);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => ($cfg['env'] === 'production'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

if (!function_exists('session_put')) {
    function session_put(string $key, $value): void {
        session_start_if_needed();
        $_SESSION[$key] = $value;
    }
}

if (!function_exists('session_get')) {
    function session_get(string $key, $default = null) {
        session_start_if_needed();
        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('session_forget')) {
    function session_forget(string $key): void {
        session_start_if_needed();
        unset($_SESSION[$key]);
    }
}

if (!function_exists('session_regenerate')) {
    function session_regenerate(): void {
        session_start_if_needed();
        session_regenerate_id(true);
    }
}

if (!function_exists('session_destroy_all')) {
    function session_destroy_all(): void {
        session_start_if_needed();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
