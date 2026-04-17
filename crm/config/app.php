<?php
declare(strict_types=1);

return [
    'name'          => 'Mi Tecnica CRM',
    'env'           => crm_env('APP_ENV', 'development'),
    'url'           => rtrim((string) crm_env('APP_URL', 'http://localhost:8080'), '/'),
    'api_base_url'  => rtrim((string) crm_env('API_BASE_URL', 'http://localhost:4000/api/v1'), '/'),
    'session_name'  => (string) crm_env('SESSION_NAME', 'mitecnica_crm_sess'),
    'google_oauth'  => filter_var((string) crm_env('GOOGLE_OAUTH_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN),
    'brand' => [
        'short'   => 'Mi Tecnica',
        'product' => 'CRM',
        'tagline' => 'Panel de administración',
    ],
    'paths' => [
        'base'   => dirname(__DIR__),
        'views'  => dirname(__DIR__) . '/app',
        'assets' => '/assets',
        'storage' => dirname(__DIR__) . '/storage',
    ],
];
