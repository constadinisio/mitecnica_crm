<?php
declare(strict_types=1);

require_auth();
require_permission('subscriptions.view');

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { http_response_code(404); echo 'Not found'; return; }

$sub = null;
$payments = [];
try {
    $sub = api_get("/subscriptions/$id")['data'] ?? null;
    if ($sub) {
        $res = api_get('/payments', ['query' => ['subscription_id' => $id, 'limit' => 20, 'sort' => 'payment_date', 'order' => 'desc']]);
        $payments = $res['data'] ?? [];
    }
} catch (ApiClientException $e) {
    flash_set('error', $e->getMessage());
    redirect('/subscriptions');
}
if (!$sub) { http_response_code(404); echo 'Not found'; return; }

$endDays = days_until($sub['end_date'] ?? null);
$trialDays = days_until($sub['trial_ends_at'] ? substr((string)$sub['trial_ends_at'], 0, 10) : null);

ob_start();
?>
<?php
  $title = 'Suscripción #' . $id;
  $subtitle = ($sub['institution_name'] ?? '—') . ' · ' . ($sub['plan_name'] ?? '—');
  $breadcrumbs = [
    ['label' => 'Dashboard', 'href' => '/dashboard'],
    ['label' => 'Suscripciones', 'href' => '/subscriptions'],
    ['label' => '#' . $id],
  ];
  $actionsHtml = '';
  if (!empty($sub['institution_id'])) {
    $actionsHtml .= '<a href="/institutions/' . (int)$sub['institution_id'] . '" class="btn-secondary h-9 text-sm inline-flex items-center">Ver institución</a> ';
    if (can('payments.view')) {
      $actionsHtml .= '<a href="/payments?institution_id=' . (int)$sub['institution_id'] . '&subscription_id=' . (int)$sub['id'] . '" class="btn-secondary h-9 text-sm inline-flex items-center">Pagos</a> ';
    }
  }
  if (can('audit.view')) {
    $actionsHtml .= '<a href="/audit?entity=subscriptions&entity_id=' . (int)$id . '" class="btn-secondary h-9 text-sm inline-flex items-center">Auditoría</a> ';
  }
  if (can('subscriptions.update')) $actionsHtml .= '<a href="/subscriptions/' . $id . '/edit" class="btn-primary h-9 text-sm inline-flex items-center">Editar</a>';
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>

<section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Estado</div>
    <div class="mt-2"><?php $status = $sub['status']; include dirname(__DIR__, 2) . '/components/status_badge.php'; ?></div>
    <?php if (can('subscriptions.change_status')): ?>
      <form method="post" action="/subscriptions/<?= (int)$sub['id'] ?>/status" class="mt-3 flex items-center gap-2">
        <?= csrf_field() ?>
        <select name="status" class="input h-9 text-sm flex-1">
          <?php foreach (['trial','active','suspended','expired','canceled'] as $st): ?>
            <option value="<?= e($st) ?>" <?= $st === $sub['status'] ? 'selected' : '' ?>><?= e(status_label($st)) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn-secondary h-9 px-3 text-sm">Cambiar</button>
      </form>
    <?php endif; ?>
  </div>
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Plan</div>
    <div class="mt-2 text-lg font-semibold text-white"><?= e($sub['plan_name'] ?? '—') ?></div>
    <div class="text-xs text-slate-500 mt-1 tabular-nums"><?= e(format_money($sub['plan_price_amount'] ?? 0, $sub['plan_currency_code'] ?? 'ARS')) ?> · <?= e(frequency_label($sub['plan_billing_frequency'] ?? '')) ?></div>
  </div>
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Vigencia</div>
    <div class="mt-2 text-slate-200"><?= format_date($sub['start_date'] ?? null) ?> → <?= format_date($sub['end_date'] ?? null) ?></div>
    <?php if ($endDays !== null): ?>
      <div class="text-xs mt-1 <?= $endDays < 0 ? 'text-rose-400' : ($endDays <= 7 ? 'text-amber-300' : 'text-slate-500') ?>">
        <?= $endDays < 0 ? 'Vencida hace ' . abs($endDays) . ' días' : ($endDays === 0 ? 'Vence hoy' : 'En ' . $endDays . ' días') ?>
      </div>
    <?php endif; ?>
    <?php if ($sub['trial_ends_at']): ?>
      <div class="text-xs text-amber-300 mt-1">Trial hasta <?= format_date($sub['trial_ends_at']) ?></div>
    <?php endif; ?>
  </div>
</section>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
  <div class="md:col-span-2 card p-6">
    <h3 class="text-sm font-semibold text-white mb-3">Detalles</h3>
    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
      <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Institución</dt><dd class="mt-1"><a href="/institutions/<?= (int)$sub['institution_id'] ?>" class="text-slate-100 hover:text-brand-300"><?= e($sub['institution_name'] ?? '—') ?></a><div class="text-xs text-slate-500"><?= e($sub['institution_code'] ?? '') ?></div></dd></div>
      <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Renovación</dt><dd class="mt-1 text-slate-200"><?= $sub['renewal_mode'] === 'automatic' ? 'Automática' : 'Manual' ?></dd></div>
      <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Plan</dt><dd class="mt-1"><a href="/plans/<?= (int)$sub['plan_id'] ?>" class="text-slate-100 hover:text-brand-300"><?= e($sub['plan_name']) ?></a></dd></div>
      <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Alta</dt><dd class="mt-1 text-slate-300"><?= format_datetime($sub['created_at'] ?? null) ?></dd></div>
    </dl>
    <?php if (!empty($sub['billing_notes'])): ?>
      <div class="mt-6 p-4 rounded-xl bg-slate-900/70 border border-slate-800">
        <div class="text-xs uppercase text-slate-500 tracking-wider mb-2">Notas</div>
        <div class="text-sm text-slate-200 whitespace-pre-line"><?= e($sub['billing_notes']) ?></div>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="px-5 py-4 border-b border-slate-800/60 flex items-center justify-between">
      <h3 class="text-sm font-semibold text-white">Pagos asociados</h3>
      <?php if (can('payments.create')): ?>
        <a href="/payments/new?institution_id=<?= (int)$sub['institution_id'] ?>&subscription_id=<?= (int)$sub['id'] ?>" class="text-xs text-brand-300 hover:text-brand-200">+ Nuevo</a>
      <?php endif; ?>
    </div>
    <ul class="divide-y divide-slate-800/60">
      <?php if (empty($payments)): ?>
        <li class="px-5 py-6 text-center text-sm text-slate-500">Sin pagos registrados.</li>
      <?php else: foreach ($payments as $p): ?>
        <li class="px-5 py-3 flex items-center gap-3">
          <div class="flex-1 min-w-0">
            <a href="/payments/<?= (int)$p['id'] ?>" class="text-sm text-slate-100 hover:text-brand-300 tabular-nums"><?= e(format_money($p['amount'], $p['currency_code'])) ?></a>
            <div class="text-xs text-slate-500"><?= format_date($p['payment_date']) ?> · <?= e($p['payment_method'] ?? '—') ?></div>
          </div>
          <?php $status = $p['status']; include dirname(__DIR__, 2) . '/components/status_badge.php'; ?>
        </li>
      <?php endforeach; endif; ?>
    </ul>
  </div>
</div>
<?php $content = ob_get_clean(); $title = 'Suscripción #' . $id; include dirname(__DIR__, 2) . '/layouts/main.php';
