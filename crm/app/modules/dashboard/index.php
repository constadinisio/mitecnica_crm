<?php
declare(strict_types=1);

require_auth();
require_permission('dashboard.view');

$summary = [
    'counts' => ['total' => 0, 'by_status' => [], 'by_technical_status' => []],
    'upcoming_expirations' => [],
    'recent_institutions' => [],
    'recent_activity' => [],
];

try {
    $res = api_get('/dashboard/summary');
    $summary = $res['data'] ?? $summary;
} catch (ApiClientException $e) {
    flash_set('error', 'No se pudieron cargar los datos del dashboard: ' . $e->getMessage());
}

$counts = $summary['counts'] ?? [];
$byStatus = $counts['by_status'] ?? [];
$byTech = $counts['by_technical_status'] ?? [];

ob_start();
?>

<?php
  $title = 'Dashboard ejecutivo';
  $subtitle = 'Resumen comercial y técnico de la red de instituciones.';
  $breadcrumbs = [['label' => 'Inicio'], ['label' => 'Dashboard']];
  $actionsHtml = '<a href="/institutions/new" class="btn-primary inline-flex">+ Nueva institución</a>';
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>

<section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
  <?php
    $label = 'Total instituciones'; $value = (int)($counts['total'] ?? 0); $accent = 'brand';
    $hint = 'Red completa bajo gestión';
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15a.75.75 0 01.75.75V21H3.75V3.75A.75.75 0 014.5 3z" /></svg>';
    include dirname(__DIR__, 2) . '/components/stat_card.php';
  ?>
  <?php
    $label = 'Activas'; $value = (int)($byStatus['active'] ?? 0); $accent = 'emerald';
    $hint = 'Clientes con plan vigente';
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>';
    include dirname(__DIR__, 2) . '/components/stat_card.php';
  ?>
  <?php
    $label = 'En trial'; $value = (int)($byStatus['trial'] ?? 0); $accent = 'amber';
    $hint = 'Período de prueba abierto';
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4.5 2.25" /><circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round" /></svg>';
    include dirname(__DIR__, 2) . '/components/stat_card.php';
  ?>
  <?php
    $label = 'Suspendidas / Expiradas';
    $value = (int)($byStatus['suspended'] ?? 0) + (int)($byStatus['expired'] ?? 0);
    $accent = 'rose';
    $hint = 'Requieren acción comercial';
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>';
    include dirname(__DIR__, 2) . '/components/stat_card.php';
  ?>
</section>

<section class="mt-6 grid grid-cols-1 xl:grid-cols-3 gap-4">
  <div class="xl:col-span-2 card">
    <div class="px-5 py-4 border-b border-slate-800/60 flex items-center justify-between">
      <div>
        <h3 class="text-sm font-semibold text-white">Próximas expiraciones</h3>
        <p class="text-xs text-slate-400">Renovaciones dentro de los próximos 30 días.</p>
      </div>
      <a href="/institutions?sort=expiration_date&order=asc" class="text-xs text-brand-300 hover:text-brand-200">Ver todas →</a>
    </div>
    <div class="overflow-x-auto">
      <table class="data-table">
        <thead>
          <tr>
            <th>Institución</th>
            <th>Plan</th>
            <th>Vence</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($summary['upcoming_expirations'])): ?>
            <tr><td colspan="4" class="text-center text-slate-500 py-8">Sin expiraciones próximas</td></tr>
          <?php else: foreach ($summary['upcoming_expirations'] as $ins):
            $days = days_until($ins['expiration_date'] ?? null);
          ?>
            <tr>
              <td>
                <a class="text-white hover:text-brand-300" href="/institutions/<?= (int)$ins['id'] ?>"><?= e($ins['name']) ?></a>
                <div class="text-xs text-slate-500"><?= e($ins['public_code']) ?></div>
              </td>
              <td class="text-slate-300"><?= e($ins['current_plan_name'] ?? '—') ?></td>
              <td>
                <div class="text-slate-200"><?= format_date($ins['expiration_date'] ?? null) ?></div>
                <?php if ($days !== null): ?>
                  <div class="text-xs <?= $days < 0 ? 'text-rose-400' : ($days <= 7 ? 'text-amber-300' : 'text-slate-500') ?>">
                    <?= $days < 0 ? 'Vencido hace ' . abs($days) . ' d' : 'En ' . $days . ' d' ?>
                  </div>
                <?php endif; ?>
              </td>
              <td><?php $status = $ins['status']; include dirname(__DIR__, 2) . '/components/status_badge.php'; ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="px-5 py-4 border-b border-slate-800/60">
      <h3 class="text-sm font-semibold text-white">Actividad reciente</h3>
      <p class="text-xs text-slate-400">Últimos eventos auditados del CRM.</p>
    </div>
    <ul class="divide-y divide-slate-800/60">
      <?php if (empty($summary['recent_activity'])): ?>
        <li class="px-5 py-6 text-center text-sm text-slate-500">Sin actividad reciente</li>
      <?php else: foreach (array_slice($summary['recent_activity'], 0, 8) as $a): ?>
        <li class="px-5 py-3 flex items-start gap-3">
          <span class="mt-1 h-2 w-2 rounded-full bg-brand-400"></span>
          <div class="flex-1 min-w-0">
            <div class="text-sm text-slate-200 truncate"><?= e($a['description'] ?? $a['action']) ?></div>
            <div class="text-xs text-slate-500">
              <?= e($a['actor_name'] ?? 'Sistema') ?> · <?= e(format_relative($a['created_at'] ?? null)) ?>
            </div>
          </div>
        </li>
      <?php endforeach; endif; ?>
    </ul>
  </div>
