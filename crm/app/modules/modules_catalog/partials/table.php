<?php
declare(strict_types=1);
$list = $modules ?? [];
?>
<div class="card mt-4 overflow-hidden">
  <?php if (empty($list)): ?>
    <?php
      $title = 'Sin módulos';
      $message = 'Creá un módulo para empezar.';
      $ctaLabel = can('modules.create') ? 'Nuevo módulo' : null;
      $ctaHref = can('modules.create') ? '/modules/new' : null;
      include dirname(__DIR__, 3) . '/components/empty_state.php';
    ?>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="data-table">
        <thead>
          <tr>
            <th>Módulo</th>
            <th>Código</th>
            <th>Categoría</th>
            <th>Core</th>
            <th>Estado</th>
            <th class="text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($list as $m): ?>
            <tr class="hover:bg-slate-900/50">
              <td>
                <a href="/modules/<?= (int)$m['id'] ?>" class="text-white hover:text-brand-300 font-medium"><?= e($m['name']) ?></a>
                <?php if (!empty($m['description'])): ?>
                  <div class="text-xs text-slate-500 truncate max-w-[28rem]"><?= e($m['description']) ?></div>
                <?php endif; ?>
              </td>
              <td><span class="font-mono text-sm text-slate-300"><?= e($m['code']) ?></span></td>
              <td class="text-slate-300"><?= e(category_label($m['category'])) ?></td>
              <td>
                <?php if (!empty($m['is_core'])): ?>
                  <span class="badge-blue inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full">Core</span>
                <?php else: ?>
                  <span class="text-xs text-slate-500">—</span>
                <?php endif; ?>
              </td>
              <td><?php $status = $m['status']; include dirname(__DIR__, 3) . '/components/status_badge.php'; ?></td>
              <td class="text-right">
                <div class="inline-flex items-center gap-1">
                  <a href="/modules/<?= (int)$m['id'] ?>" class="btn-ghost h-8 px-3">Ver</a>
                  <?php if (can('modules.update')): ?>
                    <a href="/modules/<?= (int)$m['id'] ?>/edit" class="btn-ghost h-8 px-3">Editar</a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
