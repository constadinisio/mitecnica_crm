<?php
declare(strict_types=1);

/** Vars: string $action, array|null $payment, array $institutions, array $subscriptions, string $submitLabel */
$p = $payment ?? null;
$isEdit = $p !== null;
$institutions = $institutions ?? [];
$subscriptions = $subscriptions ?? [];
$statuses = ['pending' => 'Pendiente', 'approved' => 'Aprobado', 'rejected' => 'Rechazado', 'expired' => 'Expirado', 'canceled' => 'Cancelado'];
$currencies = ['ARS' => 'ARS — Peso argentino', 'USD' => 'USD — Dólar'];
?>
<form method="post" action="<?= e($action) ?>" class="grid grid-cols-1 lg:grid-cols-3 gap-6" novalidate>
  <?= csrf_field() ?>
  <section class="lg:col-span-2 space-y-6">
    <div class="card p-6">
      <h3 class="text-sm font-semibold text-white mb-4">Datos del pago</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php
          $instOptions = ['' => '— Seleccioná institución —'];
          foreach ($institutions as $i) $instOptions[(string)$i['id']] = $i['name'] . ' (' . $i['public_code'] . ')';
          $name = 'institution_id'; $label = 'Institución'; $required = true;
          $options = $instOptions; $value = old('institution_id', $p['institution_id'] ?? ($_GET['institution_id'] ?? ''));
          include dirname(__DIR__, 3) . '/components/form_select.php';
        ?>
        <?php
          $subOptions = ['' => '— Sin suscripción asociada —'];
          foreach ($subscriptions as $s) $subOptions[(string)$s['id']] = '#' . $s['id'] . ' — ' . ($s['institution_name'] ?? '') . ' · ' . ($s['plan_name'] ?? '');
          $name = 'subscription_id'; $label = 'Suscripción'; $required = false;
          $options = $subOptions; $value = old('subscription_id', $p['subscription_id'] ?? ($_GET['subscription_id'] ?? ''));
          include dirname(__DIR__, 3) . '/components/form_select.php';
        ?>
        <?php
          $name = 'amount'; $label = 'Monto'; $type = 'number'; $required = true;
          $placeholder = '0.00'; $value = old('amount', isset($p['amount']) ? number_format((float)$p['amount'], 2, '.', '') : '');
          include dirname(__DIR__, 3) . '/components/form_input.php';
        ?>
        <?php
          $name = 'currency_code'; $label = 'Moneda'; $required = true;
          $options = $currencies; $value = old('currency_code', $p['currency_code'] ?? 'ARS');
          include dirname(__DIR__, 3) . '/components/form_select.php';
        ?>
        <?php
          $name = 'payment_date'; $label = 'Fecha'; $type = 'date'; $required = true;
          $placeholder = ''; $value = old('payment_date', !empty($p['payment_date']) ? substr((string)$p['payment_date'], 0, 10) : date('Y-m-d'));
          include dirname(__DIR__, 3) . '/components/form_input.php';
        ?>
        <?php
          $name = 'payment_method'; $label = 'Método'; $type = 'text'; $required = false;
          $placeholder = 'Transferencia bancaria, Mercado Pago, etc.';
          $value = old('payment_method', $p['payment_method'] ?? '');
          include dirname(__DIR__, 3) . '/components/form_input.php';
        ?>
        <?php
          $name = 'reference_code'; $label = 'Referencia'; $type = 'text'; $required = false;
          $placeholder = 'Número de recibo / transacción';
          $value = old('reference_code', $p['reference_code'] ?? '');
          include dirname(__DIR__, 3) . '/components/form_input.php';
        ?>
      </div>
      <div class="mt-4">
        <?php
          $name = 'notes'; $label = 'Notas'; $required = false;
          $rows = 3; $value = old('notes', $p['notes'] ?? '');
          include dirname(__DIR__, 3) . '/components/form_textarea.php';
        ?>
      </div>
    </div>
  </section>

  <aside class="space-y-6">
    <div class="card p-6">
      <h3 class="text-sm font-semibold text-white mb-4">Estado</h3>
      <?php
        $name = 'status'; $label = 'Estado del pago'; $required = true;
        $options = $statuses; $value = old('status', $p['status'] ?? 'pending');
        include dirname(__DIR__, 3) . '/components/form_select.php';
      ?>
    </div>
    <div class="flex items-center justify-end gap-2">
      <a href="<?= e($isEdit ? '/payments/' . (int)$p['id'] : '/payments') ?>" class="btn-ghost h-10 px-5">Cancelar</a>
      <button type="submit" class="btn-primary h-10 px-6"><?= e($submitLabel ?? 'Guardar') ?></button>
    </div>
  </aside>
</form>
