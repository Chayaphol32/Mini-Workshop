<?php
declare(strict_types=1);

if (!defined('COFFEE_SKIP_DATABASE')) {
    define('COFFEE_SKIP_DATABASE', true);
}
require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user();
if ($user === null) {
    redirect(app_url('login.php'));
}
redirect(app_url(role_home((string) $user['role'])));
