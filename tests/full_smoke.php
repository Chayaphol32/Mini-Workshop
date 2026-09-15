<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$memberId = null;
$userId = null;
$completedOrderId = null;
$cancelledOrderId = null;
$purchaseId = null;
$username = 'qa_' . bin2hex(random_bytes(4));
$memberNo = 'QA' . strtoupper(bin2hex(random_bytes(4)));

try {
    $memberId = create_member($pdo, [
        'member_no' => $memberNo,
        'name' => 'QA Coffee User',
        'phone' => '0800000000',
        'joined_at' => date('Y-m-d'),
    ]);
    $userId = admin_create_user_for_member($pdo, $memberId, $username, 'qa-password-123');

    if (!login_user($pdo, $username, 'qa-password-123')) {
        throw new RuntimeException('QA User login failed');
    }
    $user = current_user();
    if ($user === null || $user['role'] !== 'user' || (int) $user['member_id'] !== $memberId) {
        throw new RuntimeException('QA User scope is incorrect');
    }
    $menu = active_menu_items($pdo);
    if ($menu === []) {
        throw new RuntimeException('no active menu for full smoke');
    }
    $completedOrderId = create_user_order($pdo, $userId, $memberId, [(int) $menu[0]['id'] => 1]);
    if (find_order_for_user($pdo, $completedOrderId, 999999) !== null) {
        throw new RuntimeException('foreign order scope leaked');
    }
    logout_user();

    if (!login_user($pdo, 'admin', 'admin123')) {
        throw new RuntimeException('QA Admin login failed');
    }
    if (admin_find_order($pdo, $completedOrderId) === null) {
        throw new RuntimeException('Admin cannot see QA order');
    }
    foreach (['preparing', 'ready', 'completed'] as $nextStatus) {
        if (!admin_update_order_status($pdo, $completedOrderId, $nextStatus)) {
            throw new RuntimeException('QA order transition failed: ' . $nextStatus);
        }
    }
    $completed = admin_find_order($pdo, $completedOrderId);
    $purchaseId = (int) $completed['purchase_id'];
    $purchase = $pdo->prepare('SELECT points FROM purchases WHERE id = :id');
    $purchase->execute(['id' => $purchaseId]);
    $purchaseRow = $purchase->fetch();
    if ($completed['status'] !== 'completed' || $purchaseRow === false || (int) $purchaseRow['points'] !== calculate_points((float) $completed['total_amount'])) {
        throw new RuntimeException('QA completion did not award the expected points');
    }
    if (admin_update_order_status($pdo, $completedOrderId, 'completed')) {
        throw new RuntimeException('QA completion was not idempotent');
    }

    logout_user();
    login_user($pdo, $username, 'qa-password-123');
    $cancelledOrderId = create_user_order($pdo, $userId, $memberId, [(int) $menu[0]['id'] => 1]);
    logout_user();
    login_user($pdo, 'admin', 'admin123');
    if (!admin_update_order_status($pdo, $cancelledOrderId, 'cancelled')) {
        throw new RuntimeException('QA cancellation failed');
    }
    $cancelled = admin_find_order($pdo, $cancelledOrderId);
    if ($cancelled['status'] !== 'cancelled' || $cancelled['purchase_id'] !== null) {
        throw new RuntimeException('QA cancelled order earned points');
    }

    $seed = $pdo->query("SELECT amount, points FROM purchases WHERE member_id = 1 AND status = 'active' AND amount IN (85.00, 125.00) ORDER BY amount")->fetchAll();
    if (count($seed) < 2 || (int) $seed[0]['points'] !== 8 || (int) $seed[1]['points'] !== 12) {
        throw new RuntimeException('original Workshop point rules regressed');
    }

    fwrite(STDOUT, "Full smoke checks passed.\n");
} finally {
    if ($completedOrderId !== null) {
        $unlink = $pdo->prepare('UPDATE orders SET purchase_id = NULL WHERE id = :id');
        $unlink->execute(['id' => $completedOrderId]);
    }
    if ($purchaseId !== null) {
        $deletePurchase = $pdo->prepare('DELETE FROM purchases WHERE id = :id');
        $deletePurchase->execute(['id' => $purchaseId]);
    }
    foreach ([$completedOrderId, $cancelledOrderId] as $orderId) {
        if ($orderId !== null) {
            $deleteOrder = $pdo->prepare('DELETE FROM orders WHERE id = :id');
            $deleteOrder->execute(['id' => $orderId]);
        }
    }
    if ($userId !== null) {
        $deleteUser = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $deleteUser->execute(['id' => $userId]);
    }
    if ($memberId !== null) {
        $deleteMember = $pdo->prepare('DELETE FROM members WHERE id = :id');
        $deleteMember->execute(['id' => $memberId]);
    }
    logout_user();
}
