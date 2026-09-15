<?php
declare(strict_types=1);

if (!defined('COFFEE_SKIP_DATABASE')) {
    define('COFFEE_SKIP_DATABASE', true);
}
require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user();
if ($user !== null) {
    redirect(app_url(role_home((string) $user['role'])));
}

$pageTitle = 'เข้าสู่ระบบ';
$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/config/database.php';

    $username = normalize_username((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'คำขอไม่ถูกต้อง กรุณาลองใหม่';
    } elseif ($username === '' || $password === '') {
        $errors[] = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } elseif (!login_user($pdo, $username, $password)) {
        $errors[] = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    } else {
        $loggedInUser = current_user();
        redirect(app_url(role_home((string) $loggedInUser['role'])));
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="auth-shell">
    <div class="auth-intro">
        <p class="eyebrow">Coffee Ordering & Rewards</p>
        <h1>ยินดีต้อนรับกลับมา</h1>
        <p class="muted">เข้าสู่ระบบเพื่อสั่งอาหาร ดูแต้มสะสม หรือจัดการร้าน</p>
    </div>
    <section class="card form-card auth-card">
        <h2>เข้าสู่ระบบ</h2>
        <?php if ($errors !== []): ?>
            <ul class="error-list" role="alert">
                <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form method="post" action="<?= e(app_url('login.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="field">
                <label for="username">ชื่อผู้ใช้</label>
                <input id="username" name="username" value="<?= e($username) ?>" autocomplete="username" required autofocus>
            </div>
            <div class="field">
                <label for="password">รหัสผ่าน</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button class="button button-wide" type="submit">เข้าสู่ระบบ</button>
        </form>
        <p class="auth-switch">ยังไม่มีบัญชี? <a href="<?= e(app_url('register.php')) ?>">สมัครสมาชิก</a></p>
    </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
