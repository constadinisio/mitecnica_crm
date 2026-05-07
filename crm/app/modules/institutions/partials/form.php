<?php
declare(strict_types=1);

/** Vars:
 *   string $action  (form action)
 *   array|null $institution (for edit)
 *   string $submitLabel
 *
 * Plan vigente + fecha de vencimiento se manejan desde la suscripción activa,
 * no desde este form (ver tab Suscripción).
 */
$ins = $institution ?? null;
$isEdit = $ins !== null;

$statuses = [
  'trial'        => 'Trial',
  'active'       => 'Activa',
  'maintenance'  => 'Mantenimiento',
  'suspended'    => 'Suspendida',
  'expired'      => 'Expirada',
  'inactive'     => 'Inactiva',
];
?>
<form method="post" action="<?= e($action) ?>" class="grid grid-cols-1 lg:grid-cols-3 gap-6" novalidate>
  <?= csrf_field() ?>

  <section class="lg:col-span-2 space-y-6">
    <div class="card p-6">
      <h3 class="text-sm font-semibold text-white mb-4">Datos generales</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php
          $name = 'name'; $label = 'Nombre de la institución'; $type = 'text'; $required = true;
          $placeholder = 'Escuela Técnica...'; $value = old('name', $ins['name'] ?? '');
          $icon = null;
          include dirname(__DIR__, 3) . '/components/form_input.php';
        ?>
        <?php
          $name = 'subdomain'; $label = 'Subdominio'; $type = 'text'; $required = true;
          $placeholder = 'et20-pellegrini'; $value = old('subdomain', $ins['subdomain'] ?? '');
          $hint = 'Se usará para la URL de la tenant app (min. lower-case y guiones).';
          include dirname(__DIR__, 3) . '/components/form_input.php';
          $hint = null;
        ?>
        <?php
          $name = 'slug'; $label = 'Slug interno'; $type = 'text'; $required = false;
          $placeholder = 'se genera automáticamente'; $value = old('slug', $ins['slug'] ?? '');
          include dirname(__DIR__, 3) . '/components/form_input.php';
        ?>
        <?php
          $name = 'contact_email'; $label = 'Email de contacto'; $type = 'email'; $required = true;
          $placeholder = 'contacto@institucion.edu.ar'; $value = old('contact_email', $ins['contact_email'] ?? '');
          include dirname(__DIR__, 3) . '/components/form_input.php';
        ?>
        <?php
          $name = 'contact_phone'; $label = 'Teléfono de contacto'; $type = 'text'; $required = false;
          $placeholder = '+54 11 ...'; $value = old('contact_phone', $ins['contact_phone'] ?? '');
          include dirname(__DIR__, 3) . '/components/form_input.php';
        ?>
        <?php
          $name = 'address'; $label = 'Dirección'; $type = 'text'; $required = false;
          $placeholder = 'Calle 123, Ciudad'; $value = old('address', $ins['address'] ?? '');
          include dirname(__DIR__, 3) . '/components/form_input.php';
        ?>
      </div>
    </div>

    <div class="card p-6">
      <h3 class="text-sm font-semibold text-white mb-4">Responsable</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php
          $name = 'responsible_name'; $label = 'Nombre'; $type = 'text'; $required = false;
          $placeholder = 'María José'; $value = old('responsible_name', $ins['responsible_name'] ?? '');
          include dirname(__DIR__, 3) . '/components/form_input.php';
        ?>
        <?php
          $name = 'responsible_last_name'; $label = 'Apellido'; $type = 'text'; $required = false;
          $placeholder = 'García López'; $value = old('responsible_last_name', $ins['responsible_last_name'] ?? '');
          include dirname(__DIR__, 3) . '/components/form_input.php';
        ?>
        <div class="md:col-span-2">
          <?php
            $name = 'responsible_email'; $label = 'Email del responsable'; $type = 'email'; $required = false;
            $placeholder = 'responsable@institucion.edu.ar'; $value = old('responsible_email', $ins['responsible_email'] ?? '');
            include dirname(__DIR__, 3) . '/components/form_input.php';
          ?>
        </div>
      </div>
    </div>

    <div class="card p-6">
      <h3 class="text-sm font-semibold text-white mb-4">Notas internas</h3>
      <?php
        $name = 'notes_internal'; $label = 'Notas visibles sólo para el equipo CRM'; $required = false;
        $rows = 4; $value = old('notes_internal', $ins['notes_internal'] ?? '');
        include dirname(__DIR__, 3) . '/components/form_textarea.php';
      ?>
    </div>
  </section>

  <aside class="space-y-6">
    <div class="card p-6">
      <h3 class="text-sm font-semibold text-white mb-4">Estado</h3>
      <div class="space-y-4">
        <?php
          $name = 'status'; $label = 'Estado comercial'; $required = true;
          $options = $statuses; $value = old('status', $ins['status'] ?? 'trial');
          include dirname(__DIR__, 3) . '/components/form_select.php';
        ?>
        <p class="text-xs text-slate-500">
          El plan vigente y la fecha de vencimiento se gestionan desde la <strong class="text-slate-300">suscripción activa</strong>.
          <?php if ($isEdit): ?>
            <a href="/subscriptions?institution_id=<?= (int)$ins['id'] ?>" class="text-brand-300 hover:text-brand-200">Ver suscripciones →</a>
          <?php endif; ?>
        </p>
      </div>
    </div>

    <div class="flex items-center justify-end gap-2">
      <a href="<?= e($isEdit ? '/institutions/' . (int)$ins['id'] : '/institutions') ?>" class="btn-ghost h-10 px-5">Cancelar</a>
      <button type="submit" class="btn-primary h-10 px-6"><?= e($submitLabel ?? 'Guardar') ?></button>
    </div>
  </aside>
</form>
