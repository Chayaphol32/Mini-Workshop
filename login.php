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
        <p class="eyebrow">Specialty Coffee Club</p>
        <h1>ดื่มด่ำรสชาติกาแฟ พร้อมรับสิทธิพิเศษ</h1>
        <p class="muted">เข้าสู่ระบบเพื่อสั่งกาแฟ สะสมแต้ม และเพลิดเพลินกับสิทธิพิเศษสำหรับสมาชิก</p>

        <div class="perks-list">
            <div class="perk-item">
                <div class="perk-icon" aria-hidden="true">☕</div>
                <div class="perk-text">
                    <strong>สั่งง่าย ติดตามสด</strong>
                    <p>เลือกเมนูโปรดพร้อมระบบติดตามสถานะการชงสดแบบเรียลไทม์</p>
                </div>
            </div>
            <div class="perk-item">
                <div class="perk-icon" aria-hidden="true">✨</div>
                <div class="perk-text">
                    <strong>สะสมแต้มทุกแก้ว</strong>
                    <p>ทุก 10 บาทรับทันที 1 แต้ม เพื่อสะสมเลื่อนระดับและรับรางวัล</p>
                </div>
            </div>
            <div class="perk-item">
                <div class="perk-icon" aria-hidden="true">💳</div>
                <div class="perk-text">
                    <strong>บัตรสมาชิกดิจิทัล</strong>
                    <p>เพลิดเพลินกับสิทธิพิเศษ 4 ระดับ: Member, Silver, Gold และ Platinum</p>
                </div>
            </div>
        </div>
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
                <label for="username">ชื่อผู้ใช้ (Username)</label>
                <input id="username" name="username" value="<?= e($username) ?>" autocomplete="username" placeholder="ระบุชื่อผู้ใช้ของคุณ" required autofocus>
            </div>
            <div class="field" style="margin-top: 14px;">
                <label for="password">รหัสผ่าน (Password)</label>
                <input id="password" name="password" type="password" autocomplete="current-password" placeholder="••••••••" required>
            </div>
            <button class="button button-primary button-wide" type="submit" style="margin-top: 24px;">
                เข้าสู่ระบบ
            </button>
        </form>
        <p class="auth-switch">ยังไม่มีบัญชีสมาชิก? <a href="<?= e(app_url('register.php')) ?>"><strong>สมัครสมาชิกเลย</strong></a></p>
    </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
