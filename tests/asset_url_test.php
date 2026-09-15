<?php
declare(strict_types=1);

putenv('COFFEE_APP_BASE=coffee');
require_once __DIR__ . '/../includes/functions.php';

$assetUrl = asset_url('assets/style.css');
if (preg_match('#^/coffee/assets/style\.css\?v=\d+$#', $assetUrl) !== 1) {
    fwrite(STDERR, "FAIL: asset URL should preserve base path and include a numeric version\n");
    exit(1);
}

echo "asset URL checks passed\n";
