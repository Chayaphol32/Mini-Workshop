<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function parse_id(?string $value): ?int
{
    if ($value === null || filter_var($value, FILTER_VALIDATE_INT) === false) {
        return null;
    }

    $id = (int) $value;
    return $id > 0 ? $id : null;
}

function parse_positive_amount(?string $value): ?float
{
    if ($value === null) {
        return null;
    }

    $value = trim($value);
    if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
        return null;
    }

    $amount = (float) $value;
    return $amount > 0 ? $amount : null;
}

function calculate_points(float $amount): int
{
    return (int) floor($amount / 10);
}

function member_level(int $points): array
{
    if ($points >= 10000) {
        return ['key' => 'platinum', 'label' => 'Platinum'];
    }
    if ($points >= 5000) {
        return ['key' => 'gold', 'label' => 'Gold'];
    }
    if ($points >= 1000) {
        return ['key' => 'silver', 'label' => 'Silver'];
    }
    return ['key' => 'member', 'label' => 'Member'];
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $value): bool
{
    return is_string($value)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $value);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function pull_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($message) ? $message : null;
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function money(float|string|int $amount): string
{
    return number_format((float) $amount, 2);
}

function thai_date(string $date): string
{
    $timestamp = strtotime($date);
    return $timestamp === false ? $date : date('d/m/Y', $timestamp);
}

function thai_datetime(string $date): string
{
    $timestamp = strtotime($date);
    return $timestamp === false ? $date : date('d/m/Y H:i', $timestamp);
}

function normalize_username(string $username): string
{
    return strtolower(trim($username));
}

function valid_username(string $username): bool
{
    return preg_match('/^[a-z0-9][a-z0-9._-]{2,39}$/', $username) === 1;
}

function valid_password(string $password): bool
{
    return strlen($password) >= 8 && strlen($password) <= 255;
}

function role_home(string $role): string
{
    return match ($role) {
        'admin' => 'admin/index.php',
        'user' => 'user/index.php',
        default => 'login.php',
    };
}

function app_url(string $path): string
{
    $configuredBase = trim((string) (getenv('COFFEE_APP_BASE') ?: ''), '/');
    $base = $configuredBase === '' ? '' : '/' . $configuredBase;
    return $base . '/' . ltrim($path, '/');
}

function asset_url(string $path): string
{
    $normalizedPath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $normalizedPath;
    $version = is_file($filePath) ? (string) (filemtime($filePath) ?: 1) : '1';
    $url = app_url($path);

    return $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . rawurlencode($version);
}
