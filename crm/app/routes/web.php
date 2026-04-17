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

    ['GET',  '^/dashboard$',                      fn() => require "$MODULES/dashboard/index.php"],

    ['GET',  '^/institutions$',                   fn() => require "$MODULES/institutions/list.php"],
    ['GET',  '^/institutions/new$',               fn() => require "$MODULES/institutions/create.php"],
    ['POST', '^/institutions/new$',               fn() => require "$MODULES/institutions/create.php"],
    ['GET',  '^/institutions/(?P<id>\d+)$',       fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/institutions/detail.php"; })($m['id'])],
    ['GET',  '^/institutions/(?P<id>\d+)/edit$',  fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/institutions/edit.php"; })($m['id'])],
    ['POST', '^/institutions/(?P<id>\d+)/edit$',  fn($m) => (function($id) use ($MODULES) { $_GET['id'] = $id; require "$MODULES/institutions/edit.php"; })($m['id'])],
    ['POST', '^/institutions/(?P<id>\d+)/status$',fn($m) => (function($id) use ($MODULES) { $_POST['id'] = $id; require "$MODULES/institutions/change_status.php"; })($m['id'])],
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
