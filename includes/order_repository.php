<?php
declare(strict_types=1);

function active_menu_items(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT id, name, description, price
         FROM menu_items
         WHERE status = 'active'
         ORDER BY name ASC, id ASC"
    );

    return $stmt->fetchAll();
}

function find_order_for_user(PDO $pdo, int $orderId, int $userId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT o.id, o.user_id, o.member_id, o.status, o.total_amount, o.purchase_id,
                o.completed_at, o.cancelled_at, o.created_at, o.updated_at,
                m.member_no, m.name AS member_name, m.phone
         FROM orders o
         INNER JOIN members m ON m.id = o.member_id
         WHERE o.id = :order_id AND o.user_id = :user_id
         LIMIT 1'
    );
    $stmt->execute(['order_id' => $orderId, 'user_id' => $userId]);
    $order = $stmt->fetch();

    return $order === false ? null : $order;
}

function find_order_items(PDO $pdo, int $orderId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, menu_item_id, item_name, unit_price, quantity, line_total
         FROM order_items
         WHERE order_id = :order_id
         ORDER BY id ASC'
    );
    $stmt->execute(['order_id' => $orderId]);

    return $stmt->fetchAll();
}

function find_user_orders(PDO $pdo, int $userId, ?string $status = null): array
{
    $allowedStatuses = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];
    $sql =
        'SELECT o.id, o.member_id, o.status, o.total_amount, o.purchase_id,
                o.completed_at, o.cancelled_at, o.created_at,
                m.member_no, m.name AS member_name
         FROM orders o
         INNER JOIN members m ON m.id = o.member_id
         WHERE o.user_id = :user_id';
    $params = ['user_id' => $userId];

    if ($status !== null && in_array($status, $allowedStatuses, true)) {
        $sql .= ' AND o.status = :status';
        $params['status'] = $status;
    }
    $sql .= ' ORDER BY o.created_at DESC, o.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function create_user_order(PDO $pdo, int $userId, int $memberId, array $requestedQuantities): int
{
    $menuIds = [];
    foreach (array_keys($requestedQuantities) as $menuId) {
        $menuIds[] = (int) $menuId;
    }
    $menuIds = array_values(array_unique(array_filter($menuIds, static fn (int $id): bool => $id > 0)));
    if ($menuIds === []) {
        throw new InvalidArgumentException('ต้องเลือกเมนูอย่างน้อยหนึ่งรายการ');
    }

    $pdo->beginTransaction();
    try {
        $placeholders = implode(',', array_fill(0, count($menuIds), '?'));
        $menuStmt = $pdo->prepare(
            "SELECT id, name, price
             FROM menu_items
             WHERE status = 'active' AND id IN ({$placeholders})
             FOR UPDATE"
        );
        $menuStmt->execute($menuIds);
        $menuById = [];
        foreach ($menuStmt->fetchAll() as $menu) {
            $menuById[(int) $menu['id']] = $menu;
        }

        if (count($menuById) !== count($menuIds)) {
            throw new InvalidArgumentException('มีเมนูบางรายการปิดการขายแล้ว');
        }

        $items = [];
        foreach ($requestedQuantities as $menuId => $rawQuantity) {
            $menuId = (int) $menuId;
            if (!isset($menuById[$menuId])) {
                throw new InvalidArgumentException('เมนูที่เลือกไม่ถูกต้อง');
            }
            $quantity = parse_quantity($rawQuantity);
            if ($quantity === null) {
                throw new InvalidArgumentException('จำนวนเมนูต้องเป็นจำนวนเต็มตั้งแต่ 1 ถึง 99');
            }

            $unitPrice = (float) $menuById[$menuId]['price'];
            $items[] = [
                'menu_item_id' => $menuId,
                'item_name' => (string) $menuById[$menuId]['name'],
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => round($unitPrice * $quantity, 2),
            ];
        }

        if ($items === []) {
            throw new InvalidArgumentException('ต้องเลือกเมนูอย่างน้อยหนึ่งรายการ');
        }
        $total = calculate_order_total($items);

        $orderStmt = $pdo->prepare(
            "INSERT INTO orders (user_id, member_id, status, total_amount)
             VALUES (:user_id, :member_id, 'pending', :total_amount)"
        );
        $orderStmt->execute([
            'user_id' => $userId,
            'member_id' => $memberId,
            'total_amount' => $total,
        ]);
        $orderId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items
                (order_id, menu_item_id, item_name, unit_price, quantity, line_total)
             VALUES (:order_id, :menu_item_id, :item_name, :unit_price, :quantity, :line_total)'
        );
        foreach ($items as $item) {
            $itemStmt->execute([
                'order_id' => $orderId,
                'menu_item_id' => $item['menu_item_id'],
                'item_name' => $item['item_name'],
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
                'line_total' => $item['line_total'],
            ]);
        }

        $pdo->commit();
        return $orderId;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function cancel_user_order(PDO $pdo, int $orderId, int $userId): bool
{
    $stmt = $pdo->prepare(
        "UPDATE orders
         SET status = 'cancelled', cancelled_at = NOW()
         WHERE id = :order_id AND user_id = :user_id AND status = 'pending'"
    );
    $stmt->execute(['order_id' => $orderId, 'user_id' => $userId]);

    return $stmt->rowCount() === 1;
}

function update_member_profile(PDO $pdo, int $memberId, string $name, string $phone): bool
{
    $stmt = $pdo->prepare('UPDATE members SET name = :name, phone = :phone WHERE id = :id');
    $stmt->execute(['name' => $name, 'phone' => $phone, 'id' => $memberId]);

    return true;
}
