<?php
declare(strict_types=1);
$logs = $audit ?? [];
?>
<div class="space-y-4">
  <div class="flex items-center justify-between">
    <div>
      <h4 class="text-sm font-semibold text-white">Eventos recientes</h4>
      <p class="text-xs text-slate-500">Últimos 20 eventos auditados relacionados a esta institución.</p>
    </div>
  </div>

  <?php if (empty($logs)): ?>
    <div class="p-8 text-center text-sm text-slate-500">No hay eventos registrados todavía.</div>
  <?php else: ?>
    <ol class="relative border-l border-slate-800 ml-2 space-y-4">
      <?php foreach ($logs as $log): ?>
        <li class="ml-4">
          <span class="absolute -left-[5px] h-2.5 w-2.5 rounded-full bg-brand-400 ring-4 ring-slate-950"></span>
          <div class="flex items-center gap-2 text-xs text-slate-500">
            <span class="text-slate-300"><?= e($log['actor_name'] ?? 'Sistema') ?></span>
            <span>·</span>
            <time><?= e(format_datetime($log['created_at'] ?? null)) ?></time>
            <span class="ml-2 px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 font-mono text-[10px]"><?= e($log['action']) ?></span>
          </div>
          <div class="mt-1 text-sm text-slate-200"><?= e($log['description'] ?? '') ?></div>
        </li>
      <?php endforeach; ?>
    </ol>
  <?php endif; ?>
</div>
