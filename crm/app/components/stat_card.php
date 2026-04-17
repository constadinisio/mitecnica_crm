<?php
declare(strict_types=1);

/**
 * Vars:
 *   string $label
 *   string|int $value
 *   string|null $trend ('up'|'down'|'flat')
 *   string|null $hint
 *   string|null $accent ('brand'|'emerald'|'amber'|'rose'|'slate')
 */
$accent = $accent ?? 'brand';
$accentBg = [
  'brand'   => 'bg-brand-500/10 text-brand-300 border-brand-500/20',
  'emerald' => 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20',
  'amber'   => 'bg-amber-500/10 text-amber-300 border-amber-500/20',
  'rose'    => 'bg-rose-500/10 text-rose-300 border-rose-500/20',
  'slate'   => 'bg-slate-500/10 text-slate-300 border-slate-500/20',
][$accent] ?? 'bg-brand-500/10 text-brand-300 border-brand-500/20';
?>
<div class="card p-5">
  <div class="flex items-center justify-between">
    <div class="text-xs uppercase tracking-wider text-slate-400"><?= e($label) ?></div>
    <?php if (!empty($icon)): ?>
      <span class="h-8 w-8 grid place-items-center rounded-lg border <?= e($accentBg) ?>"><?= $icon ?></span>
    <?php endif; ?>
  </div>
  <div class="mt-3 text-3xl font-semibold text-white tabular-nums"><?= e($value) ?></div>
  <?php if (!empty($hint)): ?>
    <div class="mt-2 text-xs text-slate-400"><?= e($hint) ?></div>
  <?php endif; ?>
</div>
