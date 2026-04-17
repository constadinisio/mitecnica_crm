<?php
declare(strict_types=1);

require_auth();
require_permission('modules.create');

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
        $res = api_post('/modules-catalog', ['body' => $body]);
        old_clear();
        flash_set('success', 'Módulo creado.');
        redirect('/modules/' . (int)($res['data']['id'] ?? 0));
    } catch (ApiClientException $e) {
        old_set($body);
        if ($e->details && is_array($e->details)) errors_set($e->details);
        flash_set('error', $e->getMessage() ?: 'No se pudo crear el módulo.');
        redirect('/modules/new');
    }
}

ob_start();
?>
<?php
  $title = 'Nuevo módulo';
  $breadcrumbs = [['label' => 'Dashboard', 'href' => '/dashboard'], ['label' => 'Módulos', 'href' => '/modules'], ['label' => 'Nuevo']];
  include dirname(__DIR__, 2) . '/components/page_header.php';
?>
<?php $action = '/modules/new'; $submitLabel = 'Crear módulo'; $module = null; include __DIR__ . '/partials/form.php'; ?>
<?php $content = ob_get_clean(); $title = 'Nuevo módulo'; include dirname(__DIR__, 2) . '/layouts/main.php';
