<?php
$pageTitle = $pageTitle ?? 'Coffee Member Rewards';
$flashMessage = pull_flash();
$authUser = function_exists('current_user') ? current_user() : null;
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Coffee Member Rewards</title>
    <link rel="stylesheet" href="<?= e(asset_url('assets/style.css')) ?>">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="<?= e(app_url('index.php')) ?>"><span class="brand-mark" aria-hidden="true">☕</span> Coffee Rewards</a>
        <nav class="nav-links" aria-label="เมนูหลัก">
            <?php if ($authUser === null): ?>
                <a href="<?= e(app_url('login.php')) ?>">เข้าสู่ระบบ</a>
                <a class="button button-small" href="<?= e(app_url('register.php')) ?>">สมัครสมาชิก</a>
            <?php elseif ($authUser['role'] === 'admin'): ?>
                <a href="<?= e(app_url('admin/index.php')) ?>">Dashboard</a>
                <a href="<?= e(app_url('admin/members.php')) ?>">สมาชิก</a>
                <a href="<?= e(app_url('admin/orders.php')) ?>">ออเดอร์</a>
                <a href="<?= e(app_url('admin/menu.php')) ?>">เมนู</a>
                <a href="<?= e(app_url('admin/users.php')) ?>">บัญชี</a>
                <form class="logout-form" method="post" action="<?= e(app_url('logout.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button class="button button-small button-light" type="submit">ออกจากระบบ</button>
                </form>
            <?php else: ?>
                <a href="<?= e(app_url('user/index.php')) ?>">หน้าหลัก</a>
                <a href="<?= e(app_url('user/menu.php')) ?>">สั่งอาหาร</a>
                <a href="<?= e(app_url('user/orders.php')) ?>">ออเดอร์ของฉัน</a>
                <a href="<?= e(app_url('user/profile.php')) ?>">ข้อมูลส่วนตัว</a>
                <form class="logout-form" method="post" action="<?= e(app_url('logout.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button class="button button-small button-light" type="submit">ออกจากระบบ</button>
                </form>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container page-shell">
<?php if ($flashMessage !== null): ?>
    <div class="flash flash-<?= e($flashMessage['type']) ?>" role="status">
        <?= e($flashMessage['message']) ?>
    </div>
<?php endif; ?>
