<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('user');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_url('user/menu.php'));
}

$user = current_user();
$rawQuantities = $_POST['quantities'] ?? [];
$quantities = is_array($rawQuantities) ? $rawQuantities : [];
$selectedQuantities = [];
foreach ($quantities as $menuId => $rawQuantity) {
    if ((string) $rawQuantity !== '' && (string) $rawQuantity !== '0') {
        $selectedQuantities[(string) $menuId] = $rawQuantity;
    }
}

$errors = [];
if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    $errors[] = 'คำขอไม่ถูกต้อง กรุณาลองใหม่';
}
if ($selectedQuantities === []) {
    $errors[] = 'กรุณาเลือกเมนูอย่างน้อยหนึ่งรายการ';
}

if ($errors === []) {
    try {
        $orderId = create_user_order(
            $pdo,
            (int) $user['id'],
            (int) $user['member_id'],
            $selectedQuantities
        );
        flash('success', 'ส่งออเดอร์ #' . str_pad((string) $orderId, 6, '0', STR_PAD_LEFT) . ' เรียบร้อยแล้ว');
        redirect(app_url('user/order_detail.php?id=' . $orderId));
    } catch (InvalidArgumentException $exception) {
        $errors[] = $exception->getMessage();
    }
}

$_SESSION['order_errors'] = $errors;
$_SESSION['order_quantities'] = $selectedQuantities;
redirect(app_url('user/menu.php'));
