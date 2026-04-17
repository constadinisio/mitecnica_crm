<?php
declare(strict_types=1);

require_guest();

$code = trim((string)($_GET['code'] ?? ''));
if ($code === '') {
    flash_set('error', 'Callback de Google incompleto.');
    redirect('/login');
}

try {
    $res = api_get('/auth/google/callback', ['auth' => false, 'query' => ['code' => $code]]);
    $data = $res['data'] ?? null;
    if (!$data || empty($data['accessToken']) || empty($data['user'])) {
        flash_set('error', 'Respuesta de la API incompleta');
        redirect('/login');
    }
    session_regenerate();
    session_put('user', $data['user']);
    session_put('access_token', $data['accessToken']);
    session_put('refresh_token', $data['refreshToken'] ?? null);
    flash_set('success', 'Ingreso con Google exitoso.');
    redirect('/dashboard');
} catch (ApiClientException $e) {
    flash_set('error', $e->getMessage() ?: 'No se pudo completar el login con Google.');
    redirect('/login');
}
