<?php
declare(strict_types=1);

/**
 * Sidebar navigation. `roles` null = any authenticated role (superadmin always allowed).
 * Icon names map to inline SVG blocks in components/sidebar.php.
 */
return [
    [
        'section' => 'Principal',
        'items' => [
            ['label' => 'Dashboard',     'route' => '/dashboard',    'icon' => 'grid',     'match' => '^/dashboard$'],
            ['label' => 'Solicitudes',   'route' => '/leads',        'icon' => 'shield',   'match' => '^/leads'],
            ['label' => 'Instituciones', 'route' => '/institutions', 'icon' => 'building', 'match' => '^/institutions'],
        ],
    ],
    [
        'section' => 'Comercial',
        'items' => [
            ['label' => 'Planes',          'route' => '/plans',         'icon' => 'tag',         'match' => '^/plans'],
            ['label' => 'Módulos',         'route' => '/modules',       'icon' => 'layers',      'match' => '^/modules'],
            ['label' => 'Matriz Planes',   'route' => '/plan-matrix',   'icon' => 'grid',        'match' => '^/plan-matrix'],
            ['label' => 'Suscripciones',   'route' => '/subscriptions', 'icon' => 'repeat',      'match' => '^/subscriptions'],
            ['label' => 'Pagos',           'route' => '/payments',      'icon' => 'credit-card', 'match' => '^/payments'],
        ],
    ],
    [
        'section' => 'Administración',
        'items' => [
            ['label' => 'Auditoría',       'route' => '/audit',         'icon' => 'shield',      'match' => '^/audit', 'roles' => ['superadmin','support','developer']],
            ['label' => 'Usuarios CRM',    'route' => '#',              'icon' => 'users',       'soon' => true, 'roles' => ['superadmin']],
            ['label' => 'Soporte',         'route' => '#',              'icon' => 'shield',      'soon' => true],
            ['label' => 'Ajustes',         'route' => '#',              'icon' => 'cog',         'soon' => true, 'roles' => ['superadmin']],
        ],
    ],
];
