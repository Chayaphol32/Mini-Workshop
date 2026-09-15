<?php
declare(strict_types=1);

putenv('COFFEE_APP_BASE=');
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTPS'] = 'on';
define('COFFEE_SKIP_DATABASE', true);

ob_start();
require __DIR__ . '/../login.php';
$output = (string) ob_get_clean();

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

echo "rendered shell checks passed\n";
