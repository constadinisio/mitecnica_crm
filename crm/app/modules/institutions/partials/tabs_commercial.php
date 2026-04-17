<?php
declare(strict_types=1);
$ins = $institution;
$days = days_until($ins['expiration_date'] ?? null);
?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Plan vigente</div>
    <div class="mt-2 text-xl font-semibold text-white"><?= e($ins['current_plan_name'] ?? 'Sin plan') ?></div>
    <p class="mt-2 text-xs text-slate-500">
      La gestión completa de planes y módulos estará disponible en la próxima fase.
    </p>
  </div>
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Vencimiento</div>
    <div class="mt-2 text-xl font-semibold text-white"><?= format_date($ins['expiration_date'] ?? null) ?></div>
    <?php if ($days !== null): ?>
      <div class="mt-1 text-xs <?= $days < 0 ? 'text-rose-400' : ($days <= 7 ? 'text-amber-300' : 'text-slate-500') ?>">
        <?= $days < 0 ? 'Vencido hace ' . abs($days) . ' días' : ($days === 0 ? 'Vence hoy' : 'En ' . $days . ' días') ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Estado comercial</div>
    <div class="mt-2"><?php $status = $ins['status']; include dirname(__DIR__, 3) . '/components/status_badge.php'; ?></div>
    <div class="mt-3 text-xs text-slate-500">
      Desde esta vista podés cambiar el estado usando el bloque superior de la ficha.
    </div>
  </div>
</div>

<div class="mt-6 text-xs text-slate-500">
  <strong class="text-slate-300">Próximamente:</strong> historial de facturación, integraciones de pago y gestión de módulos por plan.
</div>
