<?php
declare(strict_types=1);

/** Vars: string $action, array|null $sub, array $institutions, array $plans, string $submitLabel */
$s = $sub ?? null;
$isEdit = $s !== null;
$institutions = $institutions ?? [];
$plans = $plans ?? [];
$statuses = ['trial' => 'Trial', 'active' => 'Activa', 'suspended' => 'Suspendida', 'expired' => 'Expirada', 'canceled' => 'Cancelada'];
$renewal = ['manual' => 'Manual', 'automatic' => 'Automática'];
?>
<form method="post" action="<?= e($action) ?>" class="grid grid-cols-1 lg:grid-cols-3 gap-6" novalidate>
  <?= csrf_field() ?>
  <section class="lg:col-span-2 space-y-6">
    <div class="card p-6">
      <h3 class="text-sm font-semibold text-white mb-4">Institución y plan</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php
          $instOptions = ['' => '— Seleccioná institución —'];
          foreach ($institutions as $i) $instOptions[(string)$i['id']] = $i['name'] . ' (' . $i['public_code'] . ')';
          $name = 'institution_id'; $label = 'Institución'; $required = true;
          $options = $instOptions; $value = old('institution_id', $s['institution_id'] ?? '');
          include dirname(__DIR__, 3) . '/components/form_select.php';
        ?>
        <?php
          $planOptions = ['' => '— Seleccioná plan —'];
          foreach ($plans as $p) $planOptions[(string)$p['id']] = $p['name'] . ' · ' . format_money($p['price_amount'], $p['currency_code']);
          $name = 'plan_id'; $label = 'Plan'; $required = true;
          $options = $planOptions; $value = old('plan_id', $s['plan_id'] ?? '');
          include dirname(__DIR__, 3) . '/components/form_select.php';
        ?>
      </div>
    </div>

    <div class="card p-6">
      <h3 class="text-sm font-semibold text-white mb-4">Fechas</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php
          $name = 'start_date'; $label = 'Inicio'; $type = 'date'; $required = true;
          $placeholder = ''; $value = old('start_date', !empty($s['start_date']) ? substr((string)$s['start_date'], 0, 10) : '');
          include dirname(__DIR__, 3) . '/components/form_input.php';
        ?>
        <?php
          $name = 'end_date'; $label = 'Fin (opcional)'; $type = 'date'; $required = false;
          $placeholder = ''; $value = old('end_date', !empty($s['end_date']) ? substr((string)$s['end_date'], 0, 10) : '');
          include dirname(__DIR__, 3) . '/components/form_input.php';
        ?>
        <?php
          $name = 'trial_ends_at'; $label = 'Fin de trial (opcional)'; $type = 'date'; $required = false;
          $placeholder = ''; $value = old('trial_ends_at', !empty($s['trial_ends_at']) ? substr((string)$s['trial_ends_at'], 0, 10) : '');
          include dirname(__DIR__, 3) . '/components/form_input.php';
        ?>
      </div>
    </div>

    <div class="card p-6">
      <h3 class="text-sm font-semibold text-white mb-4">Notas</h3>
      <?php
        $name = 'billing_notes'; $label = 'Notas comerciales / facturación'; $required = false;
        $rows = 4; $value = old('billing_notes', $s['billing_notes'] ?? '');
        include dirname(__DIR__, 3) . '/components/form_textarea.php';
      ?>
    </div>
  </section>

  <aside class="space-y-6">
    <div class="card p-6">
      <h3 class="text-sm font-semibold text-white mb-4">Estado</h3>
      <div class="space-y-4">
        <?php
          $name = 'status'; $label = 'Estado'; $required = true;
          $options = $statuses; $value = old('status', $s['status'] ?? 'trial');
          include dirname(__DIR__, 3) . '/components/form_select.php';
        ?>
        <?php
          $name = 'renewal_mode'; $label = 'Renovación'; $required = true;
          $options = $renewal; $value = old('renewal_mode', $s['renewal_mode'] ?? 'manual');
          include dirname(__DIR__, 3) . '/components/form_select.php';
        ?>
      </div>
    </div>
    <div class="flex items-center justify-end gap-2">
      <a href="<?= e($isEdit ? '/subscriptions/' . (int)$s['id'] : '/subscriptions') ?>" class="btn-ghost h-10 px-5">Cancelar</a>
      <button type="submit" class="btn-primary h-10 px-6"><?= e($submitLabel ?? 'Guardar') ?></button>
    </div>
  </aside>
</form>
