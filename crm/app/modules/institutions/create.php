<?php
declare(strict_types=1);

require_auth();
require_permission('institutions.create');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $body = [
        'name'              => trim((string)($_POST['name'] ?? '')),
        'subdomain'         => trim((string)($_POST['subdomain'] ?? '')),
        'slug'              => trim((string)($_POST['slug'] ?? '')) ?: null,
        'contact_email'     => trim((string)($_POST['contact_email'] ?? '')),
        'contact_phone'     => trim((string)($_POST['contact_phone'] ?? '')) ?: null,
        'address'           => trim((string)($_POST['address'] ?? '')) ?: null,
        'responsible_name'  => trim((string)($_POST['responsible_name'] ?? '')) ?: null,
        'responsible_email' => trim((string)($_POST['responsible_email'] ?? '')) ?: null,
        'notes_internal'    => trim((string)($_POST['notes_internal'] ?? '')) ?: null,
        'current_plan_name' => trim((string)($_POST['current_plan_name'] ?? '')) ?: null,
        'expiration_date'   => trim((string)($_POST['expiration_date'] ?? '')) ?: null,
        'status'            => $_POST['status'] ?? 'trial',
    ];

    try {
        $res = api_post('/institutions', ['body' => $body]);
        $newId = $res['data']['id'] ?? null;
        old_clear();
        flash_set('success', 'Institución creada correctamente.');
        redirect($newId ? "/institutions/$newId" : '/institutions');
    } catch (ApiClientException $e) {
        old_set($body);
        if ($e->details && is_array($e->details)) errors_set($e->details);
        flash_set('error', $e->getMessage() ?: 'No se pudo crear la institución.');
        redirect('/institutions/new');
    }
}

$availablePlans = [];
try {
    $availablePlans = api_get('/plans', ['query' => ['limit' => 100, 'status' => 'active']])['data'] ?? [];
} catch (Throwable) { /* silent: form still works with a custom value */ }

ob_start();
?>
<?php
  $title = 'Nueva institución';
  $subtitle = 'Registrá un nuevo cliente / institución bajo gestión.';
  $breadcrumbs = [
    ['label' => 'Dashboard', 'href' => '/dashboard'],
    ['label' => 'Instituciones', 'href' => '/institutions'],
    ['label' => 'Nueva'],
  ];
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>

<?php
  $action = '/institutions/new';
  $submitLabel = 'Crear institución';
  $institution = null;
  include __DIR__ . '/partials/form.php';
?>

<?php
$content = ob_get_clean();
$title = 'Nueva institución';
include dirname(__DIR__, 2) . '/layouts/main.php';
