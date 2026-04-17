<?php
declare(strict_types=1);

/**
 * Vars: string $id, string $title, string $body (raw HTML), string|null $footer (raw HTML)
 */
?>
<div id="<?= e($id) ?>" class="hidden fixed inset-0 z-40" role="dialog" aria-modal="true" data-modal>
  <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" data-modal-dismiss></div>
  <div class="relative z-10 max-w-lg mx-auto mt-20 card p-6">
    <div class="flex items-start justify-between gap-4">
      <h3 class="text-lg font-semibold text-white"><?= e($title) ?></h3>
      <button type="button" class="text-slate-400 hover:text-white" data-modal-dismiss>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
      </button>
    </div>
    <div class="mt-4 text-sm text-slate-300"><?= $body ?></div>
    <?php if (!empty($footer)): ?>
      <div class="mt-6 flex items-center justify-end gap-2"><?= $footer ?></div>
    <?php endif; ?>
  </div>
</div>
