<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

if (!login_user($pdo, 'ADMIN', 'admin123')) {
    throw new RuntimeException('admin login failed');
}
$admin = current_user();
if ($admin === null || $admin['role'] !== 'admin' || $admin['member_id'] !== null) {
    throw new RuntimeException('admin role/linkage is incorrect');
}
logout_user();

if (!login_user($pdo, 'USER1', 'user123')) {
    throw new RuntimeException('user login failed');
}
$user = current_user();
if ($user === null || $user['role'] !== 'user' || $user['member_id'] === null || $user['member_no'] !== 'M0001') {
    throw new RuntimeException('user role/linkage is incorrect');
}
logout_user();

if (login_user($pdo, 'user1', 'wrong-password')) {
    throw new RuntimeException('wrong password was accepted');
}
restore_error_handler();
fwrite(STDOUT, "Auth repository smoke checks passed.\n");
