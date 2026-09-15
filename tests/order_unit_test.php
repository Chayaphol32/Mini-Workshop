<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/order_helpers.php';

function order_check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "PASS: {$message}\n");
}

order_check(parse_quantity('1') === 1, 'quantity one is valid');
order_check(parse_quantity('99') === 99, 'quantity 99 is valid');
order_check(parse_quantity('0') === null, 'quantity zero is rejected');
order_check(parse_quantity('1.5') === null, 'decimal quantity is rejected');
order_check(parse_quantity('100') === null, 'quantity over 99 is rejected');

$items = [
    ['unit_price' => '65.00', 'quantity' => 2],
    ['unit_price' => '45.50', 'quantity' => 1],
];
order_check(calculate_order_total($items) === 175.50, 'order total is calculated from price snapshots');
order_check(allowed_user_order_status('pending'), 'pending can be cancelled by User');
order_check(!allowed_user_order_status('preparing'), 'preparing cannot be cancelled by User');
order_check(order_status_label('completed') === 'สำเร็จ', 'completed has a Thai label');

fwrite(STDOUT, "Order unit checks passed.\n");
