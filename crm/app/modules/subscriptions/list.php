<?php
declare(strict_types=1);

require_auth();
require_permission('subscriptions.view');

$query = [];
foreach (['search', 'status', 'institution_id', 'plan_id', 'renewal_mode', 'sort', 'order', 'page', 'limit'] as $k) {
    if (isset($_GET[$k]) && $_GET[$k] !== '') $query[$k] = $_GET[$k];
}
$query['limit'] = $query['limit'] ?? 15;

$subs = [];
$pagination = ['page' => 1, 'limit' => 15, 'total' => 0, 'pages' => 0];
try {
    $res = api_get('/subscriptions', ['query' => $query]);
    $subs = $res['data'] ?? [];
    $pagination = $res['meta']['pagination'] ?? $pagination;
} catch (ApiClientException $e) {
    flash_set('error', 'No se pudieron cargar las suscripciones: ' . $e->getMessage());
}

ob_start();
?>
<?php
  $title = 'Suscripciones';
  $subtitle = 'Relación comercial entre instituciones y planes.';
  $breadcrumbs = [['label' => 'Dashboard', 'href' => '/dashboard'], ['label' => 'Suscripciones']];
  $actionsHtml = can('subscriptions.create') ? '<a href="/subscriptions/new" class="btn-primary inline-flex">+ Nueva suscripción</a>' : '';
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>
<?php include __DIR__ . '/partials/filters.php'; ?>
<?php include __DIR__ . '/partials/table.php'; ?>
<div class="mt-4">
  <?php $baseUrl = '/subscriptions'; $currentQuery = $query; unset($currentQuery['page']); include dirname(__DIR__, 2) . '/components/pagination.php'; ?>
</div>
<?php $content = ob_get_clean(); $title = 'Suscripciones'; include dirname(__DIR__, 2) . '/layouts/main.php';
