<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('ต้องใช้ POST เพื่อออกจากระบบ');
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('คำขอไม่ถูกต้อง');
}

logout_user();
header('Location: ' . app_url('login.php'));
exit;
