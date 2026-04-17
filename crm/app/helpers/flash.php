<?php
declare(strict_types=1);

if (!function_exists('flash_set')) {
    function flash_set(string $type, string $message): void {
        session_start_if_needed();
        $_SESSION['_flash'] = $_SESSION['_flash'] ?? [];
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('flash_pull')) {
    function flash_pull(): array {
        session_start_if_needed();
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }
}

if (!function_exists('old_set')) {
    function old_set(array $values): void {
        session_start_if_needed();
        $_SESSION['_old'] = $values;
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = ''): string {
        session_start_if_needed();
        $v = $_SESSION['_old'][$key] ?? $default;
        if (is_array($v) || is_object($v)) return '';
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('old_raw')) {
    function old_raw(string $key, $default = null) {
        session_start_if_needed();
        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (!function_exists('old_clear')) {
    function old_clear(): void {
        session_start_if_needed();
        unset($_SESSION['_old'], $_SESSION['_errors']);
    }
}

if (!function_exists('errors_set')) {
    function errors_set(array $errors): void {
        session_start_if_needed();
        $_SESSION['_errors'] = $errors;
    }
}

if (!function_exists('errors_all')) {
    function errors_all(): array {
        session_start_if_needed();
        return $_SESSION['_errors'] ?? [];
    }
}

if (!function_exists('errors_for')) {
    function errors_for(string $field): ?string {
        session_start_if_needed();
        foreach (($_SESSION['_errors'] ?? []) as $e) {
            if (($e['field'] ?? null) === $field) return $e['message'] ?? null;
        }
        return null;
    }
}
