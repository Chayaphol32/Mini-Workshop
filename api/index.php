<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/vercel_dispatch.php';

$scriptPath = resolve_vercel_script(
    (string) ($_SERVER['REQUEST_URI'] ?? '/'),
    dirname(__DIR__)
);

if ($scriptPath === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Not Found";
    exit;
}

require $scriptPath;
