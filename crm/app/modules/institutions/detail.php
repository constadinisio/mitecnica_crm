<?php
declare(strict_types=1);

require_auth();
require_permission('institutions.view');

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { http_response_code(404); echo 'Not found'; return; }

$institution = null;
$audit = [];
try {
    $res = api_get("/institutions/$id");
    $institution = $res['data']['institution'] ?? null;
    $audit       = $res['data']['audit'] ?? [];
} catch (ApiClientException $e) {
    flash_set('error', $e->getMessage());
    redirect('/institutions');
}
if (!$institution) { http_response_code(404); echo 'Not found'; return; }

$activeTab = $_GET['tab'] ?? 'general';
$tabs = [
  'general'    => 'General',
  'commercial' => 'Comercial',
  'domains'    => 'Dominios',
  'audit'      => 'Auditoría',
];

ob_start();
?>

<?php
  $title = $institution['name'];
  $subtitle = $institution['public_code'] . ' · ' . $institution['subdomain'];
  $breadcrumbs = [
    ['label' => 'Dashboard', 'href' => '/dashboard'],
    ['label' => 'Instituciones', 'href' => '/institutions'],
    ['label' => $institution['public_code']],
  ];
  $actionsHtml = '';
  if (can('institutions.update')) {
    $actionsHtml .= '<a href="/institutions/' . (int)$institution['id'] . '/edit" class="btn-secondary inline-flex">Editar</a>';
  }
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>

<section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Estado</div>
    <div class="mt-2">
      <?php $status = $institution['status']; include dirname(__DIR__, 2) . '/components/status_badge.php'; ?>
    </div>
    <?php if (can('institutions.change_status')): ?>
      <form method="post" action="/institutions/<?= (int)$institution['id'] ?>/status" class="mt-3 flex items-center gap-2">
        <?= csrf_field() ?>
        <select name="status" class="input h-9 text-sm flex-1">
          <?php foreach (['trial','active','maintenance','suspended','expired','inactive'] as $s): ?>
            <option value="<?= e($s) ?>" <?= $s === $institution['status'] ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn-secondary h-9 px-3 text-sm">Cambiar</button>
      </form>
    <?php endif; ?>
  </div>
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Plan</div>
    <div class="mt-2 text-lg font-semibold text-white"><?= e($institution['current_plan_name'] ?? 'Sin plan asignado') ?></div>
    <div class="text-xs text-slate-500 mt-1">Vence: <?= format_date($institution['expiration_date'] ?? null) ?></div>
  </div>
  <div class="card p-5">
    <div class="text-xs uppercase tracking-wider text-slate-500">Última actividad</div>
    <div class="mt-2 text-lg font-semibold text-white"><?= e(format_relative($institution['last_activity_at'] ?? null)) ?></div>
    <div class="text-xs text-slate-500 mt-1"><?= e(format_datetime($institution['last_activity_at'] ?? null)) ?></div>
  </div>
</section>

<div class="card">
  <div class="border-b border-slate-800/60 px-2 flex items-center gap-1 overflow-x-auto">
    <?php foreach ($tabs as $key => $label): ?>
      <a href="?tab=<?= e($key) ?>"
         class="px-4 py-3 text-sm whitespace-nowrap border-b-2 <?= $activeTab === $key ? 'border-brand-500 text-white' : 'border-transparent text-slate-400 hover:text-white' ?>"
         data-tab="<?= e($key) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
  <div class="p-6">
    <?php if ($activeTab === 'general'):    include __DIR__ . '/partials/tabs_general.php';    endif; ?>
    <?php if ($activeTab === 'commercial'): include __DIR__ . '/partials/tabs_commercial.php'; endif; ?>
    <?php if ($activeTab === 'domains'):    include __DIR__ . '/partials/tabs_domains.php';    endif; ?>
    <?php if ($activeTab === 'audit'):      include __DIR__ . '/partials/tabs_audit.php';      endif; ?>
  </div>
</div>

<?php
$content = ob_get_clean();
$title = $institution['name'];
include dirname(__DIR__, 2) . '/layouts/main.php';
