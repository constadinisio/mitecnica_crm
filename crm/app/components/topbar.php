<?php
declare(strict_types=1);

$u = auth_user();
?>
<header class="h-16 border-b border-slate-800/60 bg-slate-950/60 backdrop-blur sticky top-0 z-30">
  <div class="h-full px-6 lg:px-10 flex items-center gap-4">
    <div class="flex-1 max-w-xl">
      <label class="relative block">
        <span class="sr-only">Buscar</span>
        <span class="absolute inset-y-0 left-3 flex items-center text-slate-500">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
        </span>
        <input type="search" placeholder="Buscar instituciones, dominios, códigos…" class="w-full pl-9 pr-3 h-10 rounded-lg bg-slate-900/70 border border-slate-800 text-sm text-slate-200 placeholder:text-slate-500 focus:border-brand-500 focus:ring-1 focus:ring-brand-500/40 outline-none"
               data-global-search>
      </label>
    </div>
    <div class="ml-auto flex items-center gap-3">
      <button type="button" class="h-10 w-10 grid place-items-center rounded-lg border border-slate-800 text-slate-400 hover:text-white hover:border-slate-700" title="Ayuda">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" /></svg>
      </button>
      <div class="h-9 w-9 rounded-full bg-brand-500/20 border border-brand-500/30 grid place-items-center text-sm font-semibold text-brand-200"><?= e(initials($u['name'] ?? null)) ?></div>
    </div>
  </div>
</header>