</section>

<section class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
  <div class="card">
    <div class="px-5 py-4 border-b border-slate-800/60 flex items-center justify-between">
      <div>
        <h3 class="text-sm font-semibold text-white">Altas recientes</h3>
        <p class="text-xs text-slate-400">Últimas instituciones dadas de alta.</p>
      </div>
      <a href="/institutions?sort=created_at&order=desc" class="text-xs text-brand-300 hover:text-brand-200">Ver todas →</a>
    </div>
    <ul class="divide-y divide-slate-800/60">
      <?php if (empty($summary['recent_institutions'])): ?>
        <li class="px-5 py-6 text-center text-sm text-slate-500">Sin altas recientes</li>
      <?php else: foreach (array_slice($summary['recent_institutions'], 0, 6) as $ins): ?>
        <li class="px-5 py-3 flex items-center gap-3">
          <div class="h-8 w-8 rounded-lg bg-slate-800 border border-slate-700 grid place-items-center text-xs text-slate-300 font-semibold"><?= e(initials($ins['name'])) ?></div>
          <div class="flex-1 min-w-0">
            <a href="/institutions/<?= (int)$ins['id'] ?>" class="text-sm text-white hover:text-brand-300 truncate block"><?= e($ins['name']) ?></a>
            <div class="text-xs text-slate-500"><?= e($ins['public_code']) ?> · <?= e($ins['subdomain']) ?></div>
          </div>
          <?php $status = $ins['status']; include dirname(__DIR__, 2) . '/components/status_badge.php'; ?>
        </li>
      <?php endforeach; endif; ?>
    </ul>
  </div>

  <div class="card">
    <div class="px-5 py-4 border-b border-slate-800/60">
      <h3 class="text-sm font-semibold text-white">Estado técnico (info de negocio)</h3>
      <p class="text-xs text-slate-400">Distribución simple del estado técnico reportado.</p>
    </div>
    <div class="p-5 grid grid-cols-2 gap-4">
      <?php
        $techBlocks = [
          ['key' => 'optimal', 'label' => 'Optimal',  'class' => 'bg-emerald-500/10 text-emerald-200 border-emerald-500/30'],
          ['key' => 'updating','label' => 'Updating', 'class' => 'bg-amber-500/10 text-amber-200 border-amber-500/30'],
          ['key' => 'pending', 'label' => 'Pending',  'class' => 'bg-slate-500/10 text-slate-200 border-slate-500/30'],
          ['key' => 'offline', 'label' => 'Offline',  'class' => 'bg-rose-500/10 text-rose-200 border-rose-500/30'],
        ];
        foreach ($techBlocks as $tb):
      ?>
        <div class="rounded-xl border <?= e($tb['class']) ?> px-4 py-3">
          <div class="text-xs uppercase tracking-wider opacity-80"><?= e($tb['label']) ?></div>
          <div class="mt-1 text-2xl font-semibold"><?= (int)($byTech[$tb['key']] ?? 0) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="px-5 pb-5 text-xs text-slate-500">
      * Estos valores son información de negocio reportada por la tenant app, no un panel de infraestructura.
    </div>
  </div>
</section>

<?php
$content = ob_get_clean();
$title = 'Dashboard';
include dirname(__DIR__, 2) . '/layouts/main.php';
