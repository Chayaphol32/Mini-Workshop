<?php
$pageTitle = $pageTitle ?? 'Coffee Member Rewards';
$flashMessage = pull_flash();
$authUser = function_exists('current_user') ? current_user() : null;
$authRole = $authUser === null ? 'guest' : (string) ($authUser['role'] ?? 'user');
$roleLabel = match ($authRole) {
    'admin' => 'Admin workspace',
    'user' => 'Member workspace',
    default => 'Welcome to the club',
};
$identityName = $authUser === null
    ? ''
    : (string) ($authUser['name'] ?? $authUser['username'] ?? 'สมาชิก');
$accountName = $authUser === null
    ? ''
    : (string) ($authUser['username'] ?? $identityName);
$avatarChar = $identityName !== '' ? text_initial($identityName) : 'C';

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$isPageActive = static function (string ...$slugs) use ($currentUri): bool {
    foreach ($slugs as $slug) {
        if (str_contains($currentUri, $slug)) {
            return true;
        }
    }

    return false;
};

$navItems = $authRole === 'admin'
    ? [
        ['label' => 'ภาพรวม', 'short' => 'ภาพรวม', 'description' => 'ดูสถานะร้าน', 'icon' => '⌂', 'href' => app_url('admin/index.php'), 'active' => $isPageActive('admin/index.php')],
        ['label' => 'คิวออเดอร์', 'short' => 'ออเดอร์', 'description' => 'จัดการรายการสั่งซื้อ', 'icon' => '◷', 'href' => app_url('admin/orders.php'), 'active' => $isPageActive('admin/orders.php', 'admin/order_')],
        ['label' => 'สมาชิก', 'short' => 'สมาชิก', 'description' => 'ดูแลสมาชิกและแต้ม', 'icon' => '♙', 'href' => app_url('admin/members.php'), 'active' => $isPageActive('admin/members.php', 'admin/member_')],
        ['label' => 'เมนูอาหาร', 'short' => 'เมนู', 'description' => 'จัดการ catalog', 'icon' => '☕', 'href' => app_url('admin/menu.php'), 'active' => $isPageActive('admin/menu.php', 'admin/menu_')],
        ['label' => 'บัญชีผู้ใช้', 'short' => 'บัญชี', 'description' => 'สิทธิ์และการเข้าสู่ระบบ', 'icon' => '◉', 'href' => app_url('admin/users.php'), 'active' => $isPageActive('admin/users.php')],
    ]
    : [
        ['label' => 'สั่งอาหาร', 'short' => 'เมนู', 'description' => 'เลือกแก้วโปรดของคุณ', 'icon' => '☕', 'href' => app_url('user/menu.php'), 'active' => $isPageActive('user/menu.php')],
        ['label' => 'ออเดอร์ของฉัน', 'short' => 'ออเดอร์', 'description' => 'ติดตามและดูประวัติ', 'icon' => '◷', 'href' => app_url('user/orders.php'), 'active' => $isPageActive('user/orders.php', 'user/order_')],
        ['label' => 'แต้มของฉัน', 'short' => 'แต้ม', 'description' => 'ระดับและสิทธิพิเศษ', 'icon' => '✦', 'href' => app_url('user/index.php'), 'active' => $isPageActive('user/index.php')],
        ['label' => 'บัญชี', 'short' => 'บัญชี', 'description' => 'ข้อมูลสมาชิก', 'icon' => '♙', 'href' => app_url('user/profile.php'), 'active' => $isPageActive('user/profile.php')],
    ];

$homeHref = $authUser === null ? app_url('index.php') : app_url(role_home($authRole));
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Coffee Member Rewards</title>
    <link rel="stylesheet" href="<?= e(asset_url('assets/style.css')) ?>">
