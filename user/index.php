<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('user');

$user = current_user();
$memberId = (int) ($user['member_id'] ?? 0);
$member = $memberId > 0 ? find_member($pdo, $memberId) : null;
if ($member === null) {
    http_response_code(500);
    exit('ไม่พบข้อมูลสมาชิกที่ผูกกับบัญชี');
}

$pageTitle = 'หน้าหลักของฉัน';
$pathPrefix = '../';
$totalPoints = (int) $member['total_points'];
$level = member_level($totalPoints);
$recentOrders = array_slice(find_user_orders($pdo, (int) $user['id']), 0, 5);

require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">My Coffee Rewards</p>
        <h1>สวัสดี <?= e((string) $member['name']) ?></h1>
        <p class="member-meta"><span>เลขสมาชิก <?= e((string) $member['member_no']) ?></span><span>โทร <?= e((string) $member['phone']) ?></span></p>
    </div>
    <span class="badge badge-user">User</span>
</section>

<section class="stats-grid" aria-label="สรุปข้อมูลของฉัน">
    <div class="stat-card">
        <p>แต้มสะสมปัจจุบัน</p>
        <strong><?= $totalPoints ?> แต้ม</strong>
    </div>
    <div class="stat-card">
        <p>ระดับสมาชิก</p>
        <strong><span class="badge badge-<?= e($level['key']) ?>"><?= e($level['label']) ?></span></strong>
    </div>
    <a class="stat-card stat-link" href="<?= e(app_url('user/menu.php')) ?>">
        <p>พร้อมสั่งกาแฟแล้วหรือยัง?</p>
        <strong>เปิดดูเมนู →</strong>
    </a>
</section>

<section class="card welcome-card">
    <h2>พื้นที่ส่วนตัวของคุณ</h2>
    <p class="muted">คุณจะเห็นเฉพาะข้อมูลสมาชิก แต้มสะสม และออเดอร์ของบัญชีนี้เท่านั้น</p>
    <div class="actions">
        <a class="button" href="<?= e(app_url('user/menu.php')) ?>">สั่งอาหาร</a>
        <a class="button button-muted" href="<?= e(app_url('user/orders.php')) ?>">ดูออเดอร์ของฉัน</a>
        <a class="button button-muted" href="<?= e(app_url('user/profile.php')) ?>">แก้ไขข้อมูลส่วนตัว</a>
    </div>
</section>

<section class="card">
    <div class="section-heading">
        <div>
            <h2>ออเดอร์ล่าสุดของฉัน</h2>
            <p class="muted">ดูสถานะการสั่งอาหารจากบัญชีนี้</p>
        </div>
        <a class="button button-small button-muted" href="<?= e(app_url('user/orders.php')) ?>">ดูทั้งหมด</a>
    </div>
    <?php if ($recentOrders === []): ?>
        <div class="empty">ยังไม่มีประวัติการสั่งอาหาร</div>
    <?php else: ?>
        <div class="order-list">
            <?php foreach ($recentOrders as $order): ?>
                <a class="order-row" href="<?= e(app_url('user/order_detail.php?id=' . (int) $order['id'])) ?>">
                    <span>
                        <strong>ออเดอร์ #<?= str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT) ?></strong>
                        <small><?= e(thai_datetime((string) $order['created_at'])) ?></small>
                    </span>
                    <span class="order-row-end">
                        <strong><?= e(money($order['total_amount'])) ?> บาท</strong>
                        <span class="badge badge-status-<?= e((string) $order['status']) ?>"><?= e(order_status_label((string) $order['status'])) ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
