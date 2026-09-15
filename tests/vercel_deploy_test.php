<?php
declare(strict_types=1);

function expect_vercel(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$projectRoot = dirname(__DIR__);
$dispatcher = $projectRoot . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'vercel_dispatch.php';
$vercelConfig = $projectRoot . DIRECTORY_SEPARATOR . 'vercel.json';
$databaseConfig = $projectRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

expect_vercel(is_file($dispatcher), 'Vercel dispatcher should exist');
expect_vercel(is_file($vercelConfig), 'vercel.json should exist');
expect_vercel(is_file($databaseConfig), 'database config should exist');

$databaseSource = (string) file_get_contents($databaseConfig);
expect_vercel(
    str_contains($databaseSource, "getenv('TIDB_HOST')")
        && str_contains($databaseSource, "getenv('TIDB_PORT')")
        && str_contains($databaseSource, "getenv('TIDB_DATABASE')")
        && str_contains($databaseSource, "getenv('TIDB_USER')")
        && str_contains($databaseSource, "getenv('TIDB_PASSWORD')"),
    'Database config should support TiDB environment variables'
);
expect_vercel(
    str_contains($databaseSource, 'MYSQL_ATTR_SSL_VERIFY_SERVER_CERT'),
    'TiDB connections should enable TLS certificate verification'
);
expect_vercel(
    str_contains($databaseSource, "defined('Pdo\\\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT')")
        && str_contains($databaseSource, "defined('Pdo\\\\Mysql::ATTR_SSL_CA')"),
    'TLS options should support PHP 8.5 PDO MySQL constants without deprecation warnings'
);

require_once $dispatcher;

$config = json_decode((string) file_get_contents($vercelConfig), true);
expect_vercel(is_array($config), 'vercel.json should contain valid JSON');
expect_vercel(
    ($config['functions']['api/index.php']['runtime'] ?? null) === 'vercel-php@0.9.0',
    'Vercel should use the PHP community runtime'
);
expect_vercel(
    ($config['routes'][0]['src'] ?? null) === '/assets/(.*)'
        && ($config['routes'][0]['dest'] ?? null) === '/assets/$1',
    'Only assets should bypass the PHP runtime'
);
expect_vercel(
    ($config['routes'][1]['src'] ?? null) === '/(.*)'
        && ($config['routes'][1]['dest'] ?? null) === '/api/index.php',
    'All application requests should use the PHP runtime'
);

$indexPath = realpath($projectRoot . DIRECTORY_SEPARATOR . 'index.php');
$loginPath = realpath($projectRoot . DIRECTORY_SEPARATOR . 'login.php');
$adminPath = realpath($projectRoot . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'index.php');
$userPath = realpath($projectRoot . DIRECTORY_SEPARATOR . 'user' . DIRECTORY_SEPARATOR . 'orders.php');

expect_vercel(resolve_vercel_script('/', $projectRoot) === $indexPath, 'Root should dispatch to index.php');
expect_vercel(resolve_vercel_script('/login.php', $projectRoot) === $loginPath, 'Login should dispatch to login.php');
expect_vercel(resolve_vercel_script('/admin/index.php', $projectRoot) === $adminPath, 'Admin route should dispatch to its PHP page');
expect_vercel(resolve_vercel_script('/user/orders.php?id=1', $projectRoot) === $userPath, 'User route should preserve the PHP page');
expect_vercel(resolve_vercel_script('/assets/style.css', $projectRoot) === null, 'Static assets should not be executed as PHP');
expect_vercel(resolve_vercel_script('/../../config/database.php', $projectRoot) === null, 'Traversal paths should be rejected');

echo "vercel deploy tests passed\n";
