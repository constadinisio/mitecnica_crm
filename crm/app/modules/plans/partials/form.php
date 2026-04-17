<?php
declare(strict_types=1);

/** Vars: string $action, array|null $plan, string $submitLabel */
$p = $plan ?? null;
$isEdit = $p !== null;
$statuses = ['active' => 'Activa', 'inactive' => 'Inactiva', 'archived' => 'Archivada'];
$freqs = ['monthly' => 'Mensual', 'quarterly' => 'Trimestral', 'yearly' => 'Anual', 'custom' => 'Personalizado'];
$currencies = ['ARS' => 'ARS — Peso argentino', 'USD' => 'USD — Dólar'];
?>
<form method="post" action="<?= e($action) ?>" class="grid grid-cols-1 lg:grid-cols-3 gap-6" novalidate>
  <?= csrf_field() ?>

  <section class="lg:col-span-2 space-y-6">
    <div class="card p-6">
      <h3 class="text-sm font-semibold text-white mb-4">Plan</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php
          $name = 'name'; $label = 'Nombre del plan'; $type = 'text'; $required = true;
          $placeholder = 'Professional'; $value = old('name', $p['name'] ?? '');
          include dirname(__DIR__, 3) . '/components/form_input.php';
        ?>
        <?php
          $name = 'code'; $label = 'Código interno'; $type = 'text'; $required = true;
          $placeholder = 'professional'; $value = old('code', $p['code'] ?? '');
          $hint = 'Sólo letras, números, guión o guión bajo.';
          include dirname(__DIR__, 3) . '/components/form_input.php';
          $hint = null;
        ?>
      </div>
      <div class="mt-4">
        <?php
          $name = 'description'; $label = 'Descripción'; $required = false;
          $rows = 3; $value = old('description', $p['description'] ?? '');
          include dirname(__DIR__, 3) . '/components/form_textarea.php';
        ?>
      </div>
    </div>
  </section>

  <aside class="space-y-6">
    <div class="card p-6">
      <h3 class="text-sm font-semibold text-white mb-4">Pricing</h3>
      <div class="space-y-4">
        <?php
          $name = 'price_amount'; $label = 'Precio'; $type = 'number'; $required = true;
          $placeholder = '0.00'; $value = old('price_amount', isset($p['price_amount']) ? number_format((float)$p['price_amount'], 2, '.', '') : '');
          include dirname(__DIR__, 3) . '/components/form_input.php';
        ?>
        <?php
          $name = 'currency_code'; $label = 'Moneda'; $required = true;
          $options = $currencies; $value = old('currency_code', $p['currency_code'] ?? 'ARS');
          include dirname(__DIR__, 3) . '/components/form_select.php';
        ?>
        <?php
          $name = 'billing_frequency'; $label = 'Frecuencia'; $required = true;
          $options = $freqs; $value = old('billing_frequency', $p['billing_frequency'] ?? 'monthly');
          include dirname(__DIR__, 3) . '/components/form_select.php';
        ?>
      </div>
    </div>

    <div class="card p-6">
      <h3 class="text-sm font-semibold text-white mb-4">Estado</h3>
      <?php
        $name = 'status'; $label = 'Estado comercial'; $required = true;
        $options = $statuses; $value = old('status', $p['status'] ?? 'active');
        include dirname(__DIR__, 3) . '/components/form_select.php';
      ?>
      <label class="mt-4 inline-flex items-center gap-2 text-sm text-slate-300">
        <input type="checkbox" name="is_custom" value="1" <?= old_raw('is_custom', $p['is_custom'] ?? false) ? 'checked' : '' ?> class="rounded border-slate-700 bg-slate-900 text-brand-500">
        Plan a medida (no listar públicamente)
      </label>
    </div>

    <div class="flex items-center justify-end gap-2">
      <a href="<?= e($isEdit ? '/plans/' . (int)$p['id'] : '/plans') ?>" class="btn-ghost h-10 px-5">Cancelar</a>
      <button type="submit" class="btn-primary h-10 px-6"><?= e($submitLabel ?? 'Guardar') ?></button>
    </div>
  </aside>
</form>
