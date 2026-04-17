<?php
declare(strict_types=1);
$m = $module ?? null;
$isEdit = $m !== null;
$statuses = ['active' => 'Activa', 'inactive' => 'Inactiva'];
$categories = ['' => '—', 'academic' => 'Académico', 'communication' => 'Comunicación', 'administration' => 'Administración', 'technical' => 'Técnico', 'analytics' => 'Analítica', 'other' => 'Otro'];
?>
<form method="post" action="<?= e($action) ?>" class="grid grid-cols-1 lg:grid-cols-3 gap-6" novalidate>
  <?= csrf_field() ?>
  <section class="lg:col-span-2 space-y-6">
    <div class="card p-6">
      <h3 class="text-sm font-semibold text-white mb-4">Módulo</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php
          $name = 'name'; $label = 'Nombre'; $type = 'text'; $required = true;
          $placeholder = 'Asistencias'; $value = old('name', $m['name'] ?? '');
          include dirname(__DIR__, 3) . '/components/form_input.php';
        ?>
        <?php
          $name = 'code'; $label = 'Código técnico'; $type = 'text'; $required = true;
          $placeholder = 'attendance'; $value = old('code', $m['code'] ?? '');
          $hint = 'Letras minúsculas, números, _ o -.';
          include dirname(__DIR__, 3) . '/components/form_input.php';
          $hint = null;
        ?>
      </div>
      <div class="mt-4">
        <?php
          $name = 'description'; $label = 'Descripción'; $required = false;
          $rows = 3; $value = old('description', $m['description'] ?? '');
          include dirname(__DIR__, 3) . '/components/form_textarea.php';
        ?>
      </div>
    </div>
  </section>
  <aside class="space-y-6">
    <div class="card p-6">
      <h3 class="text-sm font-semibold text-white mb-4">Clasificación</h3>
      <div class="space-y-4">
        <?php
          $name = 'category'; $label = 'Categoría'; $required = false;
          $options = $categories; $value = old('category', $m['category'] ?? '');
          include dirname(__DIR__, 3) . '/components/form_select.php';
        ?>
        <?php
          $name = 'status'; $label = 'Estado'; $required = true;
          $options = $statuses; $value = old('status', $m['status'] ?? 'active');
          include dirname(__DIR__, 3) . '/components/form_select.php';
        ?>
        <label class="inline-flex items-center gap-2 text-sm text-slate-300">
          <input type="checkbox" name="is_core" value="1" <?= old_raw('is_core', $m['is_core'] ?? false) ? 'checked' : '' ?> class="rounded border-slate-700 bg-slate-900 text-brand-500">
          Módulo core (incluido en todos los planes base)
        </label>
      </div>
    </div>
    <div class="flex items-center justify-end gap-2">
      <a href="<?= e($isEdit ? '/modules/' . (int)$m['id'] : '/modules') ?>" class="btn-ghost h-10 px-5">Cancelar</a>
      <button type="submit" class="btn-primary h-10 px-6"><?= e($submitLabel ?? 'Guardar') ?></button>
    </div>
  </aside>
</form>
