<?php
declare(strict_types=1);

function current_user(): ?array
{
    $user = $_SESSION['auth_user'] ?? null;
    return is_array($user) ? $user : null;
}

function login_user(PDO $pdo, string $username, string $password): bool
{
    $stmt = $pdo->prepare(
        'SELECT u.id, u.member_id, u.username, u.password_hash, u.role, u.status,
                m.member_no, m.name, m.phone
         FROM users u
         LEFT JOIN members m ON m.id = u.member_id
         WHERE u.username = :username
         LIMIT 1'
    );
    $stmt->execute(['username' => normalize_username($username)]);
    $user = $stmt->fetch();

    if ($user === false || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    session_regenerate_id(true);
    $_SESSION['auth_user'] = [
        'id' => (int) $user['id'],
        'member_id' => $user['member_id'] === null ? null : (int) $user['member_id'],
        'username' => $user['username'],
        'role' => $user['role'],
        'member_no' => $user['member_no'],
        'name' => $user['name'],
        'phone' => $user['phone'],
    ];

    $update = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
    $update->execute(['id' => (int) $user['id']]);

    return true;
}

function require_login(): void
{
    if (current_user() === null) {
        flash('error', 'กรุณาเข้าสู่ระบบก่อนใช้งาน');
        redirect(app_url('login.php'));
    }
}

function require_role(string $role): void
{
    require_login();
    $user = current_user();

    if ($user === null || $user['role'] !== $role) {
        http_response_code(403);
        exit('ไม่มีสิทธิ์เข้าถึงหน้านี้');
    }
}

function logout_user(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
}
