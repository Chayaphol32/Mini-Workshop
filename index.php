<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user();
if ($user === null) {
    redirect(app_url('login.php'));
}
redirect(app_url(role_home((string) $user['role'])));
