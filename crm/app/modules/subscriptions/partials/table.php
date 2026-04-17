<?php
declare(strict_types=1);
$list = $subs ?? [];
?>
<div class="card mt-4 overflow-hidden">
  <?php if (empty($list)): ?>
    <?php
      $title = 'Sin suscripciones';
      $message = 'Creá una suscripción para empezar.';
      $ctaLabel = can('subscriptions.create') ? 'Nueva suscripción' : null;
      $ctaHref = can('subscriptions.create') ? '/subscriptions/new' : null;
      include dirname(__DIR__, 3) . '/components/empty_state.php';
    ?>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="data-table">
        <thead>
          <tr>
            <th>Institución</th>
            <th>Plan</th>
            <th>Estado</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Renovación</th>
            <th class="text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($list as $s):
            $endDays = days_until($s['end_date'] ?? null);
          ?>
            <tr class="hover:bg-slate-900/50">
              <td>
                <a href="/institutions/<?= (int)$s['institution_id'] ?>" class="text-white hover:text-brand-300"><?= e($s['institution_name'] ?? '—') ?></a>
                <div class="text-xs text-slate-500"><?= e($s['institution_code'] ?? '') ?></div>
              </td>
              <td>
                <div class="text-slate-100"><?= e($s['plan_name'] ?? '—') ?></div>
                <div class="text-xs text-slate-500 tabular-nums"><?= e(format_money($s['plan_price_amount'] ?? 0, $s['plan_currency_code'] ?? 'ARS')) ?> · <?= e(frequency_label($s['plan_billing_frequency'] ?? '')) ?></div>
              </td>
              <td><?php $status = $s['status']; include dirname(__DIR__, 3) . '/components/status_badge.php'; ?></td>
              <td class="text-slate-300"><?= format_date($s['start_date'] ?? null) ?></td>
              <td>
                <div class="text-slate-300"><?= format_date($s['end_date'] ?? null) ?></div>
                <?php if ($endDays !== null): ?>
                  <div class="text-xs <?= $endDays < 0 ? 'text-rose-400' : ($endDays <= 7 ? 'text-amber-300' : 'text-slate-500') ?>">
                    <?= $endDays < 0 ? 'Vencido hace ' . abs($endDays) . ' d' : 'En ' . $endDays . ' d' ?>
                  </div>
                <?php endif; ?>
              </td>
              <td class="text-slate-400"><?= $s['renewal_mode'] === 'automatic' ? 'Automática' : 'Manual' ?></td>
              <td class="text-right">
                <div class="inline-flex items-center gap-1">
                  <a href="/subscriptions/<?= (int)$s['id'] ?>" class="btn-ghost h-8 px-3">Ver</a>
                  <?php if (can('subscriptions.update')): ?>
                    <a href="/subscriptions/<?= (int)$s['id'] ?>/edit" class="btn-ghost h-8 px-3">Editar</a>
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
