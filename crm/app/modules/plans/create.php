<?php
declare(strict_types=1);

require_auth();
require_permission('plans.create');

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
        $res = api_post('/plans', ['body' => $body]);
        old_clear();
        flash_set('success', 'Plan creado.');
        redirect('/plans/' . (int)($res['data']['id'] ?? 0));
    } catch (ApiClientException $e) {
        old_set($body);
        if ($e->details && is_array($e->details)) errors_set($e->details);
        flash_set('error', $e->getMessage() ?: 'No se pudo crear el plan.');
        redirect('/plans/new');
    }
}

ob_start();
?>
<?php
  $title = 'Nuevo plan';
  $breadcrumbs = [['label' => 'Dashboard', 'href' => '/dashboard'], ['label' => 'Planes', 'href' => '/plans'], ['label' => 'Nuevo']];
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>
<?php $action = '/plans/new'; $submitLabel = 'Crear plan'; $plan = null; include __DIR__ . '/partials/form.php'; ?>
<?php $content = ob_get_clean(); $title = 'Nuevo plan'; include dirname(__DIR__, 2) . '/layouts/main.php';
