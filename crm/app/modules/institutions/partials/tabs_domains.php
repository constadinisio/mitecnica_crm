<?php
declare(strict_types=1);
$ins = $institution;
$appConfig = require dirname(__DIR__, 4) . '/config/app.php';
$baseHost = parse_url($appConfig['url'], PHP_URL_HOST) ?: 'mitecnica.ar';
$expectedTenantUrl = 'https://' . $ins['subdomain'] . '.' . preg_replace('#^crm\.#', '', $baseHost);
?>
<div class="space-y-6">
  <div class="card p-5">
    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="text-xs uppercase tracking-wider text-slate-500">Subdominio actual</div>
        <div class="mt-2 text-xl font-mono text-white"><?= e($ins['subdomain']) ?></div>
        <a href="<?= e($expectedTenantUrl) ?>" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1 text-sm text-brand-300 hover:text-brand-200">
          Abrir tenant app
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
        </a>
      </div>
      <?php $status = $ins['technical_status']; include dirname(__DIR__, 3) . '/components/status_badge.php'; ?>
    </div>
  </div>

  <div class="card p-5">
    <h4 class="text-sm font-semibold text-white">Configuración DNS esperada</h4>
    <p class="mt-1 text-xs text-slate-500">La gestión automática de registros DNS y provisioning técnico se implementará en una fase posterior.</p>
    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3 text-xs font-mono">
      <div class="bg-slate-900/70 border border-slate-800 rounded-lg px-3 py-2">
        <div class="text-slate-500">Tipo</div><div class="text-slate-100">CNAME</div>
      </div>
      <div class="bg-slate-900/70 border border-slate-800 rounded-lg px-3 py-2">
        <div class="text-slate-500">Host</div><div class="text-slate-100"><?= e($ins['subdomain']) ?></div>
      </div>
      <div class="bg-slate-900/70 border border-slate-800 rounded-lg px-3 py-2">
        <div class="text-slate-500">Destino</div><div class="text-slate-100">tenant.<?= e($baseHost) ?></div>
      </div>
    </div>
  </div>
</div>
