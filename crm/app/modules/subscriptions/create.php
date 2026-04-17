<?php
declare(strict_types=1);

require_auth();
require_permission('subscriptions.create');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $body = [
        'institution_id' => (int)($_POST['institution_id'] ?? 0),
        'plan_id'        => (int)($_POST['plan_id'] ?? 0),
        'status'         => $_POST['status'] ?? 'trial',
        'start_date'     => trim((string)($_POST['start_date'] ?? '')),
        'end_date'       => trim((string)($_POST['end_date'] ?? '')) ?: null,
        'trial_ends_at'  => trim((string)($_POST['trial_ends_at'] ?? '')) ?: null,
        'renewal_mode'   => $_POST['renewal_mode'] ?? 'manual',
        'billing_notes'  => trim((string)($_POST['billing_notes'] ?? '')) ?: null,
    ];
    try {
        $res = api_post('/subscriptions', ['body' => $body]);
        old_clear();
        flash_set('success', 'Suscripción creada.');
        redirect('/subscriptions/' . (int)($res['data']['id'] ?? 0));
    } catch (ApiClientException $e) {
        old_set($body);
        if ($e->details && is_array($e->details)) errors_set($e->details);
        flash_set('error', $e->getMessage() ?: 'No se pudo crear la suscripción.');
        redirect('/subscriptions/new');
    }
}

$institutions = [];
$plans = [];
try {
    $institutions = api_get('/institutions', ['query' => ['limit' => 100, 'sort' => 'name', 'order' => 'asc']])['data'] ?? [];
    $plans = api_get('/plans', ['query' => ['limit' => 100, 'status' => 'active']])['data'] ?? [];
} catch (Throwable $e) {
    flash_set('error', 'No se pudieron cargar datos: ' . $e->getMessage());
}

ob_start();
?>
<?php
  $title = 'Nueva suscripción';
  $breadcrumbs = [['label' => 'Dashboard', 'href' => '/dashboard'], ['label' => 'Suscripciones', 'href' => '/subscriptions'], ['label' => 'Nueva']];
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>
<?php $action = '/subscriptions/new'; $submitLabel = 'Crear suscripción'; $sub = null; include __DIR__ . '/partials/form.php'; ?>
<?php $content = ob_get_clean(); $title = 'Nueva suscripción'; include dirname(__DIR__, 2) . '/layouts/main.php';
