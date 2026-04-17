<?php
declare(strict_types=1);

if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $iso, string $fallback = '—'): string {
        if (!$iso) return $fallback;
        try {
            $d = new DateTimeImmutable($iso);
            return $d->format('d/m/Y');
        } catch (Throwable) {
            return $fallback;
        }
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(?string $iso, string $fallback = '—'): string {
        if (!$iso) return $fallback;
        try {
            $d = new DateTimeImmutable($iso);
            return $d->format('d/m/Y H:i');
        } catch (Throwable) {
            return $fallback;
        }
    }
}

if (!function_exists('format_relative')) {
    function format_relative(?string $iso, string $fallback = '—'): string {
        if (!$iso) return $fallback;
        try {
            $d = new DateTimeImmutable($iso);
            $now = new DateTimeImmutable('now');
            $diff = $now->getTimestamp() - $d->getTimestamp();
            if ($diff < 60) return 'hace instantes';
            if ($diff < 3600) return 'hace ' . (int) ($diff / 60) . ' min';
            if ($diff < 86400) return 'hace ' . (int) ($diff / 3600) . ' h';
            if ($diff < 2592000) return 'hace ' . (int) ($diff / 86400) . ' d';
            return format_date($iso, $fallback);
        } catch (Throwable) {
            return $fallback;
        }
    }
}

if (!function_exists('days_until')) {
    function days_until(?string $isoDate): ?int {
        if (!$isoDate) return null;
        try {
            $d = new DateTimeImmutable($isoDate);
            $now = (new DateTimeImmutable('today'));
            $diff = $now->diff($d);
            return ($diff->invert ? -1 : 1) * $diff->days;
        } catch (Throwable) {
            return null;
        }
    }
}

if (!function_exists('status_label')) {
    function status_label(?string $status): string {
        return match ($status) {
            'active'       => 'Activa',
            'trial'        => 'Trial',
            'maintenance'  => 'Mantenimiento',
            'suspended'    => 'Suspendida',
            'expired'      => 'Expirada',
            'inactive'     => 'Inactiva',
            'optimal'      => 'Optimal',
            'updating'     => 'Updating',
            'offline'      => 'Offline',
            'pending'      => 'Pending',
            default        => (string) ($status ?? '—'),
        };
    }
}

if (!function_exists('status_class')) {
    function status_class(?string $status): string {
        return match ($status) {
            'active', 'optimal'            => 'badge-green',
            'trial', 'updating', 'pending' => 'badge-amber',
            'maintenance'                  => 'badge-blue',
            'suspended'                    => 'badge-rose',
            'expired', 'offline'           => 'badge-red',
            'inactive'                     => 'badge-slate',
            default                        => 'badge-slate',
        };
    }
}

if (!function_exists('initials')) {
    function initials(?string $name): string {
        if (!$name) return 'MT';
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = array_map(fn($p) => mb_substr($p, 0, 1), array_slice($parts, 0, 2));
        return mb_strtoupper(implode('', $letters) ?: 'MT');
    }
}
