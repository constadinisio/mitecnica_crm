<?php
declare(strict_types=1);

require_auth();
require_permission('exports.payments');

$allowed = ['search', 'status', 'payment_method', 'institution_id', 'subscription_id', 'from', 'to', 'sort', 'order'];
$query = [];
foreach ($allowed as $k) {
    if (isset($_GET[$k]) && $_GET[$k] !== '') $query[$k] = $_GET[$k];
}

api_stream('/payments/export.csv', $query, 'text/csv; charset=utf-8');
