<?php
declare(strict_types=1);

function admin_order_list(PDO $pdo, string $term = '', ?string $status = null): array
{
    $term = trim($term);
    $sql =
        'SELECT o.id, o.user_id, o.member_id, o.status, o.total_amount, o.purchase_id,
                o.created_at, o.completed_at, o.cancelled_at,
                u.username, m.member_no, m.name AS member_name, m.phone
         FROM orders o
         INNER JOIN users u ON u.id = o.user_id
         INNER JOIN members m ON m.id = o.member_id
         WHERE (:term = \'\'
            OR CAST(o.id AS CHAR) LIKE :pattern_id
            OR u.username LIKE :pattern_username
            OR m.member_no LIKE :pattern_member_no
            OR m.name LIKE :pattern_member_name)';
    $params = [
        'term' => $term,
        'pattern_id' => '%' . $term . '%',
        'pattern_username' => '%' . $term . '%',
        'pattern_member_no' => '%' . $term . '%',
        'pattern_member_name' => '%' . $term . '%',
    ];
    $allowedStatuses = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];
    if ($status !== null && in_array($status, $allowedStatuses, true)) {
        $sql .= ' AND o.status = :status';
        $params['status'] = $status;
    }
    $sql .= ' ORDER BY o.created_at DESC, o.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function admin_find_order(PDO $pdo, int $orderId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT o.id, o.user_id, o.member_id, o.status, o.total_amount, o.purchase_id,
                o.created_at, o.updated_at, o.completed_at, o.cancelled_at,
                u.username, m.member_no, m.name AS member_name, m.phone
         FROM orders o
         INNER JOIN users u ON u.id = o.user_id
         INNER JOIN members m ON m.id = o.member_id
         WHERE o.id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $orderId]);
    $order = $stmt->fetch();

    return $order === false ? null : $order;
}

function admin_find_order_items(PDO $pdo, int $orderId): array
{
    return find_order_items($pdo, $orderId);
}

function admin_update_order_status(PDO $pdo, int $orderId, string $nextStatus): bool
{
    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare(
            'SELECT id, member_id, status, total_amount, purchase_id
             FROM orders WHERE id = :id FOR UPDATE'
        );
        $lock->execute(['id' => $orderId]);
        $order = $lock->fetch();
        if ($order === false || !can_transition_order((string) $order['status'], $nextStatus)) {
            $pdo->rollBack();
            return false;
        }

        $purchaseId = $order['purchase_id'] === null ? null : (int) $order['purchase_id'];
        if ($nextStatus === 'completed' && $purchaseId === null) {
            $points = calculate_points((float) $order['total_amount']);
            $purchase = $pdo->prepare(
                "INSERT INTO purchases (member_id, amount, points, status)
                 VALUES (:member_id, :amount, :points, 'active')"
            );
            $purchase->execute([
                'member_id' => (int) $order['member_id'],
                'amount' => $order['total_amount'],
                'points' => $points,
            ]);
            $purchaseId = (int) $pdo->lastInsertId();
        }

        if ($nextStatus === 'completed') {
            $update = $pdo->prepare(
                "UPDATE orders
                 SET status = 'completed', purchase_id = :purchase_id, completed_at = NOW()
                 WHERE id = :id AND status = :from_status"
            );
            $update->execute([
                'purchase_id' => $purchaseId,
                'id' => $orderId,
                'from_status' => $order['status'],
            ]);
        } elseif ($nextStatus === 'cancelled') {
            $update = $pdo->prepare(
                "UPDATE orders
                 SET status = 'cancelled', cancelled_at = NOW()
                 WHERE id = :id AND status = :from_status"
            );
            $update->execute(['id' => $orderId, 'from_status' => $order['status']]);
        } else {
            $update = $pdo->prepare(
                'UPDATE orders SET status = :status
                 WHERE id = :id AND status = :from_status'
            );
            $update->execute([
                'status' => $nextStatus,
                'id' => $orderId,
                'from_status' => $order['status'],
            ]);
        }

        if ($update->rowCount() !== 1) {
            $pdo->rollBack();
            return false;
        }
        $pdo->commit();
        return true;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function admin_list_users(PDO $pdo, string $term = ''): array
{
    $stmt = $pdo->prepare(
        'SELECT u.id, u.member_id, u.username, u.role, u.status, u.last_login_at,
                u.created_at, m.member_no, m.name AS member_name, m.phone
         FROM users u
         LEFT JOIN members m ON m.id = u.member_id
         WHERE (:term = \'\' OR u.username LIKE :pattern_username
                OR m.member_no LIKE :pattern_member_no OR m.name LIKE :pattern_name)
         ORDER BY u.role DESC, u.username ASC'
    );
    $stmt->execute([
        'term' => trim($term),
        'pattern_username' => '%' . trim($term) . '%',
        'pattern_member_no' => '%' . trim($term) . '%',
        'pattern_name' => '%' . trim($term) . '%',
    ]);

    return $stmt->fetchAll();
}

function admin_set_user_status(PDO $pdo, int $userId, string $status): bool
{
    if (!in_array($status, ['active', 'disabled'], true)) {
        return false;
    }

    $stmt = $pdo->prepare(
        "UPDATE users SET status = :status
         WHERE id = :id AND role = 'user'"
    );
    $stmt->execute(['status' => $status, 'id' => $userId]);

    return $stmt->rowCount() === 1;
}

function admin_list_menu_items(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT id, name, description, price, status, created_at, updated_at
         FROM menu_items ORDER BY status ASC, name ASC, id ASC'
    );

    return $stmt->fetchAll();
}

function admin_find_menu_item(PDO $pdo, int $menuId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, name, description, price, status, created_at, updated_at
         FROM menu_items WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $menuId]);
    $menu = $stmt->fetch();

    return $menu === false ? null : $menu;
}

function admin_create_menu_item(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO menu_items (name, description, price, status)
         VALUES (:name, :description, :price, :status)'
    );
    $stmt->execute([
        'name' => $data['name'],
        'description' => $data['description'],
        'price' => $data['price'],
        'status' => $data['status'],
    ]);

    return (int) $pdo->lastInsertId();
}

function admin_update_menu_item(PDO $pdo, int $menuId, array $data): bool
{
    $stmt = $pdo->prepare(
        'UPDATE menu_items
         SET name = :name, description = :description, price = :price, status = :status
         WHERE id = :id'
    );
    $stmt->execute([
        'name' => $data['name'],
        'description' => $data['description'],
        'price' => $data['price'],
        'status' => $data['status'],
        'id' => $menuId,
    ]);

    return true;
}

function admin_find_user_for_member(PDO $pdo, int $memberId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, member_id, username, role, status, last_login_at, created_at
         FROM users WHERE member_id = :member_id LIMIT 1'
    );
    $stmt->execute(['member_id' => $memberId]);
    $user = $stmt->fetch();

    return $user === false ? null : $user;
}

function admin_members_without_user(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT m.id, m.member_no, m.name, m.phone
         FROM members m
         LEFT JOIN users u ON u.member_id = m.id
         WHERE u.id IS NULL
         ORDER BY m.member_no ASC'
    );

    return $stmt->fetchAll();
}

function admin_create_user_for_member(PDO $pdo, int $memberId, string $username, string $password): int
{
    $stmt = $pdo->prepare(
        "INSERT INTO users (member_id, username, password_hash, role, status)
         VALUES (:member_id, :username, :password_hash, 'user', 'active')"
    );
    $stmt->execute([
        'member_id' => $memberId,
        'username' => normalize_username($username),
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    return (int) $pdo->lastInsertId();
}
