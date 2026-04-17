<?php
declare(strict_types=1);

require_auth();
require_permission('institutions.override_modules');

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id < 1) { http_response_code(404); echo 'Not found'; return; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/institutions/' . $id . '?tab=modules');
}

csrf_verify();

$modes   = (array)($_POST['override_mode'] ?? []);
$notes   = (array)($_POST['override_notes'] ?? []);
$overrides = [];
foreach ($modes as $moduleId => $mode) {
    $moduleId = (int)$moduleId;
    if ($moduleId < 1) continue;
    if (!in_array($mode, ['force_enabled', 'force_disabled'], true)) continue;
    $overrides[] = [
        'module_id'     => $moduleId,
        'override_mode' => $mode,
        'notes'         => isset($notes[$moduleId]) ? substr((string)$notes[$moduleId], 0, 500) : null,
    ];
}

try {
    api_put("/institutions/$id/modules-overrides", ['body' => ['overrides' => $overrides]]);
    flash_set('success', 'Overrides de módulos actualizados.');
} catch (ApiClientException $e) {
    flash_set('error', $e->getMessage() ?: 'No se pudieron guardar los overrides.');
}

redirect('/institutions/' . $id . '?tab=modules');
