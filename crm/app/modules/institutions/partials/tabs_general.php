<?php
declare(strict_types=1);
$ins = $institution;
?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
  <dl class="space-y-4 text-sm">
    <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Nombre</dt><dd class="mt-1 text-slate-100"><?= e($ins['name']) ?></dd></div>
    <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Código público</dt><dd class="mt-1 font-mono text-slate-200"><?= e($ins['public_code']) ?></dd></div>
    <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Slug</dt><dd class="mt-1 font-mono text-slate-300"><?= e($ins['slug']) ?></dd></div>
    <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Subdominio</dt><dd class="mt-1 font-mono text-slate-300"><?= e($ins['subdomain']) ?></dd></div>
    <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Dirección</dt><dd class="mt-1 text-slate-200"><?= e($ins['address'] ?? '—') ?></dd></div>
  </dl>
  <dl class="space-y-4 text-sm">
    <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Email de contacto</dt><dd class="mt-1 text-slate-200"><?= e($ins['contact_email']) ?></dd></div>
    <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Teléfono</dt><dd class="mt-1 text-slate-200"><?= e($ins['contact_phone'] ?? '—') ?></dd></div>
    <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Responsable</dt><dd class="mt-1 text-slate-200"><?= e(trim(($ins['responsible_name'] ?? '') . ' ' . ($ins['responsible_last_name'] ?? ''))) ?: '—' ?></dd></div>
    <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Email del responsable</dt><dd class="mt-1 text-slate-200"><?= e($ins['responsible_email'] ?? '—') ?></dd></div>
    <div><dt class="text-xs uppercase text-slate-500 tracking-wider">Creada</dt><dd class="mt-1 text-slate-300"><?= format_datetime($ins['created_at'] ?? null) ?></dd></div>
  </dl>
</div>

<?php if (!empty($ins['notes_internal'])): ?>
  <div class="mt-6 p-4 rounded-xl bg-slate-900/70 border border-slate-800">
    <div class="text-xs uppercase text-slate-500 tracking-wider mb-2">Notas internas</div>
    <div class="text-sm text-slate-200 whitespace-pre-line"><?= e($ins['notes_internal']) ?></div>
  </div>
<?php endif; ?>
