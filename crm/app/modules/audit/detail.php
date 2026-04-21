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
    if ($value === null || $value === '') return '<span class="text-slate-500 text-xs">Sin datos</span>';
    $pretty = is_string($value) ? $value : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return '<pre class="text-xs text-slate-200 font-mono whitespace-pre-wrap break-words">' . htmlspecialchars((string)$pretty) . '</pre>';
}

/**
 * Flatten a value into a map of dotted-path → primitive (or primitive-stringified).
 * Used to render a compact key/value diff between before and after.
 */
function flatten_map(mixed $value, string $prefix = ''): array {
    if (!is_array($value)) return $prefix === '' ? [] : [$prefix => $value];
    $out = [];
    $isList = array_keys($value) === range(0, count($value) - 1);
    foreach ($value as $k => $v) {
        $key = $prefix === '' ? (string)$k : ($isList ? $prefix . '[' . $k . ']' : $prefix . '.' . $k);
        $out += flatten_map($v, $key);
    }
    return $out;
}

function scalar_label(mixed $v): string {
    if ($v === null) return '∅';
    if (is_bool($v)) return $v ? 'true' : 'false';
    if (is_array($v)) return json_encode($v, JSON_UNESCAPED_UNICODE);
    return (string)$v;
}

$before = $log['before_data'] ?? null;
$after  = $log['after_data']  ?? null;
$hasAny = ($before !== null && $before !== '') || ($after !== null && $after !== '');
$diffRows = [];
if ($hasAny && (is_array($before) || is_null($before)) && (is_array($after) || is_null($after))) {
    $flatBefore = flatten_map($before ?? []);
    $flatAfter  = flatten_map($after ?? []);
    $keys = array_unique(array_merge(array_keys($flatBefore), array_keys($flatAfter)));
    sort($keys);
    foreach ($keys as $k) {
        $b = $flatBefore[$k] ?? null;
        $a = $flatAfter[$k]  ?? null;
        if ($b === $a) continue;
        $diffRows[] = ['key' => $k, 'before' => $b, 'after' => $a];
    }
}

function action_badge_detail(string $action): string {
    if (str_contains($action, 'login_failed') || str_contains($action, 'failed') || str_contains($action, 'deleted')) {
        return 'bg-rose-500/15 text-rose-300 border border-rose-500/30';
    }
    if (str_contains($action, 'created'))   return 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30';
    if (str_contains($action, 'updated'))   return 'bg-sky-500/10 text-sky-300 border border-sky-500/30';
    if (str_contains($action, 'status'))    return 'bg-amber-500/10 text-amber-300 border border-amber-500/30';
    if (str_contains($action, 'login'))     return 'bg-brand-500/10 text-brand-300 border border-brand-500/30';
    return 'bg-slate-800 text-slate-200 border border-slate-700';
}

$entityLabels = [
    'institutions' => 'Instituciones',
    'subscriptions' => 'Suscripciones',
    'payments' => 'Pagos',
    'plans' => 'Planes',
    'modules_catalog' => 'Catálogo de módulos',
    'leads' => 'Solicitudes',
    'auth' => 'Autenticación',
];

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

  // Quick action: filter audit by this actor or this entity.
  $quickActions = [];
  if (!empty($log['entity']) && !empty($log['entity_id'])) {
      $quickActions[] = '<a href="/audit?entity=' . e($log['entity']) . '&entity_id=' . e($log['entity_id']) . '" class="btn-secondary h-9 text-sm inline-flex items-center">Ver histórico de esta entidad</a>';
  }
  if (!empty($log['actor_user_id'])) {
      $quickActions[] = '<a href="/audit?actor_user_id=' . e($log['actor_user_id']) . '" class="btn-secondary h-9 text-sm inline-flex items-center">Ver actividad del usuario</a>';
  }
  $actionsHtml = implode(' ', $quickActions);
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Acción</div>
    <div class="mt-2">
      <span class="font-mono text-xs px-2 py-1 rounded <?= action_badge_detail((string)$log['action']) ?>"><?= e($log['action']) ?></span>
    </div>
    <div class="mt-3 text-xs text-slate-500">Entidad: <span class="text-slate-300"><?= e($entityLabels[$log['entity']] ?? $log['entity']) ?></span>
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

<?php if (!empty($diffRows)): ?>
  <div class="card mb-4">
    <div class="px-5 py-4 border-b border-slate-800/60">
      <h3 class="text-sm font-semibold text-white">Cambios detectados</h3>
      <p class="text-xs text-slate-400">Comparación campo por campo entre estado anterior y posterior.</p>
    </div>
    <div class="overflow-x-auto">
      <table class="data-table">
        <thead>
          <tr><th>Campo</th><th>Antes</th><th>Después</th></tr>
        </thead>
        <tbody>
          <?php foreach ($diffRows as $row): ?>
            <tr>
              <td class="font-mono text-xs text-slate-400 whitespace-nowrap"><?= e($row['key']) ?></td>
              <td class="text-xs text-rose-300 font-mono break-all"><?= e(scalar_label($row['before'])) ?></td>
              <td class="text-xs text-emerald-300 font-mono break-all"><?= e(scalar_label($row['after'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500 mb-2">Estado anterior (raw)</div>
    <?= render_json_block($log['before_data'] ?? null) ?>
  </div>
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500 mb-2">Estado posterior (raw)</div>
    <?= render_json_block($log['after_data'] ?? null) ?>
  </div>
</div>

<?php
$content = ob_get_clean();
$title = 'Evento #' . (int)$log['id'];
include dirname(__DIR__, 2) . '/layouts/main.php';
