<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

$checks = 0;

function check(bool $condition, string $message): void
{
    global $checks;
    $checks++;
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "PASS: {$message}\n");
}

check(normalize_username('  User.One  ') === 'user.one', 'username is trimmed and lowercased');
check(valid_username('user.one_2'), 'valid username is accepted');
check(!valid_username('ab'), 'username shorter than three characters is rejected');
check(!valid_username('bad name'), 'username containing a space is rejected');
check(valid_password('secret123'), 'password with eight characters is accepted');
check(!valid_password('short'), 'password shorter than eight characters is rejected');
check(role_home('user') === 'user/index.php', 'User role goes to User dashboard');
check(role_home('admin') === 'admin/index.php', 'Admin role goes to Admin dashboard');
check(role_home('unknown') === 'login.php', 'unknown role goes to login');

fwrite(STDOUT, "Auth unit checks passed: {$checks}\n");
