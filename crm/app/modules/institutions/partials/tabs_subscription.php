<?php
declare(strict_types=1);
/** @var array|null $license */
/** @var array $institution */

$sub  = $license['subscription'] ?? null;
$plan = $license['plan'] ?? null;
?>

<?php if (!$sub): ?>
  <div class="p-6 rounded-xl border border-dashed border-slate-800 text-center">
    <div class="text-slate-200 text-sm mb-2">Esta institución no tiene una suscripción activa.</div>
    <p class="text-xs text-slate-500 mb-4">Creá una suscripción para asignar un plan y habilitar la licencia.</p>
    <?php if (can('subscriptions.create')): ?>
      <a href="/subscriptions/new?institution_id=<?= (int)$institution['id'] ?>" class="btn-primary inline-flex">Crear suscripción</a>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <dl class="space-y-3 text-sm">
      <div>
        <dt class="text-xs uppercase text-slate-500 tracking-wider">Plan</dt>
        <dd class="mt-1 text-slate-100 font-medium">
          <?= $plan ? e($plan['name']) : '—' ?>
          <?php if ($plan): ?>
            <span class="text-xs text-slate-500 font-mono ml-2"><?= e($plan['code']) ?></span>
          <?php endif; ?>
        </dd>
      </div>
      <div>
        <dt class="text-xs uppercase text-slate-500 tracking-wider">Estado</dt>
        <dd class="mt-1"><?php $status = $sub['status']; include dirname(__DIR__, 3) . '/components/status_badge.php'; ?></dd>
      </div>
      <div>
        <dt class="text-xs uppercase text-slate-500 tracking-wider">Ciclo</dt>
        <dd class="mt-1 text-slate-200">
          <?= $plan ? e(format_money($plan['price_amount'], $plan['currency_code'])) . ' · ' . e(frequency_label($plan['billing_frequency'])) : '—' ?>
        </dd>
      </div>
      <div>
        <dt class="text-xs uppercase text-slate-500 tracking-wider">Renovación</dt>
        <dd class="mt-1 text-slate-200"><?= e(($sub['renewal_mode'] ?? '') === 'automatic' ? 'Automática' : 'Manual') ?></dd>
      </div>
    </dl>
    <dl class="space-y-3 text-sm">
      <div>
        <dt class="text-xs uppercase text-slate-500 tracking-wider">Inicio</dt>
        <dd class="mt-1 text-slate-200 tabular-nums"><?= format_date($sub['start_date']) ?></dd>
      </div>
      <div>
        <dt class="text-xs uppercase text-slate-500 tracking-wider">Fin</dt>
        <dd class="mt-1 text-slate-200 tabular-nums"><?= format_date($sub['end_date']) ?></dd>
      </div>
      <div>
        <dt class="text-xs uppercase text-slate-500 tracking-wider">Fin de trial</dt>
        <dd class="mt-1 text-slate-300"><?= format_datetime($sub['trial_ends_at'] ?? null) ?></dd>
      </div>
      <div>
        <dt class="text-xs uppercase text-slate-500 tracking-wider">ID Suscripción</dt>
        <dd class="mt-1"><a href="/subscriptions/<?= (int)$sub['id'] ?>" class="text-brand-300 hover:text-brand-200 font-mono text-xs">#<?= (int)$sub['id'] ?> →</a></dd>
      </div>
    </dl>
  </div>

  <div class="mt-6 flex flex-wrap items-center gap-3">
    <a href="/subscriptions/<?= (int)$sub['id'] ?>" class="btn-secondary">Ver ficha completa</a>
    <?php if (can('subscriptions.update')): ?>
      <a href="/subscriptions/<?= (int)$sub['id'] ?>/edit" class="btn-primary">Editar suscripción</a>
    <?php endif; ?>
  </div>
<?php endif; ?>
