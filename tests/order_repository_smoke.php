<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$orderId = null;
try {
    if (!login_user($pdo, 'user1', 'user123')) {
        throw new RuntimeException('user1 login failed');
    }
    $user = current_user();
    if ($user === null || $user['role'] !== 'user' || $user['member_id'] === null) {
        throw new RuntimeException('user session is incomplete');
    }

    $menu = active_menu_items($pdo);
    if ($menu === []) {
        throw new RuntimeException('active menu is empty');
    }
    $menuId = (int) $menu[0]['id'];
    $orderId = create_user_order($pdo, (int) $user['id'], (int) $user['member_id'], [$menuId => 2]);
    $order = find_order_for_user($pdo, $orderId, (int) $user['id']);
    if ($order === null || $order['status'] !== 'pending' || (int) $order['user_id'] !== (int) $user['id']) {
        throw new RuntimeException('created order ownership/status is incorrect');
    }
    if (find_order_for_user($pdo, $orderId, 999999) !== null) {
        throw new RuntimeException('foreign user can see the order');
    }
    if (find_order_items($pdo, $orderId) === []) {
        throw new RuntimeException('order items were not created');
    }
    if (!cancel_user_order($pdo, $orderId, (int) $user['id'])) {
        throw new RuntimeException('first cancellation failed');
    }
    if (cancel_user_order($pdo, $orderId, (int) $user['id'])) {
        throw new RuntimeException('second cancellation succeeded');
    }

    fwrite(STDOUT, "Order repository smoke checks passed.\n");
} finally {
    if ($orderId !== null) {
        $cleanup = $pdo->prepare('DELETE FROM orders WHERE id = :id');
        $cleanup->execute(['id' => $orderId]);
    }
    logout_user();
}
