<?php
declare(strict_types=1);

$sidebar = require dirname(__DIR__, 2) . '/config/sidebar.php';
$roleKey = current_role_key();

$icons = [
    'grid'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25A2.25 2.25 0 018.25 10.5H6A2.25 2.25 0 013.75 8.25V6zM13.5 6A2.25 2.25 0 0115.75 3.75H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25A2.25 2.25 0 0113.5 8.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />',
    'building'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15a.75.75 0 01.75.75V21H3.75V3.75A.75.75 0 014.5 3zm3.75 3.75h1.5v1.5h-1.5v-1.5zm0 3.75h1.5V12h-1.5v-1.5zm0 3.75h1.5v1.5h-1.5v-1.5zm3.75-7.5h1.5v1.5h-1.5v-1.5zm0 3.75h1.5V12h-1.5v-1.5zm0 3.75h1.5v1.5h-1.5v-1.5zm3.75-7.5H18v1.5h-1.5v-1.5zm0 3.75H18V12h-1.5v-1.5zm0 3.75H18v1.5h-1.5v-1.5z" />',
    'shield'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25L15 9.75M12 2.25l7.5 3v6c0 5.25-3.75 9.75-7.5 10.5-3.75-.75-7.5-5.25-7.5-10.5v-6l7.5-3z" />',
    'tag'         => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H3.75A.75.75 0 003 3.75v5.818c0 .2.079.39.22.53l10.94 10.94a.75.75 0 001.06 0l5.819-5.819a.75.75 0 000-1.06L10.098 3.22A.75.75 0 009.568 3zM6 6h.008v.008H6V6z" />',
    'layers'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5l5.25-3 5.25 3-5.25 3-5.25-3zm0 4.5L12 15l5.25-3M6.75 16.5L12 19.5l5.25-3" />',
    'repeat'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356M4.05 10.5A7.5 7.5 0 0117.543 7.5M3.382 15.402l4.992-.006v4.992M19.95 13.5a7.5 7.5 0 01-13.493 3" />',
    'credit-card' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9v9a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9M2.25 9V6a2.25 2.25 0 012.25-2.25h15A2.25 2.25 0 0121.75 6v3M5.25 15h3v1.5h-3V15z" />',
    'users'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />',
    'cog'         => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
];

function render_icon(string $name, array $icons): string {
    $path = $icons[$name] ?? $icons['grid'];
    return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">' . $path . '</svg>';
}
?>
<aside class="hidden lg:flex w-64 shrink-0 flex-col border-r border-slate-800/60 bg-slate-950/80 backdrop-blur">
  <div class="h-16 flex items-center gap-3 px-5 border-b border-slate-800/60">
    <span class="h-9 w-9 rounded-xl bg-brand-500 grid place-items-center text-white font-bold">MT</span>
    <div class="min-w-0">
      <div class="text-sm font-semibold text-white truncate">Mi Tecnica</div>
      <div class="text-xs text-slate-400">CRM Central</div>
    </div>
  </div>
  <nav class="flex-1 overflow-y-auto px-3 py-5 space-y-6">
    <?php foreach ($sidebar as $group): ?>
      <div>
        <div class="px-2 pb-2 text-xs uppercase tracking-wider text-slate-500"><?= e($group['section']) ?></div>
        <ul class="space-y-1">
          <?php foreach ($group['items'] as $item):
            if (!empty($item['roles']) && $roleKey && !in_array($roleKey, $item['roles'], true) && $roleKey !== 'superadmin') continue;
            $active = is_active_route($item['match'] ?? null);
            $soon = !empty($item['soon']);
          ?>
            <li>
              <a href="<?= e($item['route']) ?>"
                 class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition <?= $active ? 'bg-brand-500/10 text-white border border-brand-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>"
                 <?= $soon ? 'title="Próximamente"' : '' ?>>
                <?= render_icon($item['icon'] ?? 'grid', $icons) ?>
                <span class="truncate"><?= e($item['label']) ?></span>
                <?php if ($soon): ?>
                  <span class="ml-auto text-[10px] uppercase tracking-wider bg-slate-800 text-slate-400 px-1.5 py-0.5 rounded">Soon</span>
                <?php endif; ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>
  </nav>
  <div class="border-t border-slate-800/60 p-4 flex items-center gap-3">
    <div class="h-9 w-9 rounded-full bg-slate-800 grid place-items-center text-sm font-semibold text-slate-200"><?= e(initials(auth_user()['name'] ?? null)) ?></div>
    <div class="min-w-0 flex-1">
      <div class="text-sm font-medium text-white truncate"><?= e(auth_user()['name'] ?? 'Usuario') ?></div>
      <div class="text-xs text-slate-400 truncate"><?= e(auth_user()['role']['name'] ?? '—') ?></div>
    </div>
    <a href="/logout" class="text-slate-400 hover:text-white" title="Cerrar sesión">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
    </a>
  </div>
</aside>
