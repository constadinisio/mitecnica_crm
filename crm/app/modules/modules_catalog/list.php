<?php
declare(strict_types=1);

require_auth();
require_permission('modules.view');

$query = [];
foreach (['search', 'status', 'category', 'is_core', 'sort', 'order', 'page', 'limit'] as $k) {
    if (isset($_GET[$k]) && $_GET[$k] !== '') $query[$k] = $_GET[$k];
}
$query['limit'] = $query['limit'] ?? 20;

$modules = [];
$pagination = ['page' => 1, 'limit' => 20, 'total' => 0, 'pages' => 0];
try {
    $res = api_get('/modules-catalog', ['query' => $query]);
    $modules = $res['data'] ?? [];
    $pagination = $res['meta']['pagination'] ?? $pagination;
} catch (ApiClientException $e) {
    flash_set('error', 'No se pudieron cargar los módulos: ' . $e->getMessage());
}

ob_start();
?>
<?php
  $title = 'Catálogo de módulos';
  $subtitle = 'Funcionalidades que componen los planes del producto.';
  $breadcrumbs = [['label' => 'Dashboard', 'href' => '/dashboard'], ['label' => 'Módulos']];
  $actionsHtml = '';
  if (can('modules.create')) {
      $actionsHtml .= '<a href="/modules/new" class="btn-primary inline-flex">+ Nuevo módulo</a>';
  }
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>
<?php include __DIR__ . '/partials/filters.php'; ?>
<?php include __DIR__ . '/partials/table.php'; ?>
<div class="mt-4">
  <?php $baseUrl = '/modules'; $currentQuery = $query; unset($currentQuery['page']); include dirname(__DIR__, 2) . '/components/pagination.php'; ?>
</div>
<?php $content = ob_get_clean(); $title = 'Módulos'; include dirname(__DIR__, 2) . '/layouts/main.php';
