<?php
declare(strict_types=1);

/**
 * Minimal .env loader. Looks for crm/.env then infra/env/crm.env.example as fallback.
 */
if (!function_exists('crm_load_env')) {
    function crm_load_env(string $path): array {
        if (!is_readable($path)) return [];
        $out = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (!str_contains($line, '=')) continue;
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            if (strlen($v) >= 2 && (
                ($v[0] === '"' && $v[-1] === '"') ||
                ($v[0] === "'" && $v[-1] === "'")
            )) {
                $v = substr($v, 1, -1);
            }
            $out[$k] = $v;
            if (getenv($k) === false) {
                putenv("$k=$v");
                $_ENV[$k] = $v;
            }
        }
        return $out;
    }
}

if (!function_exists('crm_env')) {
    function crm_env(string $key, $default = null) {
        $v = getenv($key);
        if ($v === false) $v = $_ENV[$key] ?? null;
        if ($v === null || $v === '') return $default;
        return $v;
    }
}

$__crm_base = dirname(__DIR__);
crm_load_env($__crm_base . '/.env');
crm_load_env(dirname($__crm_base) . '/infra/env/crm.env.example');
