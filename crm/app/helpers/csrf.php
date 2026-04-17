<?php
declare(strict_types=1);

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        session_start_if_needed();
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(): bool {
        session_start_if_needed();
        $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $ok = !empty($_SESSION['_csrf']) && is_string($sent) && hash_equals($_SESSION['_csrf'], $sent);
        if (!$ok) {
            http_response_code(419);
            header('Content-Type: text/html; charset=utf-8');
            echo '<h1>419 CSRF token inválido</h1><p>Por favor recargá la página e intentá nuevamente.</p>';
            exit;
        }
        return true;
    }
}
