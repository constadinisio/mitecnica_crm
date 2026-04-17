<?php
declare(strict_types=1);

/**
 * Vars:
 *   string $title
 *   string|null $message
 *   string|null $ctaLabel
 *   string|null $ctaHref
 */
?>
<div class="card p-10 text-center">
  <div class="mx-auto h-12 w-12 rounded-xl bg-slate-800/70 border border-slate-700 grid place-items-center">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-slate-400"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
  </div>
  <h3 class="mt-4 text-lg font-semibold text-white"><?= e($title) ?></h3>
  <?php if (!empty($message)): ?>
    <p class="mt-1 text-sm text-slate-400 max-w-md mx-auto"><?= e($message) ?></p>
  <?php endif; ?>
  <?php if (!empty($ctaLabel) && !empty($ctaHref)): ?>
    <a href="<?= e($ctaHref) ?>" class="btn-primary mt-6 inline-flex"><?= e($ctaLabel) ?></a>
  <?php endif; ?>
</div>
