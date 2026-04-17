<?php
declare(strict_types=1);

require_auth();
require_permission('subscriptions.update');

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { http_response_code(404); echo 'Not found'; return; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $body = [
        'plan_id'        => (int)($_POST['plan_id'] ?? 0),
        'status'         => $_POST['status'] ?? null,
        'start_date'     => trim((string)($_POST['start_date'] ?? '')) ?: null,
        'end_date'       => trim((string)($_POST['end_date'] ?? '')) ?: null,
        'trial_ends_at'  => trim((string)($_POST['trial_ends_at'] ?? '')) ?: null,
        'renewal_mode'   => $_POST['renewal_mode'] ?? null,
        'billing_notes'  => trim((string)($_POST['billing_notes'] ?? '')) ?: null,
    ];
    try {
        api_put("/subscriptions/$id", ['body' => $body]);
        old_clear();
        flash_set('success', 'Cambios guardados.');
        redirect("/subscriptions/$id");
    } catch (ApiClientException $e) {
        old_set($body);
        if ($e->details && is_array($e->details)) errors_set($e->details);
        flash_set('error', $e->getMessage() ?: 'No se pudo actualizar la suscripción.');
        redirect("/subscriptions/$id/edit");
    }
}

$sub = null;
$institutions = [];
$plans = [];
try {
    $sub = api_get("/subscriptions/$id")['data'] ?? null;
    $institutions = api_get('/institutions', ['query' => ['limit' => 100, 'sort' => 'name', 'order' => 'asc']])['data'] ?? [];
    $plans = api_get('/plans', ['query' => ['limit' => 100, 'status' => 'active']])['data'] ?? [];
} catch (ApiClientException $e) {
    flash_set('error', $e->getMessage());
    redirect('/subscriptions');
}
if (!$sub) { http_response_code(404); echo 'Not found'; return; }

ob_start();
?>
<?php
  $title = 'Editar suscripción';
  $subtitle = ($sub['institution_name'] ?? '') . ' · ' . ($sub['plan_name'] ?? '');
  $breadcrumbs = [
    ['label' => 'Dashboard', 'href' => '/dashboard'],
    ['label' => 'Suscripciones', 'href' => '/subscriptions'],
    ['label' => '#' . $id, 'href' => "/subscriptions/$id"],
    ['label' => 'Editar'],
  ];
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>
<?php $action = "/subscriptions/$id/edit"; $submitLabel = 'Guardar cambios'; include __DIR__ . '/partials/form.php'; ?>
<?php $content = ob_get_clean(); $title = 'Editar suscripción'; include dirname(__DIR__, 2) . '/layouts/main.php';
