<?php
declare(strict_types=1);

require_auth();
require_permission('exports.audit');

$allowed = ['search', 'action', 'entity', 'entity_id', 'actor_user_id', 'from', 'to', 'sort', 'order'];
$query = [];
foreach ($allowed as $k) {
    if (isset($_GET[$k]) && $_GET[$k] !== '') $query[$k] = $_GET[$k];
}

api_stream('/audit-logs/export.csv', $query, 'text/csv; charset=utf-8');
