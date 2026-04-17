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
            'active'         => 'Activa',
            'trial'          => 'Trial',
            'maintenance'    => 'Mantenimiento',
            'suspended'      => 'Suspendida',
            'expired'        => 'Expirada',
            'inactive'       => 'Inactiva',
            'archived'       => 'Archivada',
            'canceled'       => 'Cancelada',
            'optimal'        => 'Optimal',
            'updating'       => 'Updating',
            'offline'        => 'Offline',
            'pending'        => 'Pendiente',
            'approved'       => 'Aprobado',
            'rejected'       => 'Rechazado',
            'new'            => 'Nueva',
            'contacted'      => 'Contactada',
            'in_negotiation' => 'En negociación',
            'converted'      => 'Convertida',
            'lost'           => 'Perdida',
            default          => (string) ($status ?? '—'),
        };
    }
}

if (!function_exists('status_class')) {
    function status_class(?string $status): string {
        return match ($status) {
            'active', 'optimal', 'approved', 'converted'      => 'badge-green',
            'trial', 'updating', 'pending', 'in_negotiation'  => 'badge-amber',
            'maintenance', 'new'                              => 'badge-blue',
            'suspended', 'contacted'                          => 'badge-rose',
            'expired', 'offline', 'rejected'                  => 'badge-red',
            'inactive', 'archived', 'canceled', 'lost'        => 'badge-slate',
            default                                           => 'badge-slate',
        };
    }
}

if (!function_exists('lead_status_label')) {
    function lead_status_label(?string $status): string { return status_label($status); }
}

if (!function_exists('format_money')) {
    function format_money($amount, ?string $currency = 'ARS'): string {
        if ($amount === null || $amount === '') return '—';
        $n = (float) $amount;
        $currency = $currency ?: 'ARS';
        $formatted = number_format($n, 2, ',', '.');
        return match (strtoupper($currency)) {
            'ARS' => '$ ' . $formatted,
            'USD' => 'US$ ' . $formatted,
            default => $currency . ' ' . $formatted,
        };
    }
}

if (!function_exists('frequency_label')) {
    function frequency_label(?string $freq): string {
        return match ($freq) {
            'monthly'   => 'Mensual',
            'quarterly' => 'Trimestral',
            'yearly'    => 'Anual',
            'custom'    => 'Personalizado',
            default     => (string) ($freq ?? '—'),
        };
    }
}

if (!function_exists('category_label')) {
    function category_label(?string $cat): string {
        return match ($cat) {
            'academic'       => 'Académico',
            'communication'  => 'Comunicación',
            'administration' => 'Administración',
            'technical'      => 'Técnico',
            'analytics'      => 'Analítica',
            'other'          => 'Otro',
            default          => (string) ($cat ?? '—'),
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