</head>
<body class="app-body role-<?= e($authRole) ?>">
<a class="skip-link" href="#main-content">ข้ามไปยังเนื้อหา</a>
<div class="app-shell <?= $authUser === null ? 'app-shell-public' : 'app-shell-authenticated' ?>">
    <header class="site-header">
        <div class="container topbar">
            <a class="brand" href="<?= e($homeHref) ?>" aria-label="Coffee Member Rewards">
                <span class="brand-mark" aria-hidden="true">☕</span>
                <span class="brand-lockup"><strong>COFFEE</strong><small>MEMBER REWARDS</small></span>
            </a>

            <?php if ($authUser !== null): ?>
                <div class="topbar-context" aria-label="พื้นที่การทำงาน">
                    <span class="topbar-context-dot" aria-hidden="true"></span>
                    <span><?= e($roleLabel) ?></span>
                </div>
                <div class="topbar-actions">
                    <span class="topbar-page-title"><?= e($pageTitle) ?></span>
                    <details class="account-menu">
                        <summary class="account-trigger" aria-label="เปิดเมนูบัญชีของ <?= e($identityName) ?>">
                            <span class="nav-avatar" aria-hidden="true"><?= e($avatarChar) ?></span>
                            <span class="account-trigger-copy">
                                <strong><?= e($identityName) ?></strong>
                                <small>@<?= e($accountName) ?></small>
                            </span>
                            <span class="account-chevron" aria-hidden="true">⌄</span>
                        </summary>
                        <div class="account-popover">
                            <div class="account-popover-head">
                                <span class="eyebrow">Signed in as</span>
                                <strong><?= e($identityName) ?></strong>
                            </div>
                            <a href="<?= e($authRole === 'admin' ? app_url('admin/users.php') : app_url('user/profile.php')) ?>">จัดการบัญชี</a>
                            <form class="logout-form" method="post" action="<?= e(app_url('logout.php')) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <button class="account-logout" type="submit">ออกจากระบบ</button>
                            </form>
                        </div>
                    </details>
                </div>
            <?php else: ?>
                <div class="guest-header-actions">
                    <a class="guest-login-link" href="<?= e(app_url('login.php')) ?>">เข้าสู่ระบบ</a>
                    <a class="button button-small button-primary" href="<?= e(app_url('register.php')) ?>">สมัครสมาชิก</a>
                    <details class="nav-menu">
                        <summary class="nav-toggle"><span>เมนู</span><span aria-hidden="true">☰</span></summary>
                        <nav class="nav-links" aria-label="เมนูผู้เยี่ยมชม">
                            <a href="<?= e(app_url('login.php')) ?>">เข้าสู่ระบบ</a>
                            <a href="<?= e(app_url('register.php')) ?>">สมัครสมาชิก</a>
                        </nav>
                    </details>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($authUser !== null): ?>
        <aside class="app-sidebar" aria-label="เมนูหลัก">
            <div class="sidebar-inner">
                <div class="sidebar-intro">
                    <span class="sidebar-kicker"><?= $authRole === 'admin' ? 'OPERATIONS' : 'YOUR COFFEE CLUB' ?></span>
                    <p><?= $authRole === 'admin' ? 'ทุกอย่างที่ร้านต้องจัดการ' : 'ทุกอย่างสำหรับแก้วโปรดของคุณ' ?></p>
                </div>
                <nav class="sidebar-nav" aria-label="พื้นที่หลักของระบบ">
                    <?php foreach ($navItems as $item): ?>
                        <a class="nav-item<?= $item['active'] ? ' active' : '' ?>" href="<?= e($item['href']) ?>"<?= $item['active'] ? ' aria-current="page"' : '' ?>>
                            <span class="nav-item-icon" aria-hidden="true"><?= e($item['icon']) ?></span>
                            <span class="nav-item-copy">
                                <strong><?= e($item['label']) ?></strong>
                                <small><?= e($item['description']) ?></small>
                            </span>
                            <span class="nav-item-arrow" aria-hidden="true">↗</span>
                        </a>
                    <?php endforeach; ?>
                </nav>
                <div class="sidebar-footer">
                    <div class="sidebar-note">
                        <span class="sidebar-note-icon" aria-hidden="true">✦</span>
                        <div>
                            <strong><?= $authRole === 'admin' ? 'ร้านพร้อมเสิร์ฟ' : 'สะสมแต้มทุกแก้ว' ?></strong>
                            <small><?= $authRole === 'admin' ? 'ดูแลทุกออเดอร์ได้จากที่นี่' : 'ทุก 10 บาท = 1 แต้ม' ?></small>
                        </div>
                    </div>
                    <form class="sidebar-logout-form" method="post" action="<?= e(app_url('logout.php')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <button class="sidebar-logout" type="submit"><span aria-hidden="true">↪</span> ออกจากระบบ</button>
                    </form>
                </div>
            </div>
        </aside>

        <nav class="mobile-nav" aria-label="เมนูหลักบนมือถือ">
            <?php foreach ($navItems as $item): ?>
                <a class="mobile-nav-item<?= $item['active'] ? ' active' : '' ?>" href="<?= e($item['href']) ?>"<?= $item['active'] ? ' aria-current="page"' : '' ?>>
                    <span class="mobile-nav-icon" aria-hidden="true"><?= e($item['icon']) ?></span>
                    <span><?= e($item['short']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <main id="main-content" class="container page-shell">
    <?php if ($flashMessage !== null): ?>
        <div class="flash flash-<?= e($flashMessage['type']) ?>" role="status">
            <?= e($flashMessage['message']) ?>
        </div>
    <?php endif; ?>
