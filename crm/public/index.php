<?php
declare(strict_types=1);

/**
 * Entry point of the Mi Tecnica CRM frontend.
 * Bootstraps config, helpers, session and dispatches the router.
 */

error_reporting(E_ALL);

$BASE = dirname(__DIR__);

require $BASE . '/config/env.php';
require $BASE . '/config/app.php';   // returns array but also loaded via autoload — safe.

require $BASE . '/app/helpers/url.php';
require $BASE . '/app/helpers/session.php';
require $BASE . '/app/helpers/csrf.php';
require $BASE . '/app/helpers/flash.php';
require $BASE . '/app/helpers/format.php';
require $BASE . '/app/helpers/api_client.php';
require $BASE . '/app/helpers/permissions.php';
require $BASE . '/app/helpers/auth.php';

session_start_if_needed();

$appConfig = require $BASE . '/config/app.php';
if (($appConfig['env'] ?? 'development') === 'production') {
    ini_set('display_errors', '0');
} else {
    ini_set('display_errors', '1');
}

require $BASE . '/app/routes/web.php';
