<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pageTitle = 'Admin Dashboard';
$user = current_user();
$pathPrefix = '../';
$totalMembers = (int) $pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
$openOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'preparing', 'ready')")->fetchColumn();
$completedSales = (float) $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'completed'")->fetchColumn();
$activePoints = (int) $pdo->query("SELECT COALESCE(SUM(points), 0) FROM purchases WHERE status = 'active'")->fetchColumn();

require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Admin Portal</p>
        <h1>แดชบอร์ดผู้ดูแลระบบ</h1>
        <p class="muted">จัดการสมาชิก เมนู ออเดอร์ และระบบแต้มจากที่เดียว</p>
    </div>
    <span class="badge badge-admin">Admin</span>
</section>

<section class="card welcome-card">
    <p class="eyebrow">เข้าสู่ระบบสำเร็จ</p>
    <h2>สวัสดี <?= e((string) ($user['username'] ?? 'Admin')) ?></h2>
    <p class="muted">หน้านี้จะรวมข้อมูลทั้งหมดของร้านสำหรับผู้ดูแลระบบ</p>
</section>

<section class="stats-grid" aria-label="เมนูจัดการระบบ">
    <div class="stat-card">
        <p>สมาชิกทั้งหมด</p>
        <strong><?= $totalMembers ?></strong>
    </div>
    <div class="stat-card">
        <p>ออเดอร์ที่ต้องจัดการ</p>
        <strong><?= $openOrders ?></strong>
    </div>
    <div class="stat-card">
        <p>ยอดขายออเดอร์สำเร็จ</p>
        <strong><?= e(money($completedSales)) ?> บาท</strong>
    </div>
</section>

<section class="card">
    <div class="section-heading">
        <div>
            <h2>ทางลัดสำหรับ Admin</h2>
            <p class="muted">แต้มที่กำลังใช้งานทั้งหมด <?= $activePoints ?> แต้ม</p>
        </div>
    </div>
    <div class="actions">
        <a class="button" href="<?= e(app_url('admin/orders.php')) ?>">จัดการออเดอร์</a>
        <a class="button button-muted" href="<?= e(app_url('admin/members.php')) ?>">ดูสมาชิกทั้งหมด</a>
        <a class="button button-muted" href="<?= e(app_url('admin/menu.php')) ?>">จัดการเมนูอาหาร</a>
        <a class="button button-muted" href="<?= e(app_url('admin/users.php')) ?>">จัดการบัญชี</a>
    </div>
</section>

<section class="stats-grid" aria-label="ระบบเดิม">
    <a class="stat-card stat-link" href="<?= e(app_url('admin/purchase.php')) ?>">
        <p>ระบบ Workshop เดิม</p>
        <strong>บันทึกยอดซื้อ</strong>
    </a>
    <a class="stat-card stat-link" href="<?= e(app_url('admin/members.php')) ?>">
        <p>สมาชิกและแต้ม</p>
        <strong>เปิดรายงาน</strong>
    </a>
    <a class="stat-card stat-link" href="<?= e(app_url('admin/orders.php?status=pending')) ?>">
        <p>คิวล่าสุด</p>
        <strong>ดูออเดอร์รอดำเนินการ</strong>
    </a>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
