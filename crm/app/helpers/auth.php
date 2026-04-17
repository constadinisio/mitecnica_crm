<?php
declare(strict_types=1);

if (!function_exists('auth_user')) {
    function auth_user(): ?array {
        return session_get('user');
    }
}

if (!function_exists('auth_check')) {
    function auth_check(): bool {
        return session_get('access_token') !== null && session_get('user') !== null;
    }
}

if (!function_exists('auth_login_api')) {
    /**
     * @return true on success, string error message on failure.
     */
    function auth_login_api(string $email, string $password): bool|string {
        try {
            $res = api_request('POST', '/auth/login', [
                'auth' => false,
                'retryOn401' => false,
                'body' => ['email' => $email, 'password' => $password],
            ]);
            $data = $res['data'] ?? null;
            if (!$data || empty($data['accessToken']) || empty($data['user'])) {
                return 'Respuesta de la API incompleta';
            }
            session_regenerate();
            session_put('user', $data['user']);
            session_put('access_token', $data['accessToken']);
            session_put('refresh_token', $data['refreshToken'] ?? null);
            return true;
        } catch (ApiClientException $e) {
            return $e->getMessage() ?: 'Credenciales inválidas';
        } catch (Throwable $e) {
            return 'No se pudo conectar con la API (' . $e->getMessage() . ')';
        }
    }
}

if (!function_exists('auth_logout_api')) {
    function auth_logout_api(): void {
        $refresh = session_get('refresh_token');
        try {
            api_request('POST', '/auth/logout', [
                'retryOn401' => false,
                'body' => $refresh ? ['refresh_token' => $refresh] : [],
            ]);
        } catch (Throwable) {
            // ignore — we clean the session anyway
        }
        session_destroy_all();
    }
}

if (!function_exists('require_auth')) {
    function require_auth(): void {
        if (auth_check()) return;
        flash_set('info', 'Iniciá sesión para continuar.');
        redirect('/login');
    }
}

if (!function_exists('require_guest')) {
    function require_guest(): void {
        if (auth_check()) redirect('/dashboard');
    }
}
