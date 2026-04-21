<?php
declare(strict_types=1);

require_auth();
require_permission('audit.view');

$query = [];
$allowedFilters = ['search', 'action', 'entity', 'entity_id', 'actor_user_id', 'from', 'to', 'sort', 'order', 'page', 'limit'];
foreach ($allowedFilters as $k) {
    if (isset($_GET[$k]) && $_GET[$k] !== '') $query[$k] = $_GET[$k];
}
$query['limit'] = $query['limit'] ?? 20;

$logs = [];
$pagination = ['page' => 1, 'limit' => 20, 'total' => 0, 'pages' => 0];
try {
    $res = api_get('/audit-logs', ['query' => $query]);
    $logs = $res['data'] ?? [];
    $pagination = $res['meta']['pagination'] ?? $pagination;
} catch (ApiClientException $e) {
    flash_set('error', 'No se pudieron cargar los eventos de auditoría: ' . $e->getMessage());
}

$entityLabels = [
    'institutions'    => 'Instituciones',
    'subscriptions'   => 'Suscripciones',
    'payments'        => 'Pagos',
    'plans'           => 'Planes',
    'modules_catalog' => 'Catálogo de módulos',
    'leads'           => 'Solicitudes',
    'auth'            => 'Autenticación',
];

// Quick-filter chips (entity shortcuts). Preserve the current entity_id when someone
// lands here pre-filtered from another module.
$currentEntity = $_GET['entity'] ?? '';
$keepParams = [];
foreach (['entity_id', 'actor_user_id', 'from', 'to'] as $k) {
    if (!empty($_GET[$k])) $keepParams[$k] = $_GET[$k];
}
function audit_chip_url(string $entity, array $keep): string {
    $q = $keep;
    if ($entity !== '') $q['entity'] = $entity;
    return '/audit' . ($q ? '?' . http_build_query($q) : '');
}

function action_badge_class(string $action): string {
    if (str_contains($action, 'login_failed') || str_contains($action, 'failed')) return 'bg-rose-500/15 text-rose-300 border border-rose-500/30';
    if (str_contains($action, 'deleted') || str_contains($action, 'removed')) return 'bg-rose-500/10 text-rose-200 border border-rose-500/20';
    if (str_contains($action, 'created'))   return 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30';
    if (str_contains($action, 'updated'))   return 'bg-sky-500/10 text-sky-300 border border-sky-500/30';
    if (str_contains($action, 'status'))    return 'bg-amber-500/10 text-amber-300 border border-amber-500/30';
    if (str_contains($action, 'login'))     return 'bg-brand-500/10 text-brand-300 border border-brand-500/30';
    return 'bg-slate-800 text-slate-200 border border-slate-700';
}

ob_start();
?>
<?php
  $title = 'Auditoría';
  $subtitle = 'Registro de cambios y eventos sensibles del CRM.';
  $breadcrumbs = [['label' => 'Dashboard', 'href' => '/dashboard'], ['label' => 'Auditoría']];
  $actionsHtml = '';
  if (can('exports.audit')) {
    $exportQuery = $query; unset($exportQuery['page'], $exportQuery['limit']);
    $exportHref = '/audit/export.csv' . ($exportQuery ? '?' . http_build_query($exportQuery) : '');
    $actionsHtml = '<a href="' . e($exportHref) . '" class="btn-secondary inline-flex items-center gap-1.5 h-9 text-sm">'
      . '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>'
      . 'Exportar CSV</a>';
  }
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>

<!-- Quick entity chips -->
<div class="mb-4 flex items-center gap-2 flex-wrap">
  <?php
    $chips = ['' => 'Todos'] + $entityLabels;
    foreach ($chips as $key => $label):
      $active = $currentEntity === $key;
  ?>
    <a href="<?= e(audit_chip_url((string)$key, $keepParams)) ?>"
       class="px-3 py-1.5 text-xs rounded-full border <?= $active ? 'bg-brand-500/15 text-brand-200 border-brand-500/40' : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-slate-200' ?>">
      <?= e($label) ?>
    </a>
  <?php endforeach; ?>
  <?php if (!empty($_GET['entity_id']) && $currentEntity): ?>
    <span class="ml-2 text-xs text-slate-500">Filtrado por <?= e($entityLabels[$currentEntity] ?? $currentEntity) ?> #<?= e($_GET['entity_id']) ?></span>
    <a href="/audit" class="text-xs text-brand-300 hover:text-brand-200 ml-1">limpiar</a>
  <?php endif; ?>
</div>

