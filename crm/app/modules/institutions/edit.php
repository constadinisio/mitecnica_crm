<?php
declare(strict_types=1);

require_auth();
require_permission('institutions.update');

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { http_response_code(404); echo 'Not found'; return; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $body = [
        'name'              => trim((string)($_POST['name'] ?? '')),
        'subdomain'         => trim((string)($_POST['subdomain'] ?? '')),
        'slug'              => trim((string)($_POST['slug'] ?? '')) ?: null,
        'contact_email'     => trim((string)($_POST['contact_email'] ?? '')),
        'contact_phone'     => trim((string)($_POST['contact_phone'] ?? '')) ?: null,
        'address'           => trim((string)($_POST['address'] ?? '')) ?: null,
        'responsible_name'      => trim((string)($_POST['responsible_name'] ?? '')) ?: null,
        'responsible_last_name' => trim((string)($_POST['responsible_last_name'] ?? '')) ?: null,
        'responsible_email'     => trim((string)($_POST['responsible_email'] ?? '')) ?: null,
        'notes_internal'    => trim((string)($_POST['notes_internal'] ?? '')) ?: null,
        'status'            => $_POST['status'] ?? null,
    ];
    try {
        api_put("/institutions/$id", ['body' => $body]);
        old_clear();
        flash_set('success', 'Cambios guardados.');
        redirect("/institutions/$id");
    } catch (ApiClientException $e) {
        old_set($body);
        if ($e->details && is_array($e->details)) errors_set($e->details);
        flash_set('error', $e->getMessage() ?: 'No se pudo actualizar.');
        redirect("/institutions/$id/edit");
    }
}

$institution = null;
try {
    $res = api_get("/institutions/$id");
    $institution = $res['data']['institution'] ?? null;
} catch (ApiClientException $e) {
    flash_set('error', $e->getMessage());
    redirect('/institutions');
}

if (!$institution) { http_response_code(404); echo 'Not found'; return; }

ob_start();
?>
<?php
  $title = 'Editar institución';
  $subtitle = $institution['name'] . ' · ' . $institution['public_code'];
  $breadcrumbs = [
    ['label' => 'Dashboard', 'href' => '/dashboard'],
    ['label' => 'Instituciones', 'href' => '/institutions'],
    ['label' => $institution['public_code'], 'href' => "/institutions/$id"],
    ['label' => 'Editar'],
  ];
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>

<?php
  $action = "/institutions/$id/edit";
  $submitLabel = 'Guardar cambios';
  include __DIR__ . '/partials/form.php';
?>

<?php
$content = ob_get_clean();
$title = 'Editar institución';
include dirname(__DIR__, 2) . '/layouts/main.php';
