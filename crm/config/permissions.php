<?php
declare(strict_types=1);

/**
 * Permission matrix (frontend side). `superadmin` is implicitly granted everything.
 * Keep in sync with API-side authorizeRoles for the same routes.
 */
return [
    'dashboard.view'          => ['superadmin', 'support', 'commercial', 'finance', 'developer'],
    'institutions.view'       => ['superadmin', 'support', 'commercial', 'finance', 'developer'],
    'institutions.create'     => ['superadmin', 'commercial'],
    'institutions.update'     => ['superadmin', 'support', 'commercial'],
    'institutions.change_status' => ['superadmin', 'support', 'commercial'],
    'audit.view'              => ['superadmin', 'support', 'developer'],
    'users_crm.manage'        => ['superadmin'],
    'settings.manage'         => ['superadmin'],
];
