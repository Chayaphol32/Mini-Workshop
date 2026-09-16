<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $configuredSessionDriver = strtolower(trim((string) (getenv('COFFEE_SESSION_DRIVER') ?: '')));
    $vercelEnvironment = trim((string) (getenv('VERCEL_ENV') ?: ''));
    $useDatabaseSessions = $configuredSessionDriver === 'database'
        || ($configuredSessionDriver === '' && (getenv('VERCEL') === '1' || $vercelEnvironment !== ''));

    if ($useDatabaseSessions) {
        require_once __DIR__ . '/../config/database.php';
        require_once __DIR__ . '/session_handler.php';
        $coffeeSessionHandler = new DatabaseSessionHandler($pdo);
        session_set_save_handler($coffeeSessionHandler, true);
    }

    ini_set('session.use_strict_mode', '1');
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!defined('COFFEE_SKIP_DATABASE') || ($useDatabaseSessions ?? false)) {
    require_once __DIR__ . '/../config/database.php';
}
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/order_helpers.php';
require_once __DIR__ . '/order_repository.php';
require_once __DIR__ . '/order_status.php';
require_once __DIR__ . '/admin_repository.php';
