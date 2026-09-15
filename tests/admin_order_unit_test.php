<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/order_helpers.php';
require_once __DIR__ . '/../includes/order_status.php';

function admin_order_check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "PASS: {$message}\n");
}

admin_order_check(can_transition_order('pending', 'preparing'), 'pending can move to preparing');
admin_order_check(can_transition_order('preparing', 'ready'), 'preparing can move to ready');
admin_order_check(can_transition_order('ready', 'completed'), 'ready can move to completed');
admin_order_check(can_transition_order('pending', 'cancelled'), 'pending can be cancelled');
admin_order_check(can_transition_order('preparing', 'cancelled'), 'preparing can be cancelled');
admin_order_check(!can_transition_order('completed', 'preparing'), 'completed cannot move backward');
admin_order_check(!can_transition_order('cancelled', 'pending'), 'cancelled cannot be reopened');
admin_order_check(status_can_earn_points('completed'), 'completed earns points');
admin_order_check(!status_can_earn_points('cancelled'), 'cancelled does not earn points');
admin_order_check(order_status_label('ready') === 'พร้อมรับ', 'ready has a Thai label');

fwrite(STDOUT, "Admin order unit checks passed.\n");
