<?php
declare(strict_types=1);

require_auth();
require_permission('audit.view');

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { http_response_code(404); echo 'Not found'; return; }

$log = null;
try {
    $res = api_get("/audit-logs/$id");
    $log = $res['data'] ?? null;
} catch (ApiClientException $e) {
    flash_set('error', $e->getMessage());
    redirect('/audit');
}
if (!$log) { http_response_code(404); echo 'Not found'; return; }

function render_json_block(mixed $value): string {
    if ($value === null || $value === '') return '<span class="text-slate-500">—</span>';
    $pretty = is_string($value) ? $value : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return '<pre class="text-xs text-slate-200 font-mono whitespace-pre-wrap break-words">' . htmlspecialchars((string)$pretty) . '</pre>';
}

ob_start();
?>
<?php
  $title = 'Evento #' . (int)$log['id'];
  $subtitle = $log['action'] . ' · ' . ($log['entity'] ?? '');
  $breadcrumbs = [
    ['label' => 'Dashboard', 'href' => '/dashboard'],
    ['label' => 'Auditoría', 'href' => '/audit'],
    ['label' => '#' . (int)$log['id']],
  ];
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Acción</div>
    <div class="mt-2 font-mono text-slate-100"><?= e($log['action']) ?></div>
    <div class="mt-2 text-xs text-slate-500">Entidad: <span class="text-slate-300"><?= e($log['entity']) ?></span>
      <?php if (!empty($log['entity_id'])): ?>
        · <span class="font-mono text-slate-300">#<?= e($log['entity_id']) ?></span>
        <?php if ($log['entity'] === 'institutions'): ?>
          <a href="/institutions/<?= e($log['entity_id']) ?>" class="ml-2 text-brand-300 hover:text-brand-200">→ Ver</a>
        <?php elseif ($log['entity'] === 'subscriptions'): ?>
          <a href="/subscriptions/<?= e($log['entity_id']) ?>" class="ml-2 text-brand-300 hover:text-brand-200">→ Ver</a>
        <?php elseif ($log['entity'] === 'payments'): ?>
          <a href="/payments/<?= e($log['entity_id']) ?>" class="ml-2 text-brand-300 hover:text-brand-200">→ Ver</a>
        <?php elseif ($log['entity'] === 'plans'): ?>
          <a href="/plans/<?= e($log['entity_id']) ?>" class="ml-2 text-brand-300 hover:text-brand-200">→ Ver</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Usuario</div>
    <div class="mt-2 text-slate-100"><?= e($log['actor_name'] ?? 'Sistema') ?></div>
    <div class="text-xs text-slate-500"><?= e($log['actor_email'] ?? '') ?></div>
    <div class="mt-3 text-xs text-slate-500">ID: <?= e($log['actor_user_id'] ?? '—') ?></div>
  </div>
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Metadata</div>
    <div class="mt-2 text-sm text-slate-300"><?= format_datetime($log['created_at'] ?? null) ?></div>
    <div class="text-xs text-slate-500 mt-1">IP: <span class="font-mono"><?= e($log['ip'] ?? '—') ?></span></div>
    <div class="text-xs text-slate-500 mt-1 break-all">UA: <span class="font-mono"><?= e($log['user_agent'] ?? '—') ?></span></div>
  </div>
</div>

<?php if (!empty($log['description'])): ?>
  <div class="card p-5 mb-4">
    <div class="text-xs uppercase tracking-wider text-slate-500 mb-2">Descripción</div>
    <div class="text-slate-100 text-sm whitespace-pre-line"><?= e($log['description']) ?></div>
  </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500 mb-2">Estado anterior</div>
    <?= render_json_block($log['before_data'] ?? null) ?>
  </div>
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500 mb-2">Estado posterior</div>
    <?= render_json_block($log['after_data'] ?? null) ?>
  </div>
</div>

<?php
$content = ob_get_clean();
$title = 'Evento #' . (int)$log['id'];
include dirname(__DIR__, 2) . '/layouts/main.php';
