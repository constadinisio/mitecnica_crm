<?php
declare(strict_types=1);

if (!function_exists('current_user')) {
    function current_user(): ?array {
        return session_get('user');
    }
}

if (!function_exists('current_role_key')) {
    function current_role_key(): ?string {
        $u = current_user();
        return $u['role']['key'] ?? null;
    }
}

if (!function_exists('can')) {
    function can(string $permission): bool {
        $matrix = require dirname(__DIR__, 2) . '/config/permissions.php';
        $role = current_role_key();
        if (!$role) return false;
        if ($role === 'superadmin') return true;
        $allowed = $matrix[$permission] ?? [];
        return in_array($role, $allowed, true);
    }
}

if (!function_exists('require_permission')) {
    function require_permission(string $permission): void {
        if (can($permission)) return;
        http_response_code(403);
        $layoutVars = [
            'title'   => 'Acceso denegado',
            'code'    => 403,
            'message' => 'No tenés permisos para acceder a esta sección.',
        ];
        extract($layoutVars);
        include dirname(__DIR__) . '/layouts/error.php';
        exit;
    }
}
