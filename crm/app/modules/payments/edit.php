<?php
declare(strict_types=1);

require_auth();
require_permission('payments.update');

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { http_response_code(404); echo 'Not found'; return; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $body = [
        'subscription_id' => ($_POST['subscription_id'] ?? '') ? (int)$_POST['subscription_id'] : null,
        'amount'          => (float)($_POST['amount'] ?? 0),
        'currency_code'   => strtoupper(trim((string)($_POST['currency_code'] ?? 'ARS'))),
        'payment_date'    => trim((string)($_POST['payment_date'] ?? '')) ?: null,
        'status'          => $_POST['status'] ?? null,
        'payment_method'  => trim((string)($_POST['payment_method'] ?? '')) ?: null,
        'reference_code'  => trim((string)($_POST['reference_code'] ?? '')) ?: null,
        'notes'           => trim((string)($_POST['notes'] ?? '')) ?: null,
    ];
    try {
        api_put("/payments/$id", ['body' => $body]);
        old_clear();
        flash_set('success', 'Cambios guardados.');
        redirect("/payments/$id");
    } catch (ApiClientException $e) {
        old_set($body);
        if ($e->details && is_array($e->details)) errors_set($e->details);
        flash_set('error', $e->getMessage() ?: 'No se pudo actualizar el pago.');
        redirect("/payments/$id/edit");
    }
}

$payment = null;
$institutions = [];
$subscriptions = [];
try {
    $payment = api_get("/payments/$id")['data'] ?? null;
    $institutions = api_get('/institutions', ['query' => ['limit' => 100, 'sort' => 'name', 'order' => 'asc']])['data'] ?? [];
    $subscriptions = api_get('/subscriptions', ['query' => ['limit' => 100]])['data'] ?? [];
} catch (ApiClientException $e) {
    flash_set('error', $e->getMessage());
    redirect('/payments');
}
if (!$payment) { http_response_code(404); echo 'Not found'; return; }

ob_start();
?>
<?php
  $title = 'Editar pago';
  $subtitle = '#' . $id . ' · ' . ($payment['institution_name'] ?? '');
  $breadcrumbs = [['label' => 'Dashboard', 'href' => '/dashboard'], ['label' => 'Pagos', 'href' => '/payments'], ['label' => '#' . $id, 'href' => "/payments/$id"], ['label' => 'Editar']];
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>
<?php $action = "/payments/$id/edit"; $submitLabel = 'Guardar cambios'; include __DIR__ . '/partials/form.php'; ?>
<?php $content = ob_get_clean(); $title = 'Editar pago'; include dirname(__DIR__, 2) . '/layouts/main.php';
