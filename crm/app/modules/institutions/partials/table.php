<?php
declare(strict_types=1);

$list = $institutions ?? [];
?>
<div class="card mt-4 overflow-hidden">
  <?php if (empty($list)): ?>
    <?php
      $hasFilter = !empty(array_filter([$_GET['search'] ?? '', $_GET['status'] ?? '', $_GET['plan'] ?? '']));
      $title = $hasFilter ? 'Sin resultados' : 'Ninguna institución registrada';
      $message = $hasFilter
        ? 'Probá ajustar los filtros o limpiar la búsqueda.'
        : 'Cuando des de alta la primera institución, va a aparecer en esta tabla.';
      $ctaLabel = can('institutions.create') ? ($hasFilter ? 'Nueva institución' : 'Crear primera institución') : null;
      $ctaHref = $ctaLabel ? '/institutions/new' : null;
      include dirname(__DIR__, 3) . '/components/empty_state.php';
    ?>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="data-table">
        <thead>
          <tr>
            <th>Institución</th>
            <th>Estado</th>
            <th>Plan</th>
            <th>Vencimiento</th>
            <th>Última actividad</th>
            <th class="text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($list as $ins):
            $days = days_until($ins['expiration_date'] ?? null);
          ?>
            <tr class="hover:bg-slate-900/50">
              <td>
                <div class="flex items-center gap-3">
                  <div class="h-9 w-9 rounded-lg bg-slate-800 border border-slate-700 grid place-items-center text-xs text-slate-200 font-semibold"><?= e(initials($ins['name'])) ?></div>
                  <div class="min-w-0">
                    <a href="/institutions/<?= (int)$ins['id'] ?>" class="text-white hover:text-brand-300 truncate block max-w-[18rem]"><?= e($ins['name']) ?></a>
                    <div class="text-xs text-slate-500"><?= e($ins['public_code']) ?> · <?= e($ins['subdomain']) ?></div>
                  </div>
                </div>
              </td>
              <td><?php $status = $ins['status']; include dirname(__DIR__, 3) . '/components/status_badge.php'; ?></td>
              <td class="text-slate-300"><?= e($ins['current_plan_name'] ?? '—') ?></td>
              <td>
                <div class="text-slate-200"><?= format_date($ins['expiration_date'] ?? null) ?></div>
                <?php if ($days !== null): ?>
                  <div class="text-xs <?= $days < 0 ? 'text-rose-400' : ($days <= 7 ? 'text-amber-300' : 'text-slate-500') ?>">
                    <?= $days < 0 ? 'Vencido hace ' . abs($days) . ' d' : 'En ' . $days . ' d' ?>
                  </div>
                <?php endif; ?>
              </td>
              <td class="text-slate-400 text-xs"><?= format_relative($ins['last_activity_at'] ?? null) ?></td>
              <td class="text-right">
                <div class="inline-flex items-center gap-1">
                  <a href="/institutions/<?= (int)$ins['id'] ?>" class="btn-ghost h-8 px-3">Ver</a>
                  <?php if (can('institutions.update')): ?>
                    <a href="/institutions/<?= (int)$ins['id'] ?>/edit" class="btn-ghost h-8 px-3">Editar</a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
