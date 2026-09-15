<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$completedOrderId = null;
$cancelledOrderId = null;
$purchaseId = null;

try {
    if (!login_user($pdo, 'user1', 'user123')) {
        throw new RuntimeException('user1 login failed');
    }
    $user = current_user();
    if ($user === null || $user['member_id'] === null) {
        throw new RuntimeException('user1 session is incomplete');
    }

    $menu = active_menu_items($pdo);
    if (count($menu) < 2) {
        throw new RuntimeException('at least two active menu items are required');
    }
    $requested = [(int) $menu[0]['id'] => 1, (int) $menu[1]['id'] => 1];
    $completedOrderId = create_user_order($pdo, (int) $user['id'], (int) $user['member_id'], $requested);
    logout_user();

    if (!login_user($pdo, 'admin', 'admin123')) {
        throw new RuntimeException('admin login failed');
    }
    $availableMembers = admin_members_without_user($pdo);
    if ($availableMembers === []) {
        throw new RuntimeException('members without an account were not found');
    }
    foreach (['preparing', 'ready', 'completed'] as $nextStatus) {
        if (!admin_update_order_status($pdo, $completedOrderId, $nextStatus)) {
            throw new RuntimeException('could not move order to ' . $nextStatus);
        }
    }
    $completed = admin_find_order($pdo, $completedOrderId);
    if ($completed === null || $completed['status'] !== 'completed' || $completed['purchase_id'] === null) {
        throw new RuntimeException('completed order or linked purchase is incorrect');
    }
    $purchaseId = (int) $completed['purchase_id'];
    $purchaseStmt = $pdo->prepare('SELECT points FROM purchases WHERE id = :id');
    $purchaseStmt->execute(['id' => $purchaseId]);
    $purchase = $purchaseStmt->fetch();
    $expectedPoints = calculate_points((float) $completed['total_amount']);
    if ($purchase === false || (int) $purchase['points'] !== $expectedPoints) {
        throw new RuntimeException('completion points are incorrect');
    }
    if (admin_update_order_status($pdo, $completedOrderId, 'completed')) {
        throw new RuntimeException('completed order was allowed to complete twice');
    }

    logout_user();
    if (!login_user($pdo, 'user1', 'user123')) {
        throw new RuntimeException('user1 relogin failed');
    }
    $user = current_user();
    $cancelledOrderId = create_user_order($pdo, (int) $user['id'], (int) $user['member_id'], [(int) $menu[0]['id'] => 1]);
    logout_user();
    login_user($pdo, 'admin', 'admin123');
    if (!admin_update_order_status($pdo, $cancelledOrderId, 'cancelled')) {
        throw new RuntimeException('pending order cancellation failed');
    }
    $cancelled = admin_find_order($pdo, $cancelledOrderId);
    if ($cancelled === null || $cancelled['status'] !== 'cancelled' || $cancelled['purchase_id'] !== null) {
        throw new RuntimeException('cancelled order incorrectly earned points');
    }

    fwrite(STDOUT, "Admin repository smoke checks passed.\n");
} finally {
    if ($completedOrderId !== null) {
        $unlink = $pdo->prepare('UPDATE orders SET purchase_id = NULL WHERE id = :id');
        $unlink->execute(['id' => $completedOrderId]);
    }
    if ($purchaseId !== null) {
        $deletePurchase = $pdo->prepare('DELETE FROM purchases WHERE id = :id');
        $deletePurchase->execute(['id' => $purchaseId]);
    }
    foreach ([$completedOrderId, $cancelledOrderId] as $testOrderId) {
        if ($testOrderId !== null) {
            $deleteOrder = $pdo->prepare('DELETE FROM orders WHERE id = :id');
            $deleteOrder->execute(['id' => $testOrderId]);
        }
    }
    logout_user();
}
