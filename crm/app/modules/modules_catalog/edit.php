<?php
declare(strict_types=1);

require_auth();
require_permission('modules.update');

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { http_response_code(404); echo 'Not found'; return; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $body = [
        'name'        => trim((string)($_POST['name'] ?? '')),
        'code'        => trim((string)($_POST['code'] ?? '')),
        'description' => trim((string)($_POST['description'] ?? '')) ?: null,
        'category'    => ($_POST['category'] ?? '') ?: null,
        'status'      => $_POST['status'] ?? 'active',
        'is_core'     => isset($_POST['is_core']) && $_POST['is_core'] === '1',
    ];
    try {
        api_put("/modules-catalog/$id", ['body' => $body]);
        old_clear();
        flash_set('success', 'Cambios guardados.');
        redirect("/modules/$id");
    } catch (ApiClientException $e) {
        old_set($body);
        if ($e->details && is_array($e->details)) errors_set($e->details);
        flash_set('error', $e->getMessage() ?: 'No se pudo actualizar el módulo.');
        redirect("/modules/$id/edit");
    }
}

$module = null;
try {
    $res = api_get("/modules-catalog/$id");
    $module = $res['data'] ?? null;
} catch (ApiClientException $e) {
    flash_set('error', $e->getMessage());
    redirect('/modules');
}
if (!$module) { http_response_code(404); echo 'Not found'; return; }

ob_start();
?>
<?php
  $title = 'Editar módulo';
  $subtitle = $module['name'];
  $breadcrumbs = [['label' => 'Dashboard', 'href' => '/dashboard'], ['label' => 'Módulos', 'href' => '/modules'], ['label' => $module['code'], 'href' => "/modules/$id"], ['label' => 'Editar']];
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>
<?php $action = "/modules/$id/edit"; $submitLabel = 'Guardar cambios'; include __DIR__ . '/partials/form.php'; ?>
<?php $content = ob_get_clean(); $title = 'Editar módulo'; include dirname(__DIR__, 2) . '/layouts/main.php';
