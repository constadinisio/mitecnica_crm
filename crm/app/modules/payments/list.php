<?php
declare(strict_types=1);

require_auth();
require_permission('payments.view');

$query = [];
foreach (['search', 'status', 'institution_id', 'subscription_id', 'from', 'to', 'payment_method', 'sort', 'order', 'page', 'limit'] as $k) {
    if (isset($_GET[$k]) && $_GET[$k] !== '') $query[$k] = $_GET[$k];
}
$query['limit'] = $query['limit'] ?? 15;

$payments = [];
$pagination = ['page' => 1, 'limit' => 15, 'total' => 0, 'pages' => 0];
try {
    $res = api_get('/payments', ['query' => $query]);
    $payments = $res['data'] ?? [];
    $pagination = $res['meta']['pagination'] ?? $pagination;
} catch (ApiClientException $e) {
    flash_set('error', 'No se pudieron cargar los pagos: ' . $e->getMessage());
}

ob_start();
?>
<?php
  $title = 'Pagos';
  $subtitle = 'Registro manual de pagos asociados a suscripciones o instituciones.';
  $breadcrumbs = [['label' => 'Dashboard', 'href' => '/dashboard'], ['label' => 'Pagos']];
  $actionsHtml = can('payments.create') ? '<a href="/payments/new" class="btn-primary inline-flex">+ Nuevo pago</a>' : '';
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>
<?php include __DIR__ . '/partials/filters.php'; ?>
<?php include __DIR__ . '/partials/table.php'; ?>
<div class="mt-4">
  <?php $baseUrl = '/payments'; $currentQuery = $query; unset($currentQuery['page']); include dirname(__DIR__, 2) . '/components/pagination.php'; ?>
</div>
<?php $content = ob_get_clean(); $title = 'Pagos'; include dirname(__DIR__, 2) . '/layouts/main.php';
