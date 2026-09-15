<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$pdo->beginTransaction();
try {
    $admin = $pdo->prepare(
        "INSERT INTO users (member_id, username, password_hash, role)
         VALUES (NULL, :username, :password_hash, 'admin')
         ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'admin', status = 'active'"
    );
    $admin->execute([
        'username' => 'admin',
        'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
    ]);

    $member = find_member_by_no($pdo, 'M0001');
    if ($member === null) {
        throw new RuntimeException('ต้องมีสมาชิก M0001 ก่อน seed user1');
    }

    $user = $pdo->prepare(
        "INSERT INTO users (member_id, username, password_hash, role)
         VALUES (:member_id, :username, :password_hash, 'user')
         ON DUPLICATE KEY UPDATE member_id = VALUES(member_id), password_hash = VALUES(password_hash), role = 'user', status = 'active'"
    );
    $user->execute([
        'member_id' => (int) $member['id'],
        'username' => 'user1',
        'password_hash' => password_hash('user123', PASSWORD_DEFAULT),
    ]);

    $pdo->commit();
    fwrite(STDOUT, "Demo accounts seeded.\n");
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}
