<?php
declare(strict_types=1);
$list = $plans ?? [];
?>
<div class="card mt-4 overflow-hidden">
  <?php if (empty($list)): ?>
    <?php
      $title = 'Sin planes';
      $message = 'No hay planes creados todavía.';
      $ctaLabel = can('plans.create') ? 'Nuevo plan' : null;
      $ctaHref = can('plans.create') ? '/plans/new' : null;
      include dirname(__DIR__, 3) . '/components/empty_state.php';
    ?>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="data-table">
        <thead>
          <tr>
            <th>Plan</th>
            <th>Código</th>
            <th>Frecuencia</th>
            <th class="text-right">Precio</th>
            <th>Estado</th>
            <th class="text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($list as $p): ?>
            <tr class="hover:bg-slate-900/50">
              <td>
                <a href="/plans/<?= (int)$p['id'] ?>" class="text-white hover:text-brand-300 font-medium"><?= e($p['name']) ?></a>
                <?php if (!empty($p['is_custom'])): ?>
                  <span class="ml-2 text-[10px] uppercase px-1.5 py-0.5 rounded bg-slate-800 text-slate-400">Custom</span>
                <?php endif; ?>
                <?php if (!empty($p['description'])): ?>
                  <div class="text-xs text-slate-500 truncate max-w-[28rem]"><?= e($p['description']) ?></div>
                <?php endif; ?>
              </td>
              <td><span class="font-mono text-sm text-slate-300"><?= e($p['code']) ?></span></td>
              <td class="text-slate-300"><?= e(frequency_label($p['billing_frequency'])) ?></td>
              <td class="text-right text-slate-100 tabular-nums"><?= e(format_money($p['price_amount'], $p['currency_code'])) ?></td>
              <td><?php $status = $p['status']; include dirname(__DIR__, 3) . '/components/status_badge.php'; ?></td>
              <td class="text-right">
                <div class="inline-flex items-center gap-1">
                  <a href="/plans/<?= (int)$p['id'] ?>" class="btn-ghost h-8 px-3">Ver</a>
                  <?php if (can('plans.update')): ?>
                    <a href="/plans/<?= (int)$p['id'] ?>/edit" class="btn-ghost h-8 px-3">Editar</a>
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
