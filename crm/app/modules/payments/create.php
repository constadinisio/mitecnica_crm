<?php
declare(strict_types=1);

require_auth();
require_permission('payments.create');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $body = [
        'institution_id'  => (int)($_POST['institution_id'] ?? 0),
        'subscription_id' => ($_POST['subscription_id'] ?? '') ? (int)$_POST['subscription_id'] : null,
        'amount'          => (float)($_POST['amount'] ?? 0),
        'currency_code'   => strtoupper(trim((string)($_POST['currency_code'] ?? 'ARS'))),
        'payment_date'    => trim((string)($_POST['payment_date'] ?? '')) ?: date('Y-m-d'),
        'status'          => $_POST['status'] ?? 'pending',
        'payment_method'  => trim((string)($_POST['payment_method'] ?? '')) ?: null,
        'reference_code'  => trim((string)($_POST['reference_code'] ?? '')) ?: null,
        'notes'           => trim((string)($_POST['notes'] ?? '')) ?: null,
    ];
    try {
        $res = api_post('/payments', ['body' => $body]);
        old_clear();
        flash_set('success', 'Pago registrado.');
        redirect('/payments/' . (int)($res['data']['id'] ?? 0));
    } catch (ApiClientException $e) {
        old_set($body);
        if ($e->details && is_array($e->details)) errors_set($e->details);
        flash_set('error', $e->getMessage() ?: 'No se pudo registrar el pago.');
        redirect('/payments/new');
    }
}

$institutions = [];
$subscriptions = [];
try {
    $institutions = api_get('/institutions', ['query' => ['limit' => 100, 'sort' => 'name', 'order' => 'asc']])['data'] ?? [];
    $subscriptions = api_get('/subscriptions', ['query' => ['limit' => 100]])['data'] ?? [];
} catch (Throwable $e) {
    flash_set('error', 'No se pudieron cargar datos: ' . $e->getMessage());
}

ob_start();
?>
<?php
  $title = 'Nuevo pago';
  $breadcrumbs = [['label' => 'Dashboard', 'href' => '/dashboard'], ['label' => 'Pagos', 'href' => '/payments'], ['label' => 'Nuevo']];
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>
<?php $action = '/payments/new'; $submitLabel = 'Registrar pago'; $payment = null; include __DIR__ . '/partials/form.php'; ?>
<?php $content = ob_get_clean(); $title = 'Nuevo pago'; include dirname(__DIR__, 2) . '/layouts/main.php';
