<?php
declare(strict_types=1);

putenv('COFFEE_DB_HOST=127.0.0.1');
putenv('COFFEE_DB_PORT=1');
putenv('COFFEE_DB_NAME=coffee_rewards');
putenv('COFFEE_DB_USER=root');
putenv('COFFEE_DB_PASSWORD=root');

define('COFFEE_SKIP_DATABASE', true);
require_once __DIR__ . '/../includes/bootstrap.php';

if (isset($pdo)) {
    fwrite(STDERR, "FAIL: skip mode should not create a PDO connection\n");
    exit(1);
}

if (!function_exists('current_user') || !function_exists('csrf_token')) {
    fwrite(STDERR, "FAIL: session and auth helpers should still load in skip mode\n");
    exit(1);
}

if (current_user() !== null || csrf_token() === '') {
    fwrite(STDERR, "FAIL: skip mode helpers should remain callable\n");
    exit(1);
}

echo "bootstrap lazy-load checks passed\n";
