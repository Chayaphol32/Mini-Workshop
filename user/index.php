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

$pageTitle = 'แต้มของฉัน';
$pathPrefix = '../';
$totalPoints = (int) $member['total_points'];
$level = member_level($totalPoints);
$recentOrders = array_slice(find_user_orders($pdo, (int) $user['id']), 0, 5);

$nextTierName = '';
$pointsNeeded = 0;
$tierProgressPercent = 100;
if ($totalPoints < 1000) {
    $nextTierName = 'Silver';
    $pointsNeeded = 1000 - $totalPoints;
    $tierProgressPercent = min(100, max(0, (int) round(($totalPoints / 1000) * 100)));
} elseif ($totalPoints < 5000) {
    $nextTierName = 'Gold';
    $pointsNeeded = 5000 - $totalPoints;
    $tierProgressPercent = min(100, max(0, (int) round((($totalPoints - 1000) / 4000) * 100)));
} elseif ($totalPoints < 10000) {
    $nextTierName = 'Platinum';
    $pointsNeeded = 10000 - $totalPoints;
    $tierProgressPercent = min(100, max(0, (int) round((($totalPoints - 5000) / 5000) * 100)));
} else {
    $nextTierName = 'ระดับสูงสุด';
    $pointsNeeded = 0;
    $tierProgressPercent = 100;
}

require __DIR__ . '/../includes/header.php';
?>
<section class="page-header page-heading page-heading-premium">
    <div>
        <span class="eyebrow">MY COFFEE CLUB</span>
        <h1>แต้มและสิทธิพิเศษของฉัน</h1>
        <p class="muted">สวัสดี, <?= e((string) $member['name']) ?> — นี่คือความคืบหน้าของคุณ</p>
    </div>
    <div class="page-header-actions">
        <a class="button button-primary" href="<?= e(app_url('user/menu.php')) ?>"><span aria-hidden="true">☕</span> สั่งอีกครั้ง</a>
    </div>
</section>

<div class="member-hub">
    <section class="member-pass hero-card" aria-label="บัตรสมาชิกดิจิทัล">
        <div class="member-pass-top">
            <div class="member-pass-brand"><span aria-hidden="true">☕</span> COFFEE REWARDS PASS</div>
            <span class="badge badge-user"><?= e($level['label']) ?></span>
        </div>
        <div class="member-pass-body">
            <div>
                <span class="member-pass-label">MEMBER</span>
                <strong><?= e((string) $member['name']) ?></strong>
                <small><?= e((string) $member['member_no']) ?></small>
            </div>
            <div class="member-pass-points">
                <span>แต้มสะสม</span>
                <strong><?= number_format($totalPoints) ?></strong>
            </div>
        </div>
    </section>

    <section class="progress-card card" aria-labelledby="progress-title">
        <div class="progress-card-heading">
            <div>
                <span class="eyebrow">NEXT REWARD</span>
                <h2 id="progress-title">เส้นทางสมาชิกของคุณ</h2>
            </div>
            <span class="progress-percent"><?= $tierProgressPercent ?>%</span>
        </div>
        <div class="progress-track" role="progressbar" aria-valuenow="<?= $tierProgressPercent ?>" aria-valuemin="0" aria-valuemax="100" aria-label="ความคืบหน้าสู่ระดับถัดไป">
            <span style="width: <?= $tierProgressPercent ?>%;"></span>
        </div>
        <?php if ($pointsNeeded > 0): ?>
            <p class="progress-message">อีก <strong><?= number_format($pointsNeeded) ?> แต้ม</strong> เพื่อเลื่อนเป็นระดับ <?= e($nextTierName) ?></p>
        <?php else: ?>
            <p class="progress-message">คุณอยู่ในระดับสูงสุดแล้ว — ขอบคุณที่แวะมาหากันเสมอ</p>
        <?php endif; ?>
    </section>
</div>

<section class="stats-grid member-stats" aria-label="สรุปสมาชิก">
    <div class="stat-card"><p>แต้มสะสมปัจจุบัน</p><strong><?= number_format($totalPoints) ?> <small>แต้ม</small></strong></div>
    <div class="stat-card"><p>ระดับสมาชิก</p><strong><span class="badge badge-<?= e($level['key']) ?>"><?= e($level['label']) ?></span></strong></div>
    <div class="stat-card"><p>เลขสมาชิก</p><strong><?= e((string) $member['member_no']) ?></strong></div>
</section>

<section class="card recent-orders-card">
    <div class="section-heading">
        <div>
            <span class="eyebrow">RECENT ORDERS</span>
            <h2>ออเดอร์ล่าสุดของฉัน</h2>
            <p class="muted">ดูรายการล่าสุด หรือกดสั่งเมนูเดิมอีกครั้ง</p>
        </div>
        <a class="button button-small button-muted" href="<?= e(app_url('user/orders.php')) ?>">ดูทั้งหมด</a>
    </div>
    <?php if ($recentOrders === []): ?>
        <div class="empty-state compact-empty">
            <span class="empty-state-icon" aria-hidden="true">◷</span>
            <h2>ยังไม่มีประวัติการสั่งอาหาร</h2>
            <p>เลือกแก้วโปรดของคุณเพื่อเริ่มสะสมแต้ม</p>
            <a class="button button-primary" href="<?= e(app_url('user/menu.php')) ?>">เปิดเมนูอาหาร</a>
        </div>
    <?php else: ?>
        <div class="order-list">
            <?php foreach ($recentOrders as $order): ?>
                <a class="order-row" href="<?= e(app_url('user/order_detail.php?id=' . (int) $order['id'])) ?>">
                    <div>
                        <strong>ออเดอร์ #<?= str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT) ?></strong>
                        <small><?= e(thai_datetime((string) $order['created_at'])) ?></small>
                    </div>
                    <div class="order-row-end">
                        <strong><?= e(money($order['total_amount'])) ?> ฿</strong>
                        <span class="badge badge-status-<?= e((string) $order['status']) ?>"><?= e(order_status_label((string) $order['status'])) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="card reward-rule-card">
    <div class="rule-icon" aria-hidden="true">✦</div>
    <div>
        <span class="eyebrow">HOW IT WORKS</span>
        <h2>สะสมแต้มได้ทุกครั้งที่สั่ง</h2>
        <p class="muted">ทุกยอดใช้จ่าย 10 บาท = 1 แต้ม แต้มจะอัปเดตเมื่อออเดอร์สำเร็จ</p>
    </div>
    <a class="button button-small button-muted" href="<?= e(app_url('user/profile.php')) ?>">จัดการบัญชี</a>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
