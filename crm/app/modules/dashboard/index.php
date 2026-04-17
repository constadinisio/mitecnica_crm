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

// Fase 2A: datos comerciales (opcionales — si fallan no rompen el dashboard)
$plansSummary = null;
$subsSummary = null;
$paySummary = null;
$leadsSummary = null;
try { $plansSummary = api_get('/plans/summary')['data'] ?? null; } catch (Throwable) {}
try { $subsSummary = api_get('/subscriptions/summary')['data'] ?? null; } catch (Throwable) {}
try { $paySummary = api_get('/payments/summary')['data'] ?? null; } catch (Throwable) {}
try { $leadsSummary = api_get('/leads/summary')['data'] ?? null; } catch (Throwable) {}

function arsApprovedTotal30d(?array $ps): float {
    if (!$ps) return 0.0;
    $totals = $ps['totals_last_30d'] ?? [];
    $sum = 0.0;
    foreach ($totals as $t) {
        if (($t['status'] ?? null) === 'approved' && strtoupper($t['currency_code'] ?? '') === 'ARS') {
            $sum += (float) ($t['amount'] ?? 0);
        }
    }
    return $sum;
}
$arsApproved30d = arsApprovedTotal30d($paySummary);

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

<?php if ($leadsSummary && (int)($leadsSummary['counts']['open'] ?? 0) > 0): ?>
<section class="mt-4">
  <a href="/leads?status=new" class="card p-4 flex items-center justify-between gap-4 border-brand-500/30 bg-brand-500/5 hover:bg-brand-500/10 transition">
    <div class="flex items-center gap-3">
      <span class="h-10 w-10 rounded-xl bg-brand-500/15 text-brand-300 grid place-items-center border border-brand-500/30">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.068.157 2.148.279 3.238.364.466.037.893.281 1.153.671L12 21l2.652-3.978c.26-.39.687-.634 1.153-.67 1.09-.086 2.17-.208 3.238-.365 1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg>
      </span>
      <div>
        <div class="text-sm font-semibold text-white">
          Tenés <?= (int)$leadsSummary['counts']['open'] ?> solicitud<?= $leadsSummary['counts']['open'] === 1 ? '' : 'es' ?> abierta<?= $leadsSummary['counts']['open'] === 1 ? '' : 's' ?>
        </div>
        <div class="text-xs text-slate-400">
          <?= (int)($leadsSummary['counts']['by_status']['new'] ?? 0) ?> nuevas ·
          <?= (int)($leadsSummary['counts']['by_status']['contacted'] ?? 0) ?> contactadas ·
          <?= (int)($leadsSummary['counts']['by_status']['in_negotiation'] ?? 0) ?> en negociación
        </div>
      </div>
    </div>
    <span class="text-sm text-brand-300">Ver solicitudes →</span>
  </a>
</section>
<?php endif; ?>

<!-- KPIs comerciales Fase 2A -->
<section class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
  <?php
    $label = 'Planes activos';
    $value = (int)($plansSummary['by_status']['active'] ?? 0);
    $accent = 'brand';
    $hint = 'Catálogo comercial vigente';
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H3.75A.75.75 0 003 3.75v5.818c0 .2.079.39.22.53l10.94 10.94a.75.75 0 001.06 0l5.819-5.819a.75.75 0 000-1.06L10.098 3.22A.75.75 0 009.568 3zM6 6h.008v.008H6V6z" /></svg>';
    include dirname(__DIR__, 2) . '/components/stat_card.php';
  ?>
  <?php
    $label = 'Suscripciones vivas';
    $value = (int)($subsSummary['counts']['live'] ?? 0);
    $accent = 'emerald';
    $hint = 'Trial + activas';
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356M4.05 10.5A7.5 7.5 0 0117.543 7.5M3.382 15.402l4.992-.006v4.992M19.95 13.5a7.5 7.5 0 01-13.493 3" /></svg>';
    include dirname(__DIR__, 2) . '/components/stat_card.php';
  ?>
  <?php
    $label = 'Cobrado (últimos 30 días)';
    $value = format_money($arsApproved30d, 'ARS');
    $accent = 'emerald';
    $hint = 'Pagos aprobados en ARS';
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9v9a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9M2.25 9V6a2.25 2.25 0 012.25-2.25h15A2.25 2.25 0 0121.75 6v3M5.25 15h3v1.5h-3V15z" /></svg>';
    include dirname(__DIR__, 2) . '/components/stat_card.php';
  ?>
  <?php
    $label = 'Pagos pendientes';
    $value = (int)($paySummary['counts']['by_status']['pending'] ?? 0);
    $accent = 'amber';
    $hint = 'Por conciliar / aprobar';
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4.5 2.25" /><circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round" /></svg>';
    include dirname(__DIR__, 2) . '/components/stat_card.php';
  ?>
