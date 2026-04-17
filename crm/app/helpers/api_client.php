<?php
declare(strict_types=1);

if (!class_exists('ApiClientException')) {
    class ApiClientException extends RuntimeException {
        public int $httpStatus = 0;
        public ?string $apiCode = null;
        public ?array $details = null;
        public ?array $body = null;
    }
}

if (!function_exists('api_base_url')) {
    function api_base_url(): string {
        $cfg = require dirname(__DIR__, 2) . '/config/app.php';
        return rtrim($cfg['api_base_url'], '/');
    }
}

if (!function_exists('api_request')) {
    /**
     * @param string $method GET|POST|PUT|PATCH|DELETE
     * @param string $path e.g. "/institutions"
     * @param array $opts { query?: array, body?: array|string, headers?: array, auth?: bool, retryOn401?: bool }
     * @return array the full decoded response envelope
     */
    function api_request(string $method, string $path, array $opts = []): array {
        $url = api_base_url() . '/' . ltrim($path, '/');
        if (!empty($opts['query'])) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($opts['query']);
        }

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Request-Id: ' . bin2hex(random_bytes(8)),
        ];
        foreach (($opts['headers'] ?? []) as $h) $headers[] = $h;

        $auth = $opts['auth'] ?? true;
        if ($auth) {
            $token = session_get('access_token');
            if ($token) $headers[] = 'Authorization: Bearer ' . $token;
        }

        $body = null;
        if (array_key_exists('body', $opts)) {
            $body = is_string($opts['body']) ? $opts['body'] : json_encode($opts['body'], JSON_UNESCAPED_UNICODE);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($raw === false) {
            $e = new ApiClientException('No se pudo contactar la API: ' . $err);
            $e->httpStatus = 0;
            throw $e;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $e = new ApiClientException('Respuesta de la API inválida');
            $e->httpStatus = $status;
            $e->body = null;
            throw $e;
        }

        if ($status === 401 && $auth && ($opts['retryOn401'] ?? true)) {
            $refreshed = api_try_refresh_token();
            if ($refreshed) {
                $opts['retryOn401'] = false;
                return api_request($method, $path, $opts);
            }
            if (function_exists('flash_set')) flash_set('warn', 'Tu sesión expiró. Ingresá nuevamente.');
            session_destroy_all();
            if (!headers_sent()) header('Location: /login');
            exit;
        }

        if ($status >= 400) {
            $first = $decoded['errors'][0] ?? ['message' => 'Error desconocido', 'code' => 'UNKNOWN', 'details' => null];
            $e = new ApiClientException($first['message'] ?? 'Error');
            $e->httpStatus = $status;
            $e->apiCode = $first['code'] ?? null;
            $e->details = $first['details'] ?? null;
            $e->body = $decoded;
            throw $e;
        }

        return $decoded;
    }
}

if (!function_exists('api_get'))    { function api_get(string $p, array $o = []): array { return api_request('GET',    $p, $o); } }
if (!function_exists('api_post'))   { function api_post(string $p, array $o = []): array { return api_request('POST',   $p, $o); } }
if (!function_exists('api_put'))    { function api_put(string $p, array $o = []): array { return api_request('PUT',    $p, $o); } }
if (!function_exists('api_patch'))  { function api_patch(string $p, array $o = []): array { return api_request('PATCH',  $p, $o); } }
if (!function_exists('api_delete')) { function api_delete(string $p, array $o = []): array { return api_request('DELETE', $p, $o); } }

if (!function_exists('api_try_refresh_token')) {
    function api_try_refresh_token(): bool {
        $refresh = session_get('refresh_token');
        if (!$refresh) return false;
        try {
            $res = api_request('POST', '/auth/refresh', [
                'auth' => false,
                'retryOn401' => false,
                'body' => ['refresh_token' => $refresh],
            ]);
            $data = $res['data'] ?? null;
            if (!$data || empty($data['accessToken'])) return false;
            session_put('access_token', $data['accessToken']);
            if (!empty($data['refreshToken'])) session_put('refresh_token', $data['refreshToken']);
            if (!empty($data['user'])) session_put('user', $data['user']);
            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
