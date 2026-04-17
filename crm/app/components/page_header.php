<?php
declare(strict_types=1);

/**
 * Vars:
 *   string $title
 *   string|null $subtitle
 *   string|null $actionsHtml (raw HTML for right-aligned CTAs)
 *   array|null $breadcrumbs [{label,href?}, ...]
 */
?>
<div class="flex items-start justify-between gap-4 mb-6">
  <div>
    <?php if (!empty($breadcrumbs)): ?>
      <nav class="flex items-center gap-1.5 text-xs text-slate-500 mb-2">
        <?php foreach ($breadcrumbs as $i => $bc): ?>
          <?php if ($i > 0): ?><span>/</span><?php endif; ?>
          <?php if (!empty($bc['href'])): ?>
            <a href="<?= e($bc['href']) ?>" class="hover:text-slate-300"><?= e($bc['label']) ?></a>
          <?php else: ?>
            <span class="text-slate-400"><?= e($bc['label']) ?></span>
          <?php endif; ?>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>
    <h1 class="text-2xl font-semibold text-white tracking-tight"><?= e($title) ?></h1>
    <?php if (!empty($subtitle)): ?>
      <p class="mt-1 text-sm text-slate-400"><?= e($subtitle) ?></p>
    <?php endif; ?>
  </div>
  <?php if (!empty($actionsHtml)): ?>
    <div class="flex items-center gap-2 flex-wrap"><?= $actionsHtml ?></div>
  <?php endif; ?>
</div>
