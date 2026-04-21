<?php
declare(strict_types=1);

/**
 * Lightweight frontend router. Maps paths to module controllers (plain PHP files).
 */

$BASE = dirname(__DIR__, 2);
$MODULES = dirname(__DIR__) . '/modules';

$path = current_path();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/** @var array<int, array{string, string, callable}> */
$routes = [
    // method, pattern (regex without delimiters), handler
    ['GET',  '^/$',                               fn() => redirect(auth_check() ? '/dashboard' : '/login')],

    ['GET',  '^/login$',                          fn() => require "$MODULES/auth/login.php"],
    ['POST', '^/login$',                          fn() => require "$MODULES/auth/login.php"],
    ['GET',  '^/logout$',                         fn() => require "$MODULES/auth/logout.php"],
    ['POST', '^/logout$',                         fn() => require "$MODULES/auth/logout.php"],
    ['GET',  '^/forgot-password$',                fn() => require "$MODULES/auth/forgot_password.php"],
    ['POST', '^/forgot-password$',                fn() => require "$MODULES/auth/forgot_password.php"],
    ['GET',  '^/reset-password$',                 fn() => require "$MODULES/auth/reset_password.php"],
    ['POST', '^/reset-password$',                 fn() => require "$MODULES/auth/reset_password.php"],
    ['GET',  '^/auth/google$',                    fn() => require "$MODULES/auth/google_start.php"],
    ['GET',  '^/auth/google/callback$',           fn() => require "$MODULES/auth/google_callback.php"],

    // Public contact / purchase request (no auth)
    ['GET',  '^/contact$',                        fn() => require "$MODULES/public/contact.php"],
    ['POST', '^/contact$',                        fn() => require "$MODULES/public/contact.php"],

    // Leads (internal)
    ['GET',  '^/leads$',                          fn() => require "$MODULES/leads/list.php"],
    ['GET',  '^/leads/(?P<id>\d+)$',              fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/leads/detail.php"; })($m['id'])],
    ['GET',  '^/leads/(?P<id>\d+)/convert$',      fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/leads/convert.php"; })($m['id'])],
    ['POST', '^/leads/(?P<id>\d+)/convert$',      fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/leads/convert.php"; })($m['id'])],
    ['POST', '^/leads/(?P<id>\d+)/status$',       fn($m) => (function($id) use ($MODULES) { $_POST['id'] = $id; require "$MODULES/leads/change_status.php"; })($m['id'])],
    ['POST', '^/leads/(?P<id>\d+)/assign$',       fn($m) => (function($id) use ($MODULES) { $_POST['id'] = $id; require "$MODULES/leads/assign.php"; })($m['id'])],

    ['GET',  '^/dashboard$',                      fn() => require "$MODULES/dashboard/index.php"],

    ['GET',  '^/institutions$',                   fn() => require "$MODULES/institutions/list.php"],
    ['GET',  '^/institutions/export\.csv$',       fn() => require "$MODULES/institutions/export_csv.php"],
    ['GET',  '^/institutions/new$',               fn() => require "$MODULES/institutions/create.php"],
    ['POST', '^/institutions/new$',               fn() => require "$MODULES/institutions/create.php"],
    ['GET',  '^/institutions/(?P<id>\d+)$',       fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/institutions/detail.php"; })($m['id'])],
    ['GET',  '^/institutions/(?P<id>\d+)/edit$',  fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/institutions/edit.php"; })($m['id'])],
    ['POST', '^/institutions/(?P<id>\d+)/edit$',  fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/institutions/edit.php"; })($m['id'])],
    ['POST', '^/institutions/(?P<id>\d+)/status$',fn($m) => (function($id) use ($MODULES) { $_POST['id'] = $id; require "$MODULES/institutions/change_status.php"; })($m['id'])],
    ['POST', '^/institutions/(?P<id>\d+)/modules-overrides$', fn($m) => (function($id) use ($MODULES) { $_POST['id'] = $id; require "$MODULES/institutions/modules_overrides.php"; })($m['id'])],

    // Plans
    ['GET',  '^/plans$',                          fn() => require "$MODULES/plans/list.php"],
    ['GET',  '^/plans/new$',                      fn() => require "$MODULES/plans/create.php"],
    ['POST', '^/plans/new$',                      fn() => require "$MODULES/plans/create.php"],
    ['GET',  '^/plans/(?P<id>\d+)$',              fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/plans/detail.php"; })($m['id'])],
    ['GET',  '^/plans/(?P<id>\d+)/edit$',         fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/plans/edit.php"; })($m['id'])],
    ['POST', '^/plans/(?P<id>\d+)/edit$',         fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/plans/edit.php"; })($m['id'])],

    // Modules catalog
    ['GET',  '^/modules$',                        fn() => require "$MODULES/modules_catalog/list.php"],
    ['GET',  '^/modules/new$',                    fn() => require "$MODULES/modules_catalog/create.php"],
    ['POST', '^/modules/new$',                    fn() => require "$MODULES/modules_catalog/create.php"],
    ['GET',  '^/modules/(?P<id>\d+)$',            fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/modules_catalog/detail.php"; })($m['id'])],
    ['GET',  '^/modules/(?P<id>\d+)/edit$',       fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/modules_catalog/edit.php"; })($m['id'])],
    ['POST', '^/modules/(?P<id>\d+)/edit$',       fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/modules_catalog/edit.php"; })($m['id'])],

    // Plan × Modules matrix
    ['GET',  '^/plan-matrix$',                    fn() => require "$MODULES/plan_modules/matrix.php"],
    ['POST', '^/plan-matrix$',                    fn() => require "$MODULES/plan_modules/matrix.php"],

    // Subscriptions
    ['GET',  '^/subscriptions$',                  fn() => require "$MODULES/subscriptions/list.php"],
    ['GET',  '^/subscriptions/new$',              fn() => require "$MODULES/subscriptions/create.php"],
    ['POST', '^/subscriptions/new$',              fn() => require "$MODULES/subscriptions/create.php"],
    ['GET',  '^/subscriptions/(?P<id>\d+)$',      fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/subscriptions/detail.php"; })($m['id'])],
    ['GET',  '^/subscriptions/(?P<id>\d+)/edit$', fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/subscriptions/edit.php"; })($m['id'])],
    ['POST', '^/subscriptions/(?P<id>\d+)/edit$', fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/subscriptions/edit.php"; })($m['id'])],
    ['POST', '^/subscriptions/(?P<id>\d+)/status$', fn($m) => (function($id) use ($MODULES) { $_POST['id'] = $id; require "$MODULES/subscriptions/change_status.php"; })($m['id'])],

    // Payments
    ['GET',  '^/payments$',                       fn() => require "$MODULES/payments/list.php"],
    ['GET',  '^/payments/export\.csv$',           fn() => require "$MODULES/payments/export_csv.php"],
    ['GET',  '^/payments/new$',                   fn() => require "$MODULES/payments/create.php"],
    ['POST', '^/payments/new$',                   fn() => require "$MODULES/payments/create.php"],
    ['GET',  '^/payments/(?P<id>\d+)$',           fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/payments/detail.php"; })($m['id'])],
    ['GET',  '^/payments/(?P<id>\d+)/edit$',      fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/payments/edit.php"; })($m['id'])],
    ['POST', '^/payments/(?P<id>\d+)/edit$',      fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/payments/edit.php"; })($m['id'])],
    ['POST', '^/payments/(?P<id>\d+)/status$',    fn($m) => (function($id) use ($MODULES) { $_POST['id'] = $id; require "$MODULES/payments/change_status.php"; })($m['id'])],

    // Audit
    ['GET',  '^/audit$',                          fn() => require "$MODULES/audit/list.php"],
    ['GET',  '^/audit/export\.csv$',              fn() => require "$MODULES/audit/export_csv.php"],
    ['GET',  '^/audit/(?P<id>\d+)$',              fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/audit/detail.php"; })($m['id'])],
];

foreach ($routes as [$m, $pat, $fn]) {
    if ($m !== $method) continue;
    if (preg_match('#' . $pat . '#', $path, $match)) {
        $fn($match);
        return;
    }
}

http_response_code(404);
$title = 'Página no encontrada';
$code = 404;
$message = 'La página que estás buscando no existe o fue movida.';
include dirname(__DIR__) . '/layouts/error.php';
