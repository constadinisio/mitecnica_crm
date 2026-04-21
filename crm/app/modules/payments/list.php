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
  $actionsHtml = '';
  if (can('exports.payments')) {
    $exportQuery = $query; unset($exportQuery['page'], $exportQuery['limit']);
    $exportHref = '/payments/export.csv' . ($exportQuery ? '?' . http_build_query($exportQuery) : '');
    $actionsHtml .= '<a href="' . e($exportHref) . '" class="btn-secondary inline-flex items-center gap-1.5 h-9 text-sm">'
      . '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>'
      . 'Exportar CSV</a>';
  }
  if (can('payments.create')) $actionsHtml .= '<a href="/payments/new" class="btn-primary h-9 text-sm inline-flex items-center">+ Nuevo pago</a>';
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>
<?php include __DIR__ . '/partials/filters.php'; ?>
<?php include __DIR__ . '/partials/table.php'; ?>
<div class="mt-4">
  <?php $baseUrl = '/payments'; $currentQuery = $query; unset($currentQuery['page']); include dirname(__DIR__, 2) . '/components/pagination.php'; ?>
</div>
<?php $content = ob_get_clean(); $title = 'Pagos'; include dirname(__DIR__, 2) . '/layouts/main.php';
