<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');
redirect(app_url('admin/member_create.php'));
