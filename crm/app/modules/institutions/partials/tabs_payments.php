<?php
declare(strict_types=1);
/** @var array $institutionPayments */
/** @var array $institution */

$rows = $institutionPayments ?? [];
?>

<div class="flex items-center justify-between mb-4">
  <div>
    <h4 class="text-sm font-semibold text-white">Pagos recientes</h4>
    <p class="text-xs text-slate-500">Últimos pagos asociados a esta institución.</p>
  </div>
  <div class="flex items-center gap-3">
    <a href="/payments?institution_id=<?= (int)$institution['id'] ?>" class="text-xs text-brand-300 hover:text-brand-200">Ver histórico completo →</a>
    <?php if (can('payments.create')): ?>
      <a href="/payments/new?institution_id=<?= (int)$institution['id'] ?>" class="btn-primary">Registrar pago</a>
    <?php endif; ?>
  </div>
</div>

<?php if (empty($rows)): ?>
  <div class="p-8 text-center text-sm text-slate-500 border border-dashed border-slate-800 rounded-xl">
    No hay pagos registrados todavía.
  </div>
<?php else: ?>
  <div class="overflow-x-auto card">
    <table class="data-table">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Monto</th>
          <th>Método</th>
          <th>Referencia</th>
          <th>Estado</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $p): ?>
          <tr>
            <td class="tabular-nums text-slate-300"><?= format_datetime($p['payment_date'] ?? null) ?></td>
            <td class="tabular-nums text-slate-100"><?= e(format_money($p['amount'], $p['currency_code'] ?? 'ARS')) ?></td>
            <td class="text-slate-400"><?= e($p['payment_method'] ?? '—') ?></td>
            <td class="font-mono text-xs text-slate-400"><?= e($p['reference_code'] ?? '—') ?></td>
            <td><?php $status = $p['status']; include dirname(__DIR__, 3) . '/components/status_badge.php'; ?></td>
            <td class="text-right"><a href="/payments/<?= (int)$p['id'] ?>" class="text-xs text-brand-300 hover:text-brand-200">Ver →</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
