<?php
declare(strict_types=1);

/**
 * Sidebar navigation definition. `roles` null = any authenticated role.
 * Icon names map to inline SVG blocks in components/sidebar.php.
 */
return [
    [
        'section' => 'Principal',
        'items' => [
            ['label' => 'Dashboard',     'route' => '/dashboard',     'icon' => 'grid',        'match' => '^/dashboard'],
            ['label' => 'Instituciones', 'route' => '/institutions',  'icon' => 'building',    'match' => '^/institutions'],
        ],
    ],
    [
        'section' => 'Administración',
        'items' => [
            ['label' => 'Auditoría',     'route' => '/audit',         'icon' => 'shield',      'match' => '^/audit',         'roles' => ['superadmin','support','developer'], 'soon' => true],
            ['label' => 'Planes',        'route' => '#',              'icon' => 'tag',         'soon' => true],
            ['label' => 'Módulos',       'route' => '#',              'icon' => 'layers',      'soon' => true],
            ['label' => 'Suscripciones', 'route' => '#',              'icon' => 'repeat',      'soon' => true],
            ['label' => 'Pagos',         'route' => '#',              'icon' => 'credit-card', 'soon' => true],
        ],
    ],
    [
        'section' => 'Configuración',
        'items' => [
            ['label' => 'Usuarios CRM',  'route' => '#',              'icon' => 'users',       'soon' => true],
            ['label' => 'Ajustes',       'route' => '#',              'icon' => 'cog',         'soon' => true],
        ],
    ],
];
