<?php
declare(strict_types=1);

require_auth();
require_permission('leads.assign');
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
$userId = isset($_POST['user_id']) && $_POST['user_id'] !== '' ? (int)$_POST['user_id'] : null;
if ($id < 1) {
    flash_set('error', 'Datos inválidos.');
    back('/leads');
}

try {
    api_patch("/leads/$id/assign", ['body' => ['user_id' => $userId]]);
    flash_set('success', $userId ? 'Solicitud asignada.' : 'Solicitud liberada.');
} catch (ApiClientException $e) {
    flash_set('error', $e->getMessage() ?: 'No se pudo asignar.');
}
redirect("/leads/$id");
