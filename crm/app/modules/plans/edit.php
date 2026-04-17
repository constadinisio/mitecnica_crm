<?php
declare(strict_types=1);

require_auth();
require_permission('plans.update');

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { http_response_code(404); echo 'Not found'; return; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $body = [
        'name'              => trim((string)($_POST['name'] ?? '')),
        'code'              => trim((string)($_POST['code'] ?? '')),
        'description'       => trim((string)($_POST['description'] ?? '')) ?: null,
        'price_amount'      => (float)($_POST['price_amount'] ?? 0),
        'currency_code'     => strtoupper(trim((string)($_POST['currency_code'] ?? 'ARS'))),
        'billing_frequency' => $_POST['billing_frequency'] ?? 'monthly',
        'status'            => $_POST['status'] ?? 'active',
        'is_custom'         => isset($_POST['is_custom']) && $_POST['is_custom'] === '1',
    ];
    try {
        api_put("/plans/$id", ['body' => $body]);
        old_clear();
        flash_set('success', 'Cambios guardados.');
        redirect("/plans/$id");
    } catch (ApiClientException $e) {
        old_set($body);
        if ($e->details && is_array($e->details)) errors_set($e->details);
        flash_set('error', $e->getMessage() ?: 'No se pudo actualizar el plan.');
        redirect("/plans/$id/edit");
    }
}

$plan = null;
try {
    $res = api_get("/plans/$id");
    $plan = $res['data'] ?? null;
} catch (ApiClientException $e) {
    flash_set('error', $e->getMessage());
    redirect('/plans');
}
if (!$plan) { http_response_code(404); echo 'Not found'; return; }

ob_start();
?>
<?php
  $title = 'Editar plan';
  $subtitle = $plan['name'];
  $breadcrumbs = [
    ['label' => 'Dashboard', 'href' => '/dashboard'],
    ['label' => 'Planes', 'href' => '/plans'],
    ['label' => $plan['code'], 'href' => "/plans/$id"],
    ['label' => 'Editar'],
  ];
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>
<?php $action = "/plans/$id/edit"; $submitLabel = 'Guardar cambios'; include __DIR__ . '/partials/form.php'; ?>
<?php $content = ob_get_clean(); $title = 'Editar plan'; include dirname(__DIR__, 2) . '/layouts/main.php';
