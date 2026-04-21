<?php
declare(strict_types=1);

/**
 * Permission matrix (frontend side). `superadmin` is implicitly granted everything.
 * Keep in sync with API-side authorizeRoles.
 */
return [
    'dashboard.view'             => ['superadmin', 'support', 'commercial', 'finance', 'developer'],

    'leads.view'                 => ['superadmin', 'support', 'commercial', 'finance'],
    'leads.change_status'        => ['superadmin', 'commercial', 'support'],
    'leads.assign'               => ['superadmin', 'commercial', 'support'],
    'leads.convert'              => ['superadmin', 'commercial'],

    'institutions.view'              => ['superadmin', 'support', 'commercial', 'finance', 'developer'],
    'institutions.create'            => ['superadmin', 'commercial'],
    'institutions.update'            => ['superadmin', 'support', 'commercial'],
    'institutions.change_status'     => ['superadmin', 'support', 'commercial'],
    'institutions.view_license'      => ['superadmin', 'support', 'commercial', 'finance', 'developer'],
    'institutions.override_modules'  => ['superadmin', 'commercial'],

    'plans.view'                 => ['superadmin', 'support', 'commercial', 'finance', 'developer'],
    'plans.create'               => ['superadmin', 'commercial'],
    'plans.update'               => ['superadmin', 'commercial'],
    'plans.change_status'        => ['superadmin', 'commercial'],

    'modules.view'               => ['superadmin', 'support', 'commercial', 'finance', 'developer'],
    'modules.create'             => ['superadmin', 'commercial'],
    'modules.update'             => ['superadmin', 'commercial'],
    'modules.change_status'      => ['superadmin', 'commercial'],

    'subscriptions.view'         => ['superadmin', 'support', 'commercial', 'finance'],
    'subscriptions.create'       => ['superadmin', 'commercial'],
    'subscriptions.update'       => ['superadmin', 'commercial'],
    'subscriptions.change_status'=> ['superadmin', 'commercial', 'support'],

    'payments.view'              => ['superadmin', 'support', 'commercial', 'finance'],
    'payments.create'            => ['superadmin', 'finance', 'commercial'],
    'payments.update'            => ['superadmin', 'finance', 'commercial'],
    'payments.change_status'     => ['superadmin', 'finance', 'commercial'],

    'audit.view'                 => ['superadmin', 'support', 'developer'],
    'users_crm.manage'           => ['superadmin'],
    'settings.manage'            => ['superadmin'],

    'exports.institutions'       => ['superadmin', 'support', 'commercial', 'finance', 'developer'],
    'exports.payments'           => ['superadmin', 'support', 'commercial', 'finance'],
    'exports.audit'              => ['superadmin', 'support', 'developer'],
];
