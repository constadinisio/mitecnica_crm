<?php
declare(strict_types=1);
/** @var array|null $effectiveModules */
/** @var array $institution */

if (!$effectiveModules) {
    ?>
    <div class="p-6 text-sm text-slate-400">No se pudo cargar la lista de módulos.</div>
    <?php
    return;
}

$mods = $effectiveModules['modules'] ?? [];
$plan = $effectiveModules['plan']    ?? null;
$summary = $effectiveModules['summary'] ?? ['total' => 0, 'plan_included' => 0, 'override_count' => 0, 'effective_enabled' => 0];
$canEdit = can('institutions.override_modules');
?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
  <div>
    <h4 class="text-sm font-semibold text-white">Módulos efectivos</h4>
    <p class="text-xs text-slate-500 mt-1">
      El estado final depende del plan y de los overrides manuales. Un override siempre gana.
    </p>
  </div>
  <div class="flex items-center gap-3 text-xs">
    <span class="px-2 py-1 rounded bg-slate-900/80 border border-slate-800 text-slate-300">
      Plan: <strong class="text-slate-100 tabular-nums"><?= (int)$summary['plan_included'] ?></strong>
    </span>
    <span class="px-2 py-1 rounded bg-sky-900/40 border border-sky-900/40 text-sky-200">
      Overrides: <strong class="tabular-nums"><?= (int)$summary['override_count'] ?></strong>
    </span>
    <span class="px-2 py-1 rounded bg-emerald-900/30 border border-emerald-900/40 text-emerald-200">
      Efectivos: <strong class="tabular-nums"><?= (int)$summary['effective_enabled'] ?></strong> / <?= (int)$summary['total'] ?>
    </span>
  </div>
</div>

<?php if (!$plan): ?>
  <div class="mb-4 p-3 rounded-lg border border-amber-900/40 bg-amber-900/20 text-xs text-amber-200">
    La institución no tiene una suscripción vigente; todos los módulos aparecen deshabilitados por plan.
    Podés igual forzarlos mediante overrides (el resultado es persistente, pero conviene asignar un plan formal).
  </div>
<?php endif; ?>

<form method="post" action="/institutions/<?= (int)$institution['id'] ?>/modules-overrides">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)$institution['id'] ?>">

  <div class="overflow-x-auto card">
    <table class="data-table">
      <thead>
        <tr>
          <th>Módulo</th>
          <th class="text-center">Plan</th>
          <th class="text-center">Override manual</th>
          <th class="text-center">Estado final</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($mods as $row):
          $m = $row['module'];
          $planIncl = !empty($row['plan_included']);
          $override = $row['override_mode']; // null | force_enabled | force_disabled
          $effective = !empty($row['effective_enabled']);
          $rowTint = match ($row['source']) {
            'override', 'plan+override' => 'bg-sky-950/20',
            default => '',
          };
        ?>
          <tr class="<?= $rowTint ?>">
            <td>
              <div class="text-slate-100 font-medium"><?= e($m['name']) ?></div>
              <div class="text-xs text-slate-500 font-mono">
                <?= e($m['code']) ?> · <?= e(category_label($m['category'] ?? null)) ?>
                <?= !empty($m['is_core']) ? ' · <span class="text-brand-300">core</span>' : '' ?>
              </div>
            </td>
            <td class="text-center">
              <?php if ($planIncl): ?>
                <span class="inline-flex items-center gap-1 text-xs text-slate-300"><span class="h-2 w-2 rounded-full bg-slate-400"></span>Incluido</span>
              <?php else: ?>
                <span class="inline-flex items-center gap-1 text-xs text-slate-500"><span class="h-2 w-2 rounded-full bg-slate-700"></span>No incluido</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php if ($canEdit): ?>
                <select name="override_mode[<?= (int)$m['id'] ?>]" class="input h-9 text-xs w-44 mx-auto">
                  <option value="" <?= $override === null ? 'selected' : '' ?>>Sin override (usa plan)</option>
                  <option value="force_enabled" <?= $override === 'force_enabled' ? 'selected' : '' ?>>Forzar habilitado</option>
                  <option value="force_disabled" <?= $override === 'force_disabled' ? 'selected' : '' ?>>Forzar deshabilitado</option>
                </select>
              <?php else: ?>
                <span class="text-xs text-slate-400">
                  <?= $override === 'force_enabled' ? 'Forzado ✔'
                      : ($override === 'force_disabled' ? 'Forzado ✘' : '—') ?>
                </span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php if ($effective): ?>
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs bg-emerald-900/40 text-emerald-200">
                  <span class="h-2 w-2 rounded-full bg-emerald-400"></span>Habilitado
                </span>
              <?php else: ?>
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs bg-slate-800 text-slate-400">
                  <span class="h-2 w-2 rounded-full bg-slate-500"></span>Deshabilitado
                </span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($mods)): ?>
          <tr><td colspan="4" class="text-center text-slate-500 py-8">No hay módulos en el catálogo.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($canEdit): ?>
    <div class="flex items-center justify-end mt-4 gap-3">
      <span class="text-xs text-slate-500">Los cambios impactan inmediatamente en la licencia efectiva.</span>
      <button type="submit" class="btn-primary">Guardar overrides</button>
    </div>
  <?php else: ?>
    <div class="text-xs text-slate-500 mt-3">Solo lectura. Requiere rol <strong>commercial</strong> para editar overrides.</div>
  <?php endif; ?>
</form>
