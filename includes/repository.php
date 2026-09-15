<?php
declare(strict_types=1);

function search_members(PDO $pdo, string $term = ''): array
{
    $sql = <<<'SQL'
SELECT m.id, m.member_no, m.name, m.phone, m.joined_at,
       COALESCE(SUM(CASE WHEN p.status = 'active' THEN p.points ELSE 0 END), 0) AS total_points
FROM members m
LEFT JOIN purchases p ON p.member_id = m.id
WHERE (:term = '' OR m.member_no LIKE :pattern_no OR m.name LIKE :pattern_name)
GROUP BY m.id, m.member_no, m.name, m.phone, m.joined_at
ORDER BY m.id DESC
SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'term' => $term,
        'pattern_no' => '%' . $term . '%',
        'pattern_name' => '%' . $term . '%',
    ]);

    return $stmt->fetchAll();
}

function find_member(PDO $pdo, int $memberId): ?array
{
    $stmt = $pdo->prepare(
        "SELECT m.id, m.member_no, m.name, m.phone, m.joined_at,
                COALESCE(SUM(CASE WHEN p.status = 'active' THEN p.points ELSE 0 END), 0) AS total_points
         FROM members m
         LEFT JOIN purchases p ON p.member_id = m.id
         WHERE m.id = :id
         GROUP BY m.id, m.member_no, m.name, m.phone, m.joined_at"
    );
    $stmt->execute(['id' => $memberId]);
    $member = $stmt->fetch();

    return $member === false ? null : $member;
}

function find_member_by_no(PDO $pdo, string $memberNo): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, member_no, name, phone, joined_at FROM members WHERE member_no = :member_no'
    );
    $stmt->execute(['member_no' => $memberNo]);
    $member = $stmt->fetch();

    return $member === false ? null : $member;
}

function create_member(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO members (member_no, name, phone, joined_at) VALUES (:member_no, :name, :phone, :joined_at)'
    );
    $stmt->execute([
        'member_no' => $data['member_no'],
        'name' => $data['name'],
        'phone' => $data['phone'],
        'joined_at' => $data['joined_at'],
    ]);

    return (int) $pdo->lastInsertId();
}

function update_member(PDO $pdo, int $memberId, array $data): bool
{
    $stmt = $pdo->prepare('UPDATE members SET name = :name, phone = :phone WHERE id = :id');
    $stmt->execute([
        'name' => $data['name'],
        'phone' => $data['phone'],
        'id' => $memberId,
    ]);

    return true;
}

function find_purchases(PDO $pdo, int $memberId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, amount, points, status, purchased_at, cancelled_at
         FROM purchases WHERE member_id = :member_id ORDER BY purchased_at DESC, id DESC'
    );
    $stmt->execute(['member_id' => $memberId]);

    return $stmt->fetchAll();
}

function create_purchase(PDO $pdo, int $memberId, float $amount, int $points): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO purchases (member_id, amount, points) VALUES (:member_id, :amount, :points)'
    );
    $stmt->execute([
        'member_id' => $memberId,
        'amount' => $amount,
        'points' => $points,
    ]);

    return (int) $pdo->lastInsertId();
}

function cancel_purchase(PDO $pdo, int $memberId, int $purchaseId): bool
{
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            "UPDATE purchases
             SET status = 'cancelled', cancelled_at = NOW()
             WHERE id = :id AND member_id = :member_id AND status = 'active'"
        );
        $stmt->execute(['id' => $purchaseId, 'member_id' => $memberId]);

        if ($stmt->rowCount() !== 1) {
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
