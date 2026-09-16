<?php
declare(strict_types=1);

$handlerPath = __DIR__ . '/../includes/session_handler.php';
if (!is_file($handlerPath)) {
    fwrite(STDERR, "FAIL: database session handler should exist\n");
    exit(1);
}

putenv('COFFEE_DB_HOST=127.0.0.1');
putenv('COFFEE_DB_PORT=3306');
putenv('COFFEE_DB_NAME=coffee_rewards');
putenv('COFFEE_DB_USER=root');
putenv('COFFEE_DB_PASSWORD=root');

require_once __DIR__ . '/../config/database.php';
require_once $handlerPath;

function expect_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException("FAIL: {$message}");
    }
    echo "PASS: {$message}\n";
}

$handler = new DatabaseSessionHandler($pdo);
$sessionId = 'tdd_' . bin2hex(random_bytes(12));
$payload = 'auth_user|a:1:{s:4:"role";s:5:"admin";}';

try {
    expect_true($handler->open('', 'PHPSESSID'), 'database session handler opens');
    expect_true($handler->read($sessionId) === '', 'missing session reads as empty');
    expect_true($handler->write($sessionId, $payload), 'session payload is written');
    expect_true($handler->read($sessionId) === $payload, 'session payload survives a new read');
    expect_true($handler->destroy($sessionId), 'session can be destroyed');
    expect_true($handler->read($sessionId) === '', 'destroyed session reads as empty');
} finally {
    $cleanup = $pdo->prepare('DELETE FROM app_sessions WHERE id = :id');
    $cleanup->execute(['id' => $sessionId]);
}

echo "Database session handler checks passed.\n";
