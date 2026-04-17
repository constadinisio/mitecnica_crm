<?php
declare(strict_types=1);

require_auth();
require_permission('leads.convert');

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { http_response_code(404); echo 'Not found'; return; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $body = [
        'institution_name'     => trim((string)($_POST['institution_name'] ?? '')),
        'subdomain'            => trim((string)($_POST['subdomain'] ?? '')),
        'slug'                 => trim((string)($_POST['slug'] ?? '')) ?: null,
        'contact_email'        => trim((string)($_POST['contact_email'] ?? '')),
        'contact_phone'        => trim((string)($_POST['contact_phone'] ?? '')) ?: null,
        'address'              => trim((string)($_POST['address'] ?? '')) ?: null,
        'responsible_name'     => trim((string)($_POST['responsible_name'] ?? '')) ?: null,
        'responsible_email'    => trim((string)($_POST['responsible_email'] ?? '')) ?: null,
        'notes_internal'       => trim((string)($_POST['notes_internal'] ?? '')) ?: null,
        'institution_status'   => $_POST['institution_status'] ?? 'trial',
        'plan_id'              => isset($_POST['plan_id']) && $_POST['plan_id'] !== '' ? (int)$_POST['plan_id'] : null,
        'create_subscription'  => isset($_POST['create_subscription']) && $_POST['create_subscription'] === '1',
        'subscription_status'  => $_POST['subscription_status'] ?? 'trial',
        'start_date'           => trim((string)($_POST['start_date'] ?? '')) ?: null,
        'end_date'             => trim((string)($_POST['end_date'] ?? '')) ?: null,
        'renewal_mode'         => $_POST['renewal_mode'] ?? 'manual',
        'billing_notes'        => trim((string)($_POST['billing_notes'] ?? '')) ?: null,
    ];
    try {
        $res = api_post("/leads/$id/convert", ['body' => $body]);
        $inst = $res['data']['institution'] ?? null;
        old_clear();
        flash_set('success', 'Solicitud convertida. Institución y suscripción creadas.');
        redirect($inst ? ('/institutions/' . (int)$inst['id']) : "/leads/$id");
    } catch (ApiClientException $e) {
        old_set($body);
        if ($e->details && is_array($e->details)) errors_set($e->details);
        flash_set('error', $e->getMessage() ?: 'No se pudo convertir la solicitud.');
        redirect("/leads/$id/convert");
    }
}

$lead = null;
$plans = [];
try {
    $lead = api_get("/leads/$id")['data'] ?? null;
    $plans = api_get('/plans', ['query' => ['limit' => 100, 'status' => 'active']])['data'] ?? [];
} catch (ApiClientException $e) {
    flash_set('error', $e->getMessage());
    redirect('/leads');
}
if (!$lead) { http_response_code(404); echo 'Not found'; return; }
if (in_array($lead['status'], ['converted', 'lost'], true)) {
    flash_set('warn', 'Esta solicitud ya está en estado ' . $lead['status'] . ' y no se puede convertir.');
    redirect("/leads/$id");
}

// Preselect plan_id from plan_code_requested if present
$preselectPlanId = '';
if (!empty($lead['plan_code_requested'])) {
    foreach ($plans as $p) {
        if ($p['code'] === $lead['plan_code_requested']) { $preselectPlanId = (string)$p['id']; break; }
    }
}

$planOptions = ['' => '— Sin plan (no crear suscripción) —'];
foreach ($plans as $p) {
    $planOptions[(string)$p['id']] = $p['name'] . ' · ' . format_money($p['price_amount'], $p['currency_code']) . ' / ' . strtolower(frequency_label($p['billing_frequency']));
}

ob_start();
?>
<?php
  $title = 'Convertir solicitud a cliente';
  $subtitle = $lead['institution_name'] . ' · ' . $lead['contact_name'];
  $breadcrumbs = [
    ['label' => 'Dashboard', 'href' => '/dashboard'],
    ['label' => 'Solicitudes', 'href' => '/leads'],
    ['label' => '#' . $id, 'href' => "/leads/$id"],
    ['label' => 'Convertir'],
  ];
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>

<div class="card p-4 mb-6 border-brand-500/30 bg-brand-500/5">
  <div class="flex items-start gap-3 text-sm">
    <span class="mt-0.5 h-6 w-6 rounded-full bg-brand-500/15 text-brand-300 grid place-items-center text-xs">i</span>
    <div class="text-slate-300">
      Al convertir, se crea la <strong class="text-white">institución</strong> definitiva (con subdominio asignado) y opcionalmente una <strong class="text-white">suscripción</strong>. El pago se registra por separado desde <a href="/payments/new" class="text-brand-300 hover:text-brand-200">/payments/new</a> una vez confirmado.
    </div>
  </div>
</div>

