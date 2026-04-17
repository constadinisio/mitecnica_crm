<?php
declare(strict_types=1);

/**
 * Vars:
 *   array $pagination [page, limit, total, pages, hasNext, hasPrev]
 *   string $baseUrl   (current URL without page)
 *   array  $currentQuery (query args to preserve)
 */
$pagination = $pagination ?? [];
$page  = (int)($pagination['page'] ?? 1);
$pages = (int)($pagination['pages'] ?? 1);
$limit = (int)($pagination['limit'] ?? 20);
$total = (int)($pagination['total'] ?? 0);
$baseUrl = $baseUrl ?? current_path();
$currentQuery = $currentQuery ?? [];

function build_page_url(string $base, array $q, int $p): string {
    $q['page'] = $p;
    return $base . '?' . http_build_query($q);
}
$from = $total === 0 ? 0 : (($page - 1) * $limit) + 1;
$to = min($page * $limit, $total);
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm text-slate-400">
  <div>
    Mostrando <span class="text-slate-200"><?= e($from) ?></span>–<span class="text-slate-200"><?= e($to) ?></span>
    de <span class="text-slate-200"><?= e($total) ?></span>
  </div>
  <?php if ($pages > 1): ?>
    <div class="flex items-center gap-1">
      <a href="<?= e(build_page_url($baseUrl, $currentQuery, max(1, $page - 1))) ?>"
         class="btn-ghost h-8 px-3 <?= $page <= 1 ? 'opacity-40 pointer-events-none' : '' ?>">Anterior</a>
      <?php
      $start = max(1, $page - 2);
      $end = min($pages, $start + 4);
      $start = max(1, $end - 4);
      for ($p = $start; $p <= $end; $p++):
      ?>
        <a href="<?= e(build_page_url($baseUrl, $currentQuery, $p)) ?>"
           class="h-8 w-8 grid place-items-center rounded-md text-xs <?= $p === $page ? 'bg-brand-500/15 text-brand-200 border border-brand-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <a href="<?= e(build_page_url($baseUrl, $currentQuery, min($pages, $page + 1))) ?>"
         class="btn-ghost h-8 px-3 <?= $page >= $pages ? 'opacity-40 pointer-events-none' : '' ?>">Siguiente</a>
    </div>
  <?php endif; ?>
</div>
