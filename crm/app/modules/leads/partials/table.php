<?php
declare(strict_types=1);
$list = $leads ?? [];
?>
<div class="card mt-4 overflow-hidden">
  <?php if (empty($list)): ?>
    <?php
      $title = 'Sin solicitudes';
      $message = 'Todavía no hay pedidos entrantes. Compartí el link público /contact para empezar a recibirlos.';
      $ctaLabel = 'Abrir formulario público';
      $ctaHref = '/contact';
      include dirname(__DIR__, 3) . '/components/empty_state.php';
    ?>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="data-table">
        <thead>
          <tr>
            <th>Institución</th>
            <th>Contacto</th>
            <th>Plan interés</th>
            <th>Asignada a</th>
            <th>Estado</th>
            <th>Recibida</th>
            <th class="text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($list as $l): ?>
            <tr class="hover:bg-slate-900/50">
              <td>
                <a href="/leads/<?= (int)$l['id'] ?>" class="text-white hover:text-brand-300 font-medium"><?= e($l['institution_name']) ?></a>
                <?php if (!empty($l['converted_institution_code'])): ?>
                  <div class="text-xs text-emerald-400">Convertida: <?= e($l['converted_institution_code']) ?></div>
                <?php elseif (!empty($l['address'])): ?>
                  <div class="text-xs text-slate-500 truncate max-w-[22rem]"><?= e($l['address']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <div class="text-slate-200 text-sm"><?= e($l['contact_name']) ?></div>
                <div class="text-xs text-slate-500"><?= e($l['contact_email']) ?><?= !empty($l['contact_phone']) ? ' · ' . e($l['contact_phone']) : '' ?></div>
              </td>
              <td class="text-slate-300"><?= e($l['plan_code_requested'] ?? '—') ?></td>
              <td class="text-slate-400 text-xs"><?= e($l['assigned_to_name'] ?? 'Sin asignar') ?></td>
              <td><?php $status = $l['status']; $statusOverrideLabel = lead_status_label($l['status']); include dirname(__DIR__, 3) . '/components/status_badge.php'; ?></td>
              <td class="text-slate-400 text-xs"><?= format_relative($l['created_at'] ?? null) ?></td>
              <td class="text-right">
                <a href="/leads/<?= (int)$l['id'] ?>" class="btn-ghost h-8 px-3">Ver</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
