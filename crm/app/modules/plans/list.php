<?php
declare(strict_types=1);

require_auth();
require_permission('plans.view');

$query = [];
foreach (['search', 'status', 'billing_frequency', 'sort', 'order', 'page', 'limit'] as $k) {
    if (isset($_GET[$k]) && $_GET[$k] !== '') $query[$k] = $_GET[$k];
}
$query['limit'] = $query['limit'] ?? 15;

$plans = [];
$pagination = ['page' => 1, 'limit' => 15, 'total' => 0, 'pages' => 0];
try {
    $res = api_get('/plans', ['query' => $query]);
    $plans = $res['data'] ?? [];
    $pagination = $res['meta']['pagination'] ?? $pagination;
} catch (ApiClientException $e) {
    flash_set('error', 'No se pudieron cargar los planes: ' . $e->getMessage());
}

ob_start();
?>
<?php
  $title = 'Planes';
  $subtitle = 'Catálogo comercial de planes del producto.';
  $breadcrumbs = [['label' => 'Dashboard', 'href' => '/dashboard'], ['label' => 'Planes']];
  $actionsHtml = '';
  if (can('plans.create')) {
      $actionsHtml .= '<a href="/plans/new" class="btn-primary inline-flex">+ Nuevo plan</a>';
  }
  $actionsHtml .= '<a href="/plan-matrix" class="btn-secondary inline-flex">Matriz Planes × Módulos</a>';
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>

<?php include __DIR__ . '/partials/filters.php'; ?>
<?php include __DIR__ . '/partials/table.php'; ?>

<div class="mt-4">
  <?php
    $baseUrl = '/plans';
    $currentQuery = $query;
    unset($currentQuery['page']);
    include dirname(__DIR__, 2) . '/components/pagination.php';
  ?>
</div>
<?php
$content = ob_get_clean();
$title = 'Planes';
include dirname(__DIR__, 2) . '/layouts/main.php';
