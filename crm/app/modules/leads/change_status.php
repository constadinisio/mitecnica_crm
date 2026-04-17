<?php
declare(strict_types=1);

require_auth();
require_permission('leads.change_status');
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
$status = (string)($_POST['status'] ?? '');
if ($id < 1 || $status === '') {
    flash_set('error', 'Datos inválidos.');
    back('/leads');
}

try {
    api_patch("/leads/$id/status", ['body' => ['status' => $status, 'reason' => trim((string)($_POST['reason'] ?? '')) ?: null]]);
    flash_set('success', 'Estado actualizado.');
} catch (ApiClientException $e) {
    flash_set('error', $e->getMessage() ?: 'No se pudo cambiar el estado.');
}
redirect("/leads/$id");
