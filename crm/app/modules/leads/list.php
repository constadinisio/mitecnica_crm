<?php
declare(strict_types=1);

require_auth();
require_permission('leads.view');

$query = [];
foreach (['search', 'status', 'assigned_to', 'sort', 'order', 'page', 'limit'] as $k) {
    if (isset($_GET[$k]) && $_GET[$k] !== '') $query[$k] = $_GET[$k];
}
$query['limit'] = $query['limit'] ?? 15;

$leads = [];
$pagination = ['page' => 1, 'limit' => 15, 'total' => 0, 'pages' => 0];
$summary = null;
try {
    $res = api_get('/leads', ['query' => $query]);
    $leads = $res['data'] ?? [];
    $pagination = $res['meta']['pagination'] ?? $pagination;
    $summary = api_get('/leads/summary')['data'] ?? null;
} catch (ApiClientException $e) {
    flash_set('error', 'No se pudieron cargar las solicitudes: ' . $e->getMessage());
}

ob_start();
?>
<?php
  $title = 'Solicitudes';
  $subtitle = 'Pedidos entrantes desde el formulario público. Convertí a cliente cuando el pago esté confirmado.';
  $breadcrumbs = [['label' => 'Dashboard', 'href' => '/dashboard'], ['label' => 'Solicitudes']];
  $actionsHtml = '<a href="/contact" target="_blank" class="btn-ghost inline-flex">Ver formulario público ↗</a>';
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>

<?php if ($summary): ?>
<section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-4">
  <?php
  $blocks = [
    ['key' => 'new',            'label' => 'Nuevas',         'class' => 'bg-brand-500/10 text-brand-200 border-brand-500/30'],
    ['key' => 'contacted',      'label' => 'Contactadas',    'class' => 'bg-amber-500/10 text-amber-200 border-amber-500/30'],
    ['key' => 'in_negotiation', 'label' => 'En negociación', 'class' => 'bg-amber-500/10 text-amber-200 border-amber-500/30'],
    ['key' => 'converted',      'label' => 'Convertidas',    'class' => 'bg-emerald-500/10 text-emerald-200 border-emerald-500/30'],
    ['key' => 'lost',           'label' => 'Perdidas',       'class' => 'bg-slate-500/10 text-slate-200 border-slate-500/30'],
  ];
  foreach ($blocks as $b):
  ?>
    <a href="/leads?status=<?= e($b['key']) ?>" class="rounded-xl border <?= e($b['class']) ?> px-4 py-3 hover:opacity-90 transition">
      <div class="text-[10px] uppercase tracking-wider opacity-80"><?= e($b['label']) ?></div>
      <div class="mt-0.5 text-xl font-semibold tabular-nums"><?= (int)($summary['counts']['by_status'][$b['key']] ?? 0) ?></div>
    </a>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<?php include __DIR__ . '/partials/filters.php'; ?>
<?php include __DIR__ . '/partials/table.php'; ?>

<div class="mt-4">
  <?php $baseUrl = '/leads'; $currentQuery = $query; unset($currentQuery['page']); include dirname(__DIR__, 2) . '/components/pagination.php'; ?>
</div>

<?php $content = ob_get_clean(); $title = 'Solicitudes'; include dirname(__DIR__, 2) . '/layouts/main.php';
