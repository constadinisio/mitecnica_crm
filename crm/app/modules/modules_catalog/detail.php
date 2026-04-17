<?php
declare(strict_types=1);

require_auth();
require_permission('modules.view');

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { http_response_code(404); echo 'Not found'; return; }

$module = null;
try {
    $res = api_get("/modules-catalog/$id");
    $module = $res['data'] ?? null;
} catch (ApiClientException $e) {
    flash_set('error', $e->getMessage());
    redirect('/modules');
}
if (!$module) { http_response_code(404); echo 'Not found'; return; }

ob_start();
?>
<?php
  $title = $module['name'];
  $subtitle = 'Código: ' . $module['code'];
  $breadcrumbs = [['label' => 'Dashboard', 'href' => '/dashboard'], ['label' => 'Módulos', 'href' => '/modules'], ['label' => $module['code']]];
  $actionsHtml = can('modules.update') ? '<a href="/modules/' . $id . '/edit" class="btn-secondary inline-flex">Editar</a>' : '';
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>
<section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Categoría</div>
    <div class="mt-2 text-lg font-semibold text-white"><?= e(category_label($module['category'])) ?></div>
  </div>
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Estado</div>
    <div class="mt-2"><?php $status = $module['status']; include dirname(__DIR__, 2) . '/components/status_badge.php'; ?></div>
  </div>
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Tipo</div>
    <div class="mt-2 text-lg font-semibold text-white"><?= $module['is_core'] ? 'Core' : 'Opcional' ?></div>
  </div>
</section>
<div class="card p-6">
  <h3 class="text-sm font-semibold text-white mb-3">Descripción</h3>
  <p class="text-sm text-slate-300 whitespace-pre-line"><?= e($module['description'] ?: 'Sin descripción.') ?></p>
</div>
<?php $content = ob_get_clean(); $title = $module['name']; include dirname(__DIR__, 2) . '/layouts/main.php';