</section>

<?php if (!empty($subsSummary['upcoming_renewals']) || !empty($paySummary['recent'])): ?>
<section class="mt-6 grid grid-cols-1 xl:grid-cols-2 gap-4">
  <div class="card">
    <div class="px-5 py-4 border-b border-slate-800/60 flex items-center justify-between">
      <div>
        <h3 class="text-sm font-semibold text-white">Renovaciones próximas</h3>
        <p class="text-xs text-slate-400">Suscripciones que vencen en los próximos 30 días.</p>
      </div>
      <a href="/subscriptions?sort=end_date&order=asc" class="text-xs text-brand-300 hover:text-brand-200">Ver todas →</a>
    </div>
    <ul class="divide-y divide-slate-800/60">
      <?php if (empty($subsSummary['upcoming_renewals'])): ?>
        <li class="px-5 py-6 text-center text-sm text-slate-500">Sin renovaciones próximas</li>
      <?php else: foreach (array_slice($subsSummary['upcoming_renewals'], 0, 6) as $s):
        $d = days_until($s['end_date'] ?? null);
      ?>
        <li class="px-5 py-3 flex items-center gap-3">
          <div class="flex-1 min-w-0">
            <a href="/subscriptions/<?= (int)$s['id'] ?>" class="text-sm text-white hover:text-brand-300 truncate block"><?= e($s['institution_name'] ?? '—') ?></a>
            <div class="text-xs text-slate-500"><?= e($s['plan_name'] ?? '') ?> · <?= e(format_money($s['plan_price_amount'] ?? 0, $s['plan_currency_code'] ?? 'ARS')) ?></div>
          </div>
          <div class="text-right">
            <div class="text-sm text-slate-300"><?= format_date($s['end_date'] ?? null) ?></div>
            <?php if ($d !== null): ?>
              <div class="text-xs <?= $d <= 7 ? 'text-amber-300' : 'text-slate-500' ?>">En <?= $d ?> d</div>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; endif; ?>
    </ul>
  </div>

  <div class="card">
    <div class="px-5 py-4 border-b border-slate-800/60 flex items-center justify-between">
      <div>
        <h3 class="text-sm font-semibold text-white">Pagos recientes</h3>
        <p class="text-xs text-slate-400">Últimos movimientos registrados.</p>
      </div>
      <a href="/payments" class="text-xs text-brand-300 hover:text-brand-200">Ver todos →</a>
    </div>
    <ul class="divide-y divide-slate-800/60">
      <?php if (empty($paySummary['recent'])): ?>
        <li class="px-5 py-6 text-center text-sm text-slate-500">Sin pagos registrados</li>
      <?php else: foreach (array_slice($paySummary['recent'], 0, 6) as $p): ?>
        <li class="px-5 py-3 flex items-center gap-3">
          <div class="flex-1 min-w-0">
            <a href="/payments/<?= (int)$p['id'] ?>" class="text-sm text-white hover:text-brand-300 truncate block"><?= e($p['institution_name'] ?? '—') ?></a>
            <div class="text-xs text-slate-500"><?= format_date($p['payment_date']) ?> · <?= e($p['payment_method'] ?? '—') ?></div>
          </div>
          <div class="text-right">
            <div class="text-sm text-slate-100 tabular-nums"><?= e(format_money($p['amount'], $p['currency_code'])) ?></div>
            <?php $status = $p['status']; include dirname(__DIR__, 2) . '/components/status_badge.php'; ?>
          </div>
        </li>
      <?php endforeach; endif; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

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

<section class="mt-6">
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
</section>

<?php
$content = ob_get_clean();
$title = 'Dashboard';
include dirname(__DIR__, 2) . '/layouts/main.php';