<form method="get" class="card p-4 mb-4">
  <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
    <div class="md:col-span-2">
      <label class="text-xs text-slate-500">Buscar</label>
      <input type="text" name="search" value="<?= e($_GET['search'] ?? '') ?>" placeholder="Acción, descripción o entidad…" class="input h-10">
    </div>
    <div>
      <label class="text-xs text-slate-500">Entidad</label>
      <select name="entity" class="input h-10">
        <option value="">Todas</option>
        <?php foreach ($entityLabels as $k => $label): ?>
          <option value="<?= e($k) ?>" <?= (($_GET['entity'] ?? '') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-xs text-slate-500">ID entidad</label>
      <input type="text" name="entity_id" value="<?= e($_GET['entity_id'] ?? '') ?>" class="input h-10" placeholder="Ej. 42">
    </div>
    <div>
      <label class="text-xs text-slate-500">Usuario (ID)</label>
      <input type="number" name="actor_user_id" value="<?= e($_GET['actor_user_id'] ?? '') ?>" class="input h-10">
    </div>
    <div>
      <label class="text-xs text-slate-500">Acción</label>
      <input type="text" name="action" value="<?= e($_GET['action'] ?? '') ?>" class="input h-10" placeholder="Ej. institution.updated">
    </div>
    <div>
      <label class="text-xs text-slate-500">Desde</label>
      <input type="date" name="from" value="<?= e($_GET['from'] ?? '') ?>" class="input h-10">
    </div>
    <div>
      <label class="text-xs text-slate-500">Hasta</label>
      <input type="date" name="to" value="<?= e($_GET['to'] ?? '') ?>" class="input h-10">
    </div>
    <div class="md:col-span-2 flex items-end gap-2">
      <button class="btn-primary h-10">Filtrar</button>
      <a href="/audit" class="btn-secondary h-10 inline-flex items-center">Limpiar</a>
    </div>
  </div>
</form>

<div class="card overflow-hidden">
  <div class="overflow-x-auto">
    <table class="data-table">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Usuario</th>
          <th>Acción</th>
          <th>Entidad</th>
          <th>Descripción</th>
          <th>IP</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
          <tr>
            <td colspan="7" class="text-center py-12">
              <div class="text-slate-300 font-medium">Sin eventos</div>
              <div class="text-xs text-slate-500 mt-1">No hay registros de auditoría con los filtros actuales.</div>
              <a href="/audit" class="inline-block mt-3 text-xs text-brand-300 hover:text-brand-200">Limpiar filtros</a>
            </td>
          </tr>
        <?php endif; ?>
        <?php foreach ($logs as $log):
          $badgeClass = action_badge_class((string)($log['action'] ?? ''));
        ?>
          <tr>
            <td class="tabular-nums text-slate-300 whitespace-nowrap"><?= format_datetime($log['created_at'] ?? null) ?></td>
            <td>
              <?php if (!empty($log['actor_name'])): ?>
                <div class="text-slate-100 text-sm"><?= e($log['actor_name']) ?></div>
                <div class="text-xs text-slate-500"><?= e($log['actor_email'] ?? '') ?></div>
              <?php else: ?>
                <span class="text-xs text-slate-500">Sistema</span>
              <?php endif; ?>
            </td>
            <td><span class="font-mono text-[11px] px-2 py-1 rounded <?= $badgeClass ?>"><?= e($log['action']) ?></span></td>
            <td>
              <div class="text-slate-300"><?= e($entityLabels[$log['entity']] ?? $log['entity']) ?></div>
              <?php if (!empty($log['entity_id'])): ?>
                <div class="text-xs text-slate-500 font-mono">#<?= e($log['entity_id']) ?></div>
              <?php endif; ?>
            </td>
            <td class="max-w-md">
              <span class="text-slate-200 text-sm"><?= e(mb_strimwidth((string)($log['description'] ?? ''), 0, 140, '…')) ?></span>
            </td>
            <td class="text-xs text-slate-500 font-mono"><?= e($log['ip'] ?? '—') ?></td>
            <td class="text-right"><a href="/audit/<?= (int)$log['id'] ?>" class="text-xs text-brand-300 hover:text-brand-200">Ver →</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="mt-4">
  <?php
    $baseUrl = '/audit';
    $currentQuery = $query;
    unset($currentQuery['page']);
    include dirname(__DIR__, 2) . '/components/pagination.php';
  ?>
</div>

<?php
$content = ob_get_clean();
$title = 'Auditoría';
include dirname(__DIR__, 2) . '/layouts/main.php';
