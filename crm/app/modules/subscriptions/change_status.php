<?php
declare(strict_types=1);

require_auth();
require_permission('subscriptions.change_status');
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
$status = (string)($_POST['status'] ?? '');
$reason = trim((string)($_POST['reason'] ?? '')) ?: null;

if ($id < 1 || $status === '') {
    flash_set('error', 'Datos inválidos.');
    back('/subscriptions');
}

try {
    api_patch("/subscriptions/$id/status", ['body' => ['status' => $status, 'reason' => $reason]]);
    flash_set('success', "Estado actualizado a $status.");
} catch (ApiClientException $e) {
    flash_set('error', $e->getMessage() ?: 'No se pudo cambiar el estado.');
}
redirect("/subscriptions/$id");
