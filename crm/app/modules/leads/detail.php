<?php
declare(strict_types=1);

require_auth();
require_permission('leads.view');

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { http_response_code(404); echo 'Not found'; return; }

$lead = null;
try {
    $lead = api_get("/leads/$id")['data'] ?? null;
} catch (ApiClientException $e) {
    flash_set('error', $e->getMessage());
    redirect('/leads');
}
if (!$lead) { http_response_code(404); echo 'Not found'; return; }

$me = current_user();
$isTerminal = in_array($lead['status'], ['converted', 'lost'], true);

ob_start();
?>
<?php
  $title = $lead['institution_name'];
  $subtitle = 'Solicitud #' . $id . ' · ' . $lead['contact_name'];
  $breadcrumbs = [['label' => 'Dashboard', 'href' => '/dashboard'], ['label' => 'Solicitudes', 'href' => '/leads'], ['label' => '#' . $id]];
  $actionsHtml = '';
  if (!$isTerminal && can('leads.convert')) {
      $actionsHtml .= '<a href="/leads/' . $id . '/convert" class="btn-primary inline-flex">Convertir a cliente</a>';
  }
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>

<section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Estado</div>
    <div class="mt-2"><?php $status = $lead['status']; include dirname(__DIR__, 2) . '/components/status_badge.php'; ?></div>

    <?php if (!$isTerminal && can('leads.change_status')): ?>
      <form method="post" action="/leads/<?= $id ?>/status" class="mt-3 flex items-center gap-2">
        <?= csrf_field() ?>
        <select name="status" class="input h-9 text-sm flex-1">
          <?php foreach (['contacted' => 'Contactada', 'in_negotiation' => 'En negociación', 'lost' => 'Perdida'] as $v => $l): ?>
            <?php if ($v === $lead['status']) continue; ?>
            <option value="<?= e($v) ?>"><?= e($l) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn-secondary h-9 px-3 text-sm">Cambiar</button>
      </form>
    <?php endif; ?>
  </div>

  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Asignada a</div>
    <div class="mt-2 text-slate-100"><?= e($lead['assigned_to_name'] ?? 'Sin asignar') ?></div>
    <?php if (!$isTerminal && can('leads.assign')): ?>
      <div class="mt-3 flex items-center gap-2">
        <?php if ($me && ($lead['assigned_to_user_id'] ?? null) !== $me['id']): ?>
          <form method="post" action="/leads/<?= $id ?>/assign" class="flex-1"><?= csrf_field() ?>
            <input type="hidden" name="user_id" value="<?= (int)$me['id'] ?>">
            <button class="btn-secondary h-9 px-3 text-sm w-full">Asignarme</button>
          </form>
        <?php endif; ?>
        <?php if (!empty($lead['assigned_to_user_id'])): ?>
          <form method="post" action="/leads/<?= $id ?>/assign"><?= csrf_field() ?>
            <input type="hidden" name="user_id" value="">
            <button class="btn-ghost h-9 px-3 text-sm">Liberar</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Plan solicitado</div>
    <div class="mt-2 text-lg font-semibold text-white"><?= e($lead['plan_code_requested'] ?? 'Sin definir') ?></div>
    <div class="text-xs text-slate-500 mt-1">Recibida: <?= format_datetime($lead['created_at']) ?></div>
  </div>
</section>

<?php if ($isTerminal && !empty($lead['converted_institution_code'])): ?>
  <div class="card p-4 mb-6 border-emerald-500/30 bg-emerald-500/5">
    <div class="flex items-center gap-3">
      <span class="h-8 w-8 rounded-full bg-emerald-500/15 text-emerald-300 grid place-items-center text-sm">✓</span>
      <div class="flex-1">
        <div class="text-sm text-emerald-200 font-medium">Solicitud convertida a cliente</div>
        <div class="text-xs text-slate-400">
          <a href="/institutions/<?= (int)$lead['converted_institution_id'] ?>" class="text-brand-300 hover:text-brand-200"><?= e($lead['converted_institution_code']) ?> — <?= e($lead['converted_institution_name']) ?></a>
          <?php if (!empty($lead['converted_subscription_id'])): ?>
            · <a href="/subscriptions/<?= (int)$lead['converted_subscription_id'] ?>" class="text-brand-300 hover:text-brand-200">suscripción #<?= (int)$lead['converted_subscription_id'] ?></a>
          <?php endif; ?>
          · <?= format_datetime($lead['converted_at']) ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<section class="grid grid-cols-1 lg:grid-cols-2 gap-4">
  <div class="card p-6">
    <h3 class="text-sm font-semibold text-white mb-4">Datos de la solicitud</h3>
    <dl class="space-y-3 text-sm">
      <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Institución</dt><dd class="mt-1 text-slate-100"><?= e($lead['institution_name']) ?></dd></div>
      <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Contacto</dt><dd class="mt-1 text-slate-100"><?= e($lead['contact_name']) ?></dd></div>
      <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Email</dt><dd class="mt-1"><a href="mailto:<?= e($lead['contact_email']) ?>" class="text-brand-300 hover:text-brand-200"><?= e($lead['contact_email']) ?></a></dd></div>
      <?php if (!empty($lead['contact_phone'])): ?>
        <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Teléfono</dt><dd class="mt-1 text-slate-200"><?= e($lead['contact_phone']) ?></dd></div>
      <?php endif; ?>
      <?php if (!empty($lead['address'])): ?>
        <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Dirección</dt><dd class="mt-1 text-slate-200"><?= e($lead['address']) ?></dd></div>
      <?php endif; ?>
    </dl>
    <?php if (!empty($lead['notes'])): ?>
      <div class="mt-6 p-4 rounded-xl bg-slate-900/70 border border-slate-800">
        <div class="text-xs uppercase text-slate-500 tracking-wider mb-2">Mensaje</div>
        <div class="text-sm text-slate-200 whitespace-pre-line"><?= e($lead['notes']) ?></div>
      </div>
    <?php endif; ?>
  </div>

  <div class="card p-6">
    <h3 class="text-sm font-semibold text-white mb-4">Metadata</h3>
    <dl class="space-y-3 text-sm">
      <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Origen</dt><dd class="mt-1 text-slate-200"><?= e($lead['source'] ?? '—') ?></dd></div>
      <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Recibida</dt><dd class="mt-1 text-slate-200"><?= format_datetime($lead['created_at']) ?></dd></div>
      <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Última actualización</dt><dd class="mt-1 text-slate-200"><?= format_relative($lead['updated_at']) ?></dd></div>
      <?php if (!empty($lead['ip'])): ?>
        <div><dt class="text-xs uppercase text-slate-500 tracking-wider">IP</dt><dd class="mt-1 font-mono text-xs text-slate-400"><?= e($lead['ip']) ?></dd></div>
      <?php endif; ?>
      <?php if (!empty($lead['user_agent'])): ?>
        <div><dt class="text-xs uppercase text-slate-500 tracking-wider">User agent</dt><dd class="mt-1 font-mono text-xs text-slate-500 break-all"><?= e($lead['user_agent']) ?></dd></div>
      <?php endif; ?>
    </dl>
  </div>
</section>

<?php $content = ob_get_clean(); $title = 'Solicitud #' . $id; include dirname(__DIR__, 2) . '/layouts/main.php';
