<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');
$query = trim((string) ($_SERVER['QUERY_STRING'] ?? ''));
redirect(app_url('admin/member_detail.php' . ($query === '' ? '' : '?' . $query)));
