<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
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

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/order_helpers.php';
require_once __DIR__ . '/order_repository.php';
require_once __DIR__ . '/order_status.php';
require_once __DIR__ . '/admin_repository.php';
