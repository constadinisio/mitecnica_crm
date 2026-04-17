<?php
declare(strict_types=1);

require_auth();
require_permission('plans.view');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (!can('plans.update')) {
        flash_set('error', 'No tenés permisos para editar la matriz.');
        redirect('/plan-matrix');
    }
    $planId = (int)($_POST['plan_id'] ?? 0);
    $moduleIds = array_map('intval', (array)($_POST['module_ids'] ?? []));
    if ($planId < 1) {
        flash_set('error', 'plan_id inválido.');
        redirect('/plan-matrix');
    }
    try {
        api_put("/plans/$planId/modules", ['body' => ['module_ids' => array_values(array_unique($moduleIds))]]);
        flash_set('success', 'Matriz actualizada.');
    } catch (ApiClientException $e) {
        flash_set('error', $e->getMessage() ?: 'No se pudo guardar la matriz.');
    }
    redirect('/plan-matrix');
}

$matrix = ['plans' => [], 'modules' => [], 'relations' => []];
try {
    $res = api_get('/plan-modules/matrix');
    $matrix = $res['data'] ?? $matrix;
} catch (ApiClientException $e) {
    flash_set('error', 'No se pudo cargar la matriz: ' . $e->getMessage());
}

$plansList = $matrix['plans'] ?? [];
$modulesList = $matrix['modules'] ?? [];
$rel = $matrix['relations'] ?? [];

ob_start();
?>
<?php
  $title = 'Matriz Planes × Módulos';
  $subtitle = 'Qué módulos incluye cada plan. Guardá los cambios por plan (columna).';
  $breadcrumbs = [['label' => 'Dashboard', 'href' => '/dashboard'], ['label' => 'Planes', 'href' => '/plans'], ['label' => 'Matriz']];
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>

<div class="grid grid-cols-1 xl:grid-cols-4 gap-4">
  <div class="xl:col-span-3 card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="data-table">
        <thead>
          <tr>
            <th class="sticky left-0 bg-slate-950/80 z-10 min-w-[18rem]">Módulo</th>
            <?php foreach ($plansList as $pl): ?>
              <th class="text-center min-w-[10rem]">
                <div class="text-white font-semibold"><?= e($pl['name']) ?></div>
                <div class="text-[11px] normal-case tracking-normal font-normal text-slate-400 mt-0.5 tabular-nums"><?= e(format_money($pl['price_amount'], $pl['currency_code'])) ?> · <?= e(frequency_label($pl['billing_frequency'])) ?></div>
              </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($modulesList as $mod): ?>
            <tr>
              <td class="sticky left-0 bg-slate-950/60 z-10">
                <div class="text-slate-100 font-medium"><?= e($mod['name']) ?></div>
                <div class="text-xs text-slate-500 font-mono"><?= e($mod['code']) ?> · <?= e(category_label($mod['category'])) ?><?= !empty($mod['is_core']) ? ' · <span class="text-brand-300">core</span>' : '' ?></div>
              </td>
              <?php foreach ($plansList as $pl):
                $checked = !empty($rel[$pl['id']][$mod['id']]);
              ?>
                <td class="text-center">
                  <label class="inline-flex items-center justify-center">
                    <input type="checkbox"
                           data-matrix-cell
                           data-plan-id="<?= (int)$pl['id'] ?>"
                           data-module-id="<?= (int)$mod['id'] ?>"
                           <?= $checked ? 'checked' : '' ?>
                           <?= can('plans.update') ? '' : 'disabled' ?>
                           class="h-5 w-5 rounded border-slate-700 bg-slate-900 text-brand-500 focus:ring-brand-500/40 cursor-pointer">
                  </label>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <aside class="card p-5 space-y-4">
    <h3 class="text-sm font-semibold text-white">Resumen</h3>
    <ul class="space-y-3 text-sm">
      <?php foreach ($plansList as $pl):
        $count = count(array_filter(($rel[$pl['id']] ?? []), fn($v) => (bool)$v));
      ?>
        <li class="flex items-center justify-between">
          <div>
            <div class="text-slate-100"><?= e($pl['name']) ?></div>
            <div class="text-xs text-slate-500"><?= e(frequency_label($pl['billing_frequency'])) ?></div>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-slate-300 tabular-nums text-sm" data-plan-count="<?= (int)$pl['id'] ?>"><?= $count ?></span>
            <span class="text-xs text-slate-500">/ <?= count($modulesList) ?></span>
            <?php if (can('plans.update')): ?>
              <button type="button"
                      class="btn-primary h-8 px-3 text-xs"
                      data-plan-save="<?= (int)$pl['id'] ?>">Guardar</button>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php if (!can('plans.update')): ?>
      <div class="text-xs text-slate-500 pt-3 border-t border-slate-800/60">Solo lectura. Necesitás permisos de <strong>commercial</strong> para editar.</div>
    <?php endif; ?>
  </aside>
</div>

<!-- Hidden forms used by the matrix save buttons, one per plan -->
<?php foreach ($plansList as $pl): ?>
  <form method="post" action="/plan-matrix" id="plan-form-<?= (int)$pl['id'] ?>" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="plan_id" value="<?= (int)$pl['id'] ?>">
    <?php foreach ($modulesList as $mod):
      $checked = !empty($rel[$pl['id']][$mod['id']]);
    ?>
      <input type="checkbox"
             name="module_ids[]"
             value="<?= (int)$mod['id'] ?>"
             data-plan-form-cell="<?= (int)$pl['id'] ?>-<?= (int)$mod['id'] ?>"
             <?= $checked ? 'checked' : '' ?>>
    <?php endforeach; ?>
  </form>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
$title = 'Matriz Planes × Módulos';
$extraScripts = ['/assets/js/plan-matrix.js'];
include dirname(__DIR__, 2) . '/layouts/main.php';
