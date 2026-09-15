<?php
declare(strict_types=1);

function parse_quantity(mixed $value): ?int
{
    if (!is_string($value) && !is_int($value)) {
        return null;
    }

    $text = trim((string) $value);
    if (!preg_match('/^[1-9][0-9]?$/', $text)) {
        return null;
    }

    $quantity = (int) $text;
    return $quantity >= 1 && $quantity <= 99 ? $quantity : null;
}

function calculate_order_total(array $items): float
{
    $total = 0.0;
    foreach ($items as $item) {
        $total += round((float) $item['unit_price'] * (int) $item['quantity'], 2);
    }

    return round($total, 2);
}

function allowed_user_order_status(string $status): bool
{
    return $status === 'pending';
}

function order_status_label(string $status): string
{
    return [
        'pending' => 'รอดำเนินการ',
        'preparing' => 'กำลังเตรียม',
        'ready' => 'พร้อมรับ',
        'completed' => 'สำเร็จ',
        'cancelled' => 'ยกเลิกแล้ว',
    ][$status] ?? 'ไม่ทราบสถานะ';
}
