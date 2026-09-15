<?php
declare(strict_types=1);

function resolve_vercel_script(string $requestUri, string $projectRoot): ?string
{
    $rootPath = realpath($projectRoot);
    if ($rootPath === false) {
        return null;
    }

    $urlPath = parse_url($requestUri, PHP_URL_PATH);
    if (!is_string($urlPath)) {
        return null;
    }

    $path = ltrim(rawurldecode($urlPath), '/');
    if ($path === '' || $path === 'api/index.php') {
        $path = 'index.php';
    } elseif (str_ends_with($path, '/')) {
        $path .= 'index.php';
    }

    if (str_contains($path, '..') || !preg_match('/^[A-Za-z0-9_.\/-]+\.php$/', $path)) {
        return null;
    }

    $candidatePath = $rootPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    $scriptPath = realpath($candidatePath);
    if ($scriptPath === false || !is_file($scriptPath)) {
        return null;
    }

    $rootPrefix = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (!str_starts_with($scriptPath, $rootPrefix)) {
        return null;
    }

    return $scriptPath;
}
