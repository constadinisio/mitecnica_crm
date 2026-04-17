<?php
declare(strict_types=1);

$flashes = $flashes ?? flash_pull();
if (empty($flashes)) return;
$types = [
  'success' => 'bg-emerald-500/10 text-emerald-200 border-emerald-500/30',
  'error'   => 'bg-rose-500/10 text-rose-200 border-rose-500/30',
  'warn'    => 'bg-amber-500/10 text-amber-200 border-amber-500/30',
  'info'    => 'bg-brand-500/10 text-brand-200 border-brand-500/30',
];
?>
<div class="mb-6 space-y-2">
  <?php foreach ($flashes as $f): $cls = $types[$f['type'] ?? 'info'] ?? $types['info']; ?>
    <div class="flex items-start gap-3 px-4 py-3 rounded-xl border text-sm <?= e($cls) ?>">
      <span class="mt-0.5">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm8.25-3.75a.75.75 0 111.5 0v4.5a.75.75 0 11-1.5 0v-4.5zm.75 8.25h.008v.008H11.25v-.008z" /></svg>
      </span>
      <div class="flex-1"><?= e($f['message'] ?? '') ?></div>
    </div>
  <?php endforeach; ?>
</div>
