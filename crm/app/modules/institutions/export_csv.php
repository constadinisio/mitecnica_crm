<?php
declare(strict_types=1);

require_auth();
require_permission('exports.institutions');

$allowed = ['search', 'status', 'technical_status', 'plan', 'expiration_from', 'expiration_to', 'sort', 'order'];
$query = [];
foreach ($allowed as $k) {
    if (isset($_GET[$k]) && $_GET[$k] !== '') $query[$k] = $_GET[$k];
}

api_stream('/institutions/export.csv', $query, 'text/csv; charset=utf-8');
