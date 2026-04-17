<?php
declare(strict_types=1);
/** @var array|null $license */
/** @var array $institution */

if (!$license) {
    ?>
    <div class="p-6 text-sm text-slate-400">No se pudo cargar el resumen de licencia.</div>
    <?php
    return;
}

$plan = $license['plan'] ?? null;
$sub  = $license['subscription'] ?? null;
$exp  = $license['expiration'] ?? ['end_date' => null, 'days_remaining' => null];
$days = $exp['days_remaining'];
$daysClass = $days === null ? 'text-slate-500'
            : ($days < 0 ? 'text-rose-400' : ($days <= 7 ? 'text-amber-300' : 'text-emerald-300'));
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Plan</div>
    <div class="mt-2 text-xl font-semibold text-white">
      <?= $plan ? e($plan['name']) : 'Sin plan asignado' ?>
    </div>
    <?php if ($plan): ?>
      <div class="text-xs text-slate-500 mt-1 font-mono"><?= e($plan['code']) ?></div>
      <div class="text-sm text-slate-300 mt-3 tabular-nums">
        <?= e(format_money($plan['price_amount'], $plan['currency_code'])) ?>
        <span class="text-slate-500"> · <?= e(frequency_label($plan['billing_frequency'])) ?></span>
      </div>
    <?php else: ?>
      <p class="text-xs text-slate-500 mt-3">Crear una suscripción desde la pestaña Suscripción.</p>
    <?php endif; ?>
  </div>

  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Suscripción</div>
    <?php if ($sub): ?>
      <div class="mt-2 flex items-center gap-2">
        <?php $status = $sub['status']; include dirname(__DIR__, 3) . '/components/status_badge.php'; ?>
      </div>
      <div class="text-xs text-slate-500 mt-2">
        <div>Inicio: <?= format_date($sub['start_date']) ?></div>
        <div>Fin: <?= format_date($sub['end_date']) ?></div>
        <?php if (!empty($sub['trial_ends_at'])): ?>
          <div>Trial hasta: <?= format_datetime($sub['trial_ends_at']) ?></div>
        <?php endif; ?>
      </div>
      <a href="/subscriptions/<?= (int)$sub['id'] ?>" class="inline-block mt-3 text-xs text-brand-300 hover:text-brand-200">Ver suscripción →</a>
    <?php else: ?>
      <div class="mt-2 text-slate-300">Sin suscripción activa</div>
      <p class="text-xs text-slate-500 mt-2">Asignar un plan creando una suscripción.</p>
      <?php if (can('subscriptions.create')): ?>
        <a href="/subscriptions/new?institution_id=<?= (int)$institution['id'] ?>" class="inline-block mt-3 text-xs text-brand-300 hover:text-brand-200">Crear suscripción →</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Vencimiento</div>
    <div class="mt-2 text-xl font-semibold text-white"><?= format_date($exp['end_date']) ?></div>
    <?php if ($days !== null): ?>
      <div class="mt-1 text-xs <?= $daysClass ?>">
        <?= $days < 0 ? 'Vencido hace ' . abs($days) . ' días'
            : ($days === 0 ? 'Vence hoy' : 'Quedan ' . $days . ' días') ?>
      </div>
    <?php endif; ?>
    <div class="text-xs text-slate-500 mt-3">
      Módulos activos: <span class="text-slate-200 tabular-nums"><?= (int)($license['effective_modules_count'] ?? 0) ?></span>
      / <?= (int)($license['total_modules_count'] ?? 0) ?>
      <?php if (!empty($license['has_overrides'])): ?>
        · <span class="text-sky-300">con overrides</span>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="card p-5">
  <div class="flex items-center justify-between mb-3">
    <h4 class="text-sm font-semibold text-white">Últimos pagos</h4>
    <a href="/payments?institution_id=<?= (int)$institution['id'] ?>" class="text-xs text-brand-300 hover:text-brand-200">Ver todos →</a>
  </div>
  <?php $payments = $license['recent_payments'] ?? []; ?>
  <?php if (empty($payments)): ?>
    <div class="p-6 text-center text-sm text-slate-500">Todavía no hay pagos registrados.</div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="data-table">
        <thead>
          <tr><th>Fecha</th><th>Monto</th><th>Método</th><th>Estado</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td class="tabular-nums text-slate-300"><?= format_datetime($p['payment_date'] ?? null) ?></td>
              <td class="tabular-nums text-slate-100"><?= e(format_money($p['amount'], $p['currency_code'] ?? 'ARS')) ?></td>
              <td class="text-slate-400"><?= e($p['payment_method'] ?? '—') ?></td>
              <td><?php $status = $p['status']; include dirname(__DIR__, 3) . '/components/status_badge.php'; ?></td>
              <td class="text-right"><a href="/payments/<?= (int)$p['id'] ?>" class="text-xs text-brand-300 hover:text-brand-200">Ver</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
