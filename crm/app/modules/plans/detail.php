<?php
declare(strict_types=1);

require_auth();
require_permission('plans.view');

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { http_response_code(404); echo 'Not found'; return; }

$plan = null;
$modules = [];
$catalog = [];
try {
    $resP = api_get("/plans/$id");
    $plan = $resP['data'] ?? null;
    $resM = api_get("/plans/$id/modules");
    $modules = $resM['data']['module_ids'] ?? [];
    $resC = api_get('/modules-catalog', ['query' => ['limit' => 200, 'status' => 'active']]);
    $catalog = $resC['data'] ?? [];
} catch (ApiClientException $e) {
    flash_set('error', $e->getMessage());
    redirect('/plans');
}
if (!$plan) { http_response_code(404); echo 'Not found'; return; }

$includedSet = array_fill_keys($modules, true);
$included = array_values(array_filter($catalog, fn($m) => isset($includedSet[$m['id']])));
$excluded = array_values(array_filter($catalog, fn($m) => !isset($includedSet[$m['id']])));

ob_start();
?>
<?php
  $title = $plan['name'];
  $subtitle = 'Código: ' . $plan['code'];
  $breadcrumbs = [['label' => 'Dashboard', 'href' => '/dashboard'], ['label' => 'Planes', 'href' => '/plans'], ['label' => $plan['code']]];
  $actionsHtml = '';
  if (can('plans.update')) {
    $actionsHtml .= '<a href="/plans/' . $id . '/edit" class="btn-secondary inline-flex">Editar</a>';
  }
  $actionsHtml .= '<a href="/plan-matrix" class="btn-ghost inline-flex">Editar matriz</a>';
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>

<section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Precio</div>
    <div class="mt-2 text-2xl font-semibold text-white tabular-nums"><?= e(format_money($plan['price_amount'], $plan['currency_code'])) ?></div>
    <div class="text-xs text-slate-500 mt-1"><?= e(frequency_label($plan['billing_frequency'])) ?></div>
  </div>
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Estado</div>
    <div class="mt-2"><?php $status = $plan['status']; include dirname(__DIR__, 2) . '/components/status_badge.php'; ?></div>
    <?php if (!empty($plan['is_custom'])): ?>
      <div class="mt-2 text-xs text-slate-400">Plan a medida</div>
    <?php endif; ?>
  </div>
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Módulos incluidos</div>
    <div class="mt-2 text-2xl font-semibold text-white tabular-nums"><?= count($included) ?></div>
    <div class="text-xs text-slate-500 mt-1">de <?= count($catalog) ?> disponibles</div>
  </div>
</section>

<div class="card p-6 mb-6">
  <h3 class="text-sm font-semibold text-white mb-3">Descripción</h3>
  <p class="text-sm text-slate-300 whitespace-pre-line"><?= e($plan['description'] ?: 'Sin descripción.') ?></p>
</div>

<section class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div class="card">
    <div class="px-5 py-4 border-b border-slate-800/60">
      <h3 class="text-sm font-semibold text-white">Módulos incluidos</h3>
    </div>
    <ul class="divide-y divide-slate-800/60">
      <?php if (empty($included)): ?>
        <li class="px-5 py-6 text-center text-sm text-slate-500">Ningún módulo asignado. <a href="/plan-matrix" class="text-brand-300 hover:text-brand-200">Asignar ahora →</a></li>
      <?php else: foreach ($included as $m): ?>
        <li class="px-5 py-3 flex items-center gap-3">
          <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
          <div class="flex-1 min-w-0">
            <div class="text-sm text-slate-100"><?= e($m['name']) ?><?= $m['is_core'] ? ' <span class="ml-1 text-[10px] uppercase px-1 rounded bg-brand-500/15 text-brand-200">core</span>' : '' ?></div>
            <div class="text-xs text-slate-500 font-mono"><?= e($m['code']) ?> · <?= e(category_label($m['category'])) ?></div>
          </div>
        </li>
      <?php endforeach; endif; ?>
    </ul>
  </div>
  <div class="card">
    <div class="px-5 py-4 border-b border-slate-800/60">
      <h3 class="text-sm font-semibold text-white">Módulos no incluidos</h3>
    </div>
    <ul class="divide-y divide-slate-800/60">
      <?php if (empty($excluded)): ?>
        <li class="px-5 py-6 text-center text-sm text-slate-500">Este plan incluye todos los módulos activos.</li>
      <?php else: foreach ($excluded as $m): ?>
        <li class="px-5 py-3 flex items-center gap-3">
          <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
          <div class="flex-1 min-w-0">
            <div class="text-sm text-slate-300"><?= e($m['name']) ?></div>
            <div class="text-xs text-slate-500 font-mono"><?= e($m['code']) ?></div>
          </div>
        </li>
      <?php endforeach; endif; ?>
    </ul>
  </div>
</section>
<?php $content = ob_get_clean(); $title = $plan['name']; include dirname(__DIR__, 2) . '/layouts/main.php';
