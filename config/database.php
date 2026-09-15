<?php
declare(strict_types=1);

$host = getenv('COFFEE_DB_HOST') ?: '127.0.0.1';
$port = getenv('COFFEE_DB_PORT') ?: '3306';
$database = getenv('COFFEE_DB_NAME') ?: 'coffee_rewards';
$username = getenv('COFFEE_DB_USER') ?: 'root';
$password = getenv('COFFEE_DB_PASSWORD') ?: 'root';

$dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
