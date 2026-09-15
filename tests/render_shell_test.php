<?php
declare(strict_types=1);

putenv('COFFEE_APP_BASE=');
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTPS'] = 'on';
define('COFFEE_SKIP_DATABASE', true);

$warnings = [];
set_error_handler(static function (int $severity, string $message) use (&$warnings): bool {
    if (in_array($severity, [E_WARNING, E_NOTICE, E_DEPRECATED], true)) {
        $warnings[] = $message;
    }
    return true;
});
ob_start();
require __DIR__ . '/../login.php';
$output = (string) ob_get_clean();
restore_error_handler();

if ($warnings !== []) {
    fwrite(STDERR, "FAIL: public shell should not emit PHP warnings\n");
    exit(1);
}

$expectations = [
    '<a class="skip-link" href="#main-content">' => 'skip link',
    'id="main-content"' => 'main landmark',
    'class="app-body role-guest"' => 'guest role class',
    '<details class="nav-menu">' => 'responsive navigation menu',
];

foreach ($expectations as $needle => $label) {
    if (!str_contains($output, $needle)) {
        fwrite(STDERR, "FAIL: rendered shell should include {$label}\n");
        exit(1);
    }
}

$pageContracts = [
    __DIR__ . '/../user/index.php' => ['page-heading-premium', 'hero-card'],
    __DIR__ . '/../user/menu.php' => ['page-heading-premium', 'hero-card'],
    __DIR__ . '/../admin/index.php' => ['page-heading-premium', 'hero-card'],
];
foreach ($pageContracts as $pagePath => $classes) {
    $source = (string) file_get_contents($pagePath);
    foreach ($classes as $class) {
        if (!str_contains($source, $class)) {
            fwrite(STDERR, "FAIL: {$pagePath} should include {$class}\n");
            exit(1);
        }
    }
}

echo "rendered shell checks passed\n";