<form method="post" action="/leads/<?= $id ?>/convert" class="grid grid-cols-1 lg:grid-cols-3 gap-6" novalidate>
  <?= csrf_field() ?>

  <section class="lg:col-span-2 space-y-6">
    <div class="card p-6">
      <h3 class="text-sm font-semibold text-white mb-4">Institución</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php
          $name = 'institution_name'; $label = 'Nombre'; $type = 'text'; $required = true;
          $placeholder = ''; $value = old('institution_name', $lead['institution_name']);
          include dirname(__DIR__, 2) . '/components/form_input.php';
        ?>
        <?php
          $name = 'subdomain'; $label = 'Subdominio'; $type = 'text'; $required = true;
          $placeholder = 'nombre-escuela'; $value = old('subdomain', '');
          $hint = 'Se definirá ahora y no se podrá cambiar fácilmente.';
          include dirname(__DIR__, 2) . '/components/form_input.php';
          $hint = null;
        ?>
        <?php
          $name = 'contact_email'; $label = 'Email de contacto'; $type = 'email'; $required = true;
          $placeholder = ''; $value = old('contact_email', $lead['contact_email']);
          include dirname(__DIR__, 2) . '/components/form_input.php';
        ?>
        <?php
          $name = 'contact_phone'; $label = 'Teléfono'; $type = 'text'; $required = false;
          $placeholder = ''; $value = old('contact_phone', $lead['contact_phone'] ?? '');
          include dirname(__DIR__, 2) . '/components/form_input.php';
        ?>
        <div class="md:col-span-2">
          <?php
            $name = 'address'; $label = 'Dirección'; $type = 'text'; $required = false;
            $placeholder = ''; $value = old('address', $lead['address'] ?? '');
            include dirname(__DIR__, 2) . '/components/form_input.php';
          ?>
        </div>
        <?php
          $name = 'responsible_name'; $label = 'Responsable'; $type = 'text'; $required = false;
          $placeholder = ''; $value = old('responsible_name', $lead['contact_name'] ?? '');
          include dirname(__DIR__, 2) . '/components/form_input.php';
        ?>
        <?php
          $name = 'responsible_email'; $label = 'Email del responsable'; $type = 'email'; $required = false;
          $placeholder = ''; $value = old('responsible_email', $lead['contact_email'] ?? '');
          include dirname(__DIR__, 2) . '/components/form_input.php';
        ?>
      </div>
      <div class="mt-4">
        <?php
          $name = 'notes_internal'; $label = 'Notas internas'; $required = false;
          $rows = 3; $value = old('notes_internal', $lead['notes'] ? '(Desde lead #' . $id . ') ' . $lead['notes'] : '');
          include dirname(__DIR__, 2) . '/components/form_textarea.php';
        ?>
      </div>
    </div>

    <div class="card p-6">
      <h3 class="text-sm font-semibold text-white mb-4">Suscripción inicial (opcional)</h3>
      <label class="inline-flex items-center gap-2 text-sm text-slate-300 mb-4">
        <input type="checkbox" name="create_subscription" value="1" <?= old_raw('create_subscription', '1') ? 'checked' : '' ?> class="rounded border-slate-700 bg-slate-900 text-brand-500">
        Crear suscripción al convertir
      </label>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php
          $name = 'plan_id'; $label = 'Plan'; $required = false;
          $options = $planOptions; $value = old('plan_id', $preselectPlanId);
          include dirname(__DIR__, 2) . '/components/form_select.php';
        ?>
        <?php
          $name = 'subscription_status'; $label = 'Estado suscripción'; $required = false;
          $options = ['trial' => 'Trial', 'active' => 'Activa']; $value = old('subscription_status', 'trial');
          include dirname(__DIR__, 2) . '/components/form_select.php';
        ?>
        <?php
          $name = 'start_date'; $label = 'Inicio'; $type = 'date'; $required = false;
          $placeholder = ''; $value = old('start_date', date('Y-m-d'));
          include dirname(__DIR__, 2) . '/components/form_input.php';
        ?>
        <?php
          $name = 'end_date'; $label = 'Fin (opcional)'; $type = 'date'; $required = false;
          $placeholder = ''; $value = old('end_date', '');
          include dirname(__DIR__, 2) . '/components/form_input.php';
        ?>
        <?php
          $name = 'renewal_mode'; $label = 'Renovación'; $required = false;
          $options = ['manual' => 'Manual', 'automatic' => 'Automática']; $value = old('renewal_mode', 'manual');
          include dirname(__DIR__, 2) . '/components/form_select.php';
        ?>
      </div>
      <div class="mt-4">
        <?php
          $name = 'billing_notes'; $label = 'Notas comerciales'; $required = false;
          $rows = 3; $value = old('billing_notes', '');
          include dirname(__DIR__, 2) . '/components/form_textarea.php';
        ?>
      </div>
    </div>
  </section>

  <aside class="space-y-6">
    <div class="card p-6">
      <h3 class="text-sm font-semibold text-white mb-4">Estado inicial de institución</h3>
      <?php
        $name = 'institution_status'; $label = 'Estado'; $required = true;
        $options = ['trial' => 'Trial', 'active' => 'Activa', 'maintenance' => 'Mantenimiento'];
        $value = old('institution_status', 'trial');
        include dirname(__DIR__, 2) . '/components/form_select.php';
      ?>
    </div>
    <div class="flex items-center justify-end gap-2">
      <a href="/leads/<?= $id ?>" class="btn-ghost h-10 px-5">Cancelar</a>
      <button type="submit" class="btn-primary h-10 px-6">Convertir</button>
    </div>
  </aside>
</form>

<?php $content = ob_get_clean(); $title = 'Convertir solicitud'; include dirname(__DIR__, 2) . '/layouts/main.php';
