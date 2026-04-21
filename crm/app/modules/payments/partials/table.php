<?php
declare(strict_types=1);
$list = $payments ?? [];
?>
<div class="card mt-4 overflow-hidden">
  <?php if (empty($list)): ?>
    <?php
      $hasFilter = !empty(array_filter([$_GET['search'] ?? '', $_GET['status'] ?? '', $_GET['institution_id'] ?? '', $_GET['from'] ?? '', $_GET['to'] ?? '']));
      $title = $hasFilter ? 'Sin resultados' : 'Sin pagos registrados';
      $message = $hasFilter
        ? 'Ningún pago coincide con los filtros aplicados. Probá con otro rango de fechas o estado.'
        : 'Cuando registres pagos asociados a una institución o suscripción, aparecerán en esta tabla.';
      $ctaLabel = (!$hasFilter && can('payments.create')) ? 'Registrar primer pago' : null;
      $ctaHref = $ctaLabel ? '/payments/new' : null;
      include dirname(__DIR__, 3) . '/components/empty_state.php';
    ?>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="data-table">
        <thead>
          <tr>
            <th>Institución</th>
            <th>Plan</th>
            <th class="text-right">Monto</th>
            <th>Fecha</th>
            <th>Método</th>
            <th>Referencia</th>
            <th>Estado</th>
            <th class="text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($list as $p): ?>
            <tr class="hover:bg-slate-900/50">
              <td>
                <a href="/institutions/<?= (int)$p['institution_id'] ?>" class="text-white hover:text-brand-300"><?= e($p['institution_name'] ?? '—') ?></a>
                <div class="text-xs text-slate-500"><?= e($p['institution_code'] ?? '') ?></div>
              </td>
              <td class="text-slate-300"><?= e($p['plan_name'] ?? '—') ?></td>
              <td class="text-right text-slate-100 tabular-nums"><?= e(format_money($p['amount'], $p['currency_code'])) ?></td>
              <td class="text-slate-300"><?= format_date($p['payment_date']) ?></td>
              <td class="text-slate-300"><?= e($p['payment_method'] ?? '—') ?></td>
              <td><span class="font-mono text-xs text-slate-400"><?= e($p['reference_code'] ?? '—') ?></span></td>
              <td><?php $status = $p['status']; include dirname(__DIR__, 3) . '/components/status_badge.php'; ?></td>
              <td class="text-right">
                <div class="inline-flex items-center gap-1">
                  <a href="/payments/<?= (int)$p['id'] ?>" class="btn-ghost h-8 px-3">Ver</a>
                  <?php if (can('payments.update')): ?>
                    <a href="/payments/<?= (int)$p['id'] ?>/edit" class="btn-ghost h-8 px-3">Editar</a>
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
