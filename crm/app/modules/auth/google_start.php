<?php
declare(strict_types=1);

require_guest();

try {
    $res = api_get('/auth/google', ['auth' => false]);
    $data = $res['data'] ?? [];
    if (empty($data['enabled']) || empty($data['url'])) {
        flash_set('warn', 'El inicio de sesión con Google no está habilitado.');
        redirect('/login');
    }
    redirect($data['url']);
} catch (Throwable $e) {
    flash_set('error', 'No se pudo iniciar el flujo de Google: ' . $e->getMessage());
    redirect('/login');
}
