<?php
declare(strict_types=1);

require_auth();
require_permission('institutions.view');

$query = [];
$allowedFilters = ['search', 'status', 'technical_status', 'plan', 'sort', 'order', 'page', 'limit'];
foreach ($allowedFilters as $k) {
    if (isset($_GET[$k]) && $_GET[$k] !== '') $query[$k] = $_GET[$k];
}
$query['limit'] = $query['limit'] ?? 15;

$institutions = [];
$pagination = ['page' => 1, 'limit' => 15, 'total' => 0, 'pages' => 0];

try {
    $res = api_get('/institutions', ['query' => $query]);
    $institutions = $res['data'] ?? [];
    $pagination = $res['meta']['pagination'] ?? $pagination;
} catch (ApiClientException $e) {
    flash_set('error', 'No se pudieron cargar las instituciones: ' . $e->getMessage());
}

ob_start();
?>

<?php
  $title = 'Instituciones';
  $subtitle = 'Gestión comercial y técnica de clientes activos y potenciales.';
  $breadcrumbs = [['label' => 'Dashboard', 'href' => '/dashboard'], ['label' => 'Instituciones']];
  $actionsHtml = '';
  if (can('institutions.create')) {
      $actionsHtml .= '<a href="/institutions/new" class="btn-primary inline-flex">+ Nueva institución</a>';
  }
  $actionsHtml .= '<button type="button" class="btn-secondary inline-flex opacity-60 cursor-not-allowed" title="Disponible en la próxima fase" disabled>Exportar reporte</button>';
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>

<?php include __DIR__ . '/partials/filters.php'; ?>
<?php include __DIR__ . '/partials/table.php'; ?>

<div class="mt-4">
  <?php
    $baseUrl = '/institutions';
    $currentQuery = $query;
    unset($currentQuery['page']);
    include dirname(__DIR__, 2) . '/components/pagination.php';
  ?>
</div>

<?php
$content = ob_get_clean();
$title = 'Instituciones';
$extraScripts = ['/assets/js/institutions.js'];
include dirname(__DIR__, 2) . '/layouts/main.php';
