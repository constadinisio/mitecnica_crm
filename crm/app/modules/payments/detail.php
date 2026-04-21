<?php
declare(strict_types=1);

require_auth();
require_permission('payments.view');

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { http_response_code(404); echo 'Not found'; return; }

$payment = null;
try {
    $payment = api_get("/payments/$id")['data'] ?? null;
} catch (ApiClientException $e) {
    flash_set('error', $e->getMessage());
    redirect('/payments');
}
if (!$payment) { http_response_code(404); echo 'Not found'; return; }

ob_start();
?>
<?php
  $title = 'Pago #' . $id;
  $subtitle = ($payment['institution_name'] ?? '—') . ' · ' . format_money($payment['amount'], $payment['currency_code']);
  $breadcrumbs = [['label' => 'Dashboard', 'href' => '/dashboard'], ['label' => 'Pagos', 'href' => '/payments'], ['label' => '#' . $id]];
  $actionsHtml = '';
  if (!empty($payment['institution_id'])) {
    $actionsHtml .= '<a href="/institutions/' . (int)$payment['institution_id'] . '" class="btn-secondary h-9 text-sm inline-flex items-center">Ver institución</a> ';
  }
  if (!empty($payment['subscription_id']) && can('subscriptions.view')) {
    $actionsHtml .= '<a href="/subscriptions/' . (int)$payment['subscription_id'] . '" class="btn-secondary h-9 text-sm inline-flex items-center">Ver suscripción</a> ';
  }
  if (can('audit.view')) {
    $actionsHtml .= '<a href="/audit?entity=payments&entity_id=' . (int)$id . '" class="btn-secondary h-9 text-sm inline-flex items-center">Auditoría</a> ';
  }
  if (can('payments.update')) {
    $actionsHtml .= '<a href="/payments/' . $id . '/edit" class="btn-primary h-9 text-sm inline-flex items-center">Editar</a>';
  }
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>
<section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Monto</div>
    <div class="mt-2 text-2xl font-semibold text-white tabular-nums"><?= e(format_money($payment['amount'], $payment['currency_code'])) ?></div>
    <div class="text-xs text-slate-500 mt-1"><?= format_date($payment['payment_date']) ?></div>
  </div>
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Estado</div>
    <div class="mt-2"><?php $status = $payment['status']; include dirname(__DIR__, 2) . '/components/status_badge.php'; ?></div>
    <?php if (can('payments.change_status')): ?>
      <form method="post" action="/payments/<?= (int)$payment['id'] ?>/status" class="mt-3 flex items-center gap-2">
        <?= csrf_field() ?>
        <select name="status" class="input h-9 text-sm flex-1">
          <?php foreach (['pending','approved','rejected','expired','canceled'] as $st): ?>
            <option value="<?= e($st) ?>" <?= $st === $payment['status'] ? 'selected' : '' ?>><?= e(status_label($st)) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn-secondary h-9 px-3 text-sm">Cambiar</button>
      </form>
    <?php endif; ?>
  </div>
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Método</div>
    <div class="mt-2 text-lg font-semibold text-white"><?= e($payment['payment_method'] ?? '—') ?></div>
    <?php if (!empty($payment['reference_code'])): ?>
      <div class="text-xs text-slate-500 font-mono mt-1"><?= e($payment['reference_code']) ?></div>
    <?php endif; ?>
  </div>
</section>
<div class="card p-6 mb-6">
  <h3 class="text-sm font-semibold text-white mb-3">Detalles</h3>
  <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
    <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Institución</dt><dd class="mt-1"><a href="/institutions/<?= (int)$payment['institution_id'] ?>" class="text-slate-100 hover:text-brand-300"><?= e($payment['institution_name'] ?? '—') ?></a><div class="text-xs text-slate-500"><?= e($payment['institution_code'] ?? '') ?></div></dd></div>
    <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Suscripción</dt>
      <dd class="mt-1">
        <?php if (!empty($payment['subscription_id'])): ?>
          <a href="/subscriptions/<?= (int)$payment['subscription_id'] ?>" class="text-slate-100 hover:text-brand-300">#<?= (int)$payment['subscription_id'] ?> · <?= e($payment['plan_name'] ?? '—') ?></a>
        <?php else: ?>
          <span class="text-slate-500">—</span>
        <?php endif; ?>
      </dd>
    </div>
    <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Creado por</dt><dd class="mt-1 text-slate-300"><?= e($payment['created_by_name'] ?? 'Sistema') ?></dd></div>
    <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Creado</dt><dd class="mt-1 text-slate-300"><?= format_datetime($payment['created_at'] ?? null) ?></dd></div>
  </dl>
  <?php if (!empty($payment['notes'])): ?>
    <div class="mt-6 p-4 rounded-xl bg-slate-900/70 border border-slate-800">
      <div class="text-xs uppercase text-slate-500 tracking-wider mb-2">Notas</div>
      <div class="text-sm text-slate-200 whitespace-pre-line"><?= e($payment['notes']) ?></div>
    </div>
  <?php endif; ?>
</div>
<?php $content = ob_get_clean(); $title = 'Pago #' . $id; include dirname(__DIR__, 2) . '/layouts/main.php';
