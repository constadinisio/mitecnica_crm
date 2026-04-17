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

// Known entity labels for the filter select.
$entityLabels = [
    'institutions'    => 'Instituciones',
    'subscriptions'   => 'Suscripciones',
    'payments'        => 'Pagos',
    'plans'           => 'Planes',
    'modules_catalog' => 'Catálogo de módulos',
    'leads'           => 'Solicitudes',
    'auth'            => 'Autenticación',
];

ob_start();
?>
<?php
  $title = 'Auditoría';
  $subtitle = 'Registro de cambios y eventos sensibles del CRM.';
  $breadcrumbs = [['label' => 'Dashboard', 'href' => '/dashboard'], ['label' => 'Auditoría']];
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>

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
          <tr><td colspan="7" class="text-center text-slate-500 py-10">No hay eventos registrados para los filtros actuales.</td></tr>
        <?php endif; ?>
        <?php foreach ($logs as $log): ?>
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
            <td><span class="font-mono text-xs px-2 py-1 rounded bg-slate-800 text-slate-200"><?= e($log['action']) ?></span></td>
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
