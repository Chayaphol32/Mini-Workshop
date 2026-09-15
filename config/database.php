<?php
declare(strict_types=1);

$tidbHost = getenv('TIDB_HOST') ?: '';
$usingTiDb = $tidbHost !== '' && !(getenv('COFFEE_DB_HOST') ?: '');

$host = getenv('COFFEE_DB_HOST') ?: ($tidbHost ?: '127.0.0.1');
$port = getenv('COFFEE_DB_PORT') ?: (getenv('TIDB_PORT') ?: '3306');
$database = getenv('COFFEE_DB_NAME') ?: (getenv('TIDB_DATABASE') ?: 'coffee_rewards');
$username = getenv('COFFEE_DB_USER') ?: (getenv('TIDB_USER') ?: 'root');
$password = getenv('COFFEE_DB_PASSWORD') ?: (getenv('TIDB_PASSWORD') ?: 'root');

$dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

if ($usingTiDb) {
    $persistent = filter_var(getenv('TIDB_PERSISTENT') ?: '1', FILTER_VALIDATE_BOOLEAN);
    if ($persistent) {
        $pdoOptions[PDO::ATTR_PERSISTENT] = true;
    }

    if (defined('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT')) {
        $pdoOptions[constant('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT')] = true;
    } else {
        $pdoOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    $caPath = getenv('TIDB_SSL_CA') ?: '';
    if ($caPath === '') {
        foreach (['/etc/ssl/certs/ca-certificates.crt', '/etc/ssl/cert.pem'] as $candidate) {
            if (is_file($candidate)) {
                $caPath = $candidate;
                break;
            }
        }
    }

    if ($caPath !== '') {
        if (defined('Pdo\\Mysql::ATTR_SSL_CA')) {
            $pdoOptions[constant('Pdo\\Mysql::ATTR_SSL_CA')] = $caPath;
        } else {
            $pdoOptions[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
        }
    }
}

$pdo = new PDO($dsn, $username, $password, $pdoOptions);
