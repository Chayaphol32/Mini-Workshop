<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pageTitle = 'Admin Dashboard';
$pathPrefix = '../';

$totalMembers = (int) $pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
$openOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'preparing', 'ready')")->fetchColumn();
$completedSales = (float) $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'completed'")->fetchColumn();
$activePoints = (int) $pdo->query("SELECT COALESCE(SUM(points), 0) FROM purchases WHERE status = 'active'")->fetchColumn();

$pendingOrders = $pdo->query(
    "SELECT o.*, m.name AS member_name, m.member_no, u.username
     FROM orders o
     JOIN members m ON o.member_id = m.id
     JOIN users u ON o.user_id = u.id
     WHERE o.status = 'pending'
     ORDER BY o.id ASC
     LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/../includes/header.php';
?>
<section class="page-header page-heading page-heading-premium">
    <div>
        <span class="eyebrow">OPERATIONS OVERVIEW</span>
        <h1>ภาพรวมร้าน</h1>
        <p class="muted">เห็นสิ่งที่ต้องจัดการก่อน แล้วค่อยลงรายละเอียดเมื่อพร้อม</p>
    </div>
    <div class="page-header-actions">
        <a class="button button-primary" href="<?= e(app_url('admin/orders.php?status=pending')) ?>"><span aria-hidden="true">◷</span> เปิดคิวงาน <span class="button-count"><?= $openOrders ?></span></a>
    </div>
</section>

<section class="stats-grid metric-strip" aria-label="สรุปข้อมูลร้าน">
    <div class="stat-card admin-metric-priority">
        <p>ออเดอร์ที่ต้องจัดการ</p>
        <strong><?= number_format($openOrders) ?> <small>รายการ</small></strong>
        <span class="metric-caption <?= $openOrders > 0 ? 'is-alert' : '' ?>"><?= $openOrders > 0 ? 'มีรายการรอการจัดการ' : 'คิวว่างแล้ว' ?></span>
    </div>
    <div class="stat-card">
        <p>ยอดขายออเดอร์สำเร็จ</p>
        <strong><?= e(money($completedSales)) ?> <small>฿</small></strong>
        <span class="metric-caption">สะสมในระบบ</span>
    </div>
    <div class="stat-card">
        <p>สมาชิกทั้งหมด</p>
        <strong><?= number_format($totalMembers) ?> <small>คน</small></strong>
        <span class="metric-caption">แต้มรวม <?= number_format($activePoints) ?> แต้ม</span>
    </div>
</section>

<section class="card hero-card queue-card">
    <div class="section-heading">
        <div>
            <span class="eyebrow">LIVE QUEUE</span>
            <h2>คิวออเดอร์ใหม่</h2>
            <p class="muted">รายการที่รอรับเพื่อเริ่มเตรียมอาหาร</p>
        </div>
        <a class="button button-small button-muted" href="<?= e(app_url('admin/orders.php?status=pending')) ?>">ดูทั้งหมด <?= $openOrders ?> รายการ</a>
    </div>
    <?php if ($pendingOrders === []): ?>
        <div class="empty-state compact-empty">
            <span class="empty-state-icon" aria-hidden="true">✓</span>
            <h2>ไม่มีออเดอร์ค้างในคิว</h2>
            <p>ทุกรายการเรียบร้อยแล้ว พักจิบกาแฟได้เลย</p>
        </div>
    <?php else: ?>
        <div class="table-wrap data-table-desktop">
            <table>
                <caption class="sr-only">คิวออเดอร์ใหม่</caption>
                <thead>
                    <tr>
                        <th scope="col">เลขออเดอร์</th>
                        <th scope="col">ลูกค้า / สมาชิก</th>
                        <th scope="col">เวลาสั่ง</th>
                        <th scope="col">ยอดรวม</th>
                        <th scope="col">สถานะ</th>
                        <th scope="col" style="text-align: right;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pendingOrders as $order): ?>
                    <tr>
                        <td><a href="<?= e(app_url('admin/order_detail.php?id=' . (int) $order['id'])) ?>"><strong>#<?= str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT) ?></strong></a></td>
                        <td><strong><?= e($order['member_name']) ?></strong><span class="table-subtext"><?= e($order['member_no']) ?> · @<?= e($order['username']) ?></span></td>
                        <td><?= e(thai_datetime((string) $order['created_at'])) ?></td>
                        <td><strong><?= e(money($order['total_amount'])) ?> ฿</strong></td>
                        <td><span class="badge badge-status-pending">รอดำเนินการ</span></td>
                        <td style="text-align: right;"><a class="button button-small button-primary" href="<?= e(app_url('admin/order_detail.php?id=' . (int) $order['id'])) ?>">รับออเดอร์</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="data-list-mobile order-data-list" aria-label="คิวออเดอร์ใหม่บนมือถือ">
            <?php foreach ($pendingOrders as $order): ?>
                <article class="data-card order-data-card queue-data-card">
                    <div class="data-card-top">
                        <a href="<?= e(app_url('admin/order_detail.php?id=' . (int) $order['id'])) ?>">
                            <span class="data-card-label">NEXT ORDER</span>
                            <strong>#<?= str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT) ?></strong>
                        </a>
                        <span class="badge badge-status-pending">รอดำเนินการ</span>
                    </div>
                    <div class="data-card-grid">
                        <div>
                            <span class="data-card-label">ลูกค้า</span>
                            <strong><?= e($order['member_name']) ?></strong>
                            <small><?= e($order['member_no']) ?> · <?= e(thai_datetime((string) $order['created_at'])) ?></small>
                        </div>
                        <div class="data-card-total">
                            <span class="data-card-label">ยอดรวม</span>
                            <strong><?= e(money($order['total_amount'])) ?> ฿</strong>
                        </div>
                    </div>
                    <a class="button button-small button-primary data-card-action" href="<?= e(app_url('admin/order_detail.php?id=' . (int) $order['id'])) ?>">เปิดและรับออเดอร์</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="card quick-actions-card">
    <div class="section-heading">
        <div>
            <span class="eyebrow">QUICK ACTIONS</span>
            <h2>ทางลัดสำหรับทีมร้าน</h2>
            <p class="muted">เข้าถึงงานที่ใช้บ่อยโดยไม่ต้องไล่หาในเมนู</p>
        </div>
    </div>
    <div class="quick-actions-grid">
        <a class="quick-action" href="<?= e(app_url('admin/orders.php')) ?>"><span aria-hidden="true">◷</span><strong>จัดการออเดอร์</strong><small>คิวและสถานะการทำอาหาร</small></a>
        <a class="quick-action" href="<?= e(app_url('admin/members.php')) ?>"><span aria-hidden="true">♙</span><strong>ดูแลสมาชิก</strong><small>สมาชิก แต้ม และประวัติ</small></a>
        <a class="quick-action" href="<?= e(app_url('admin/menu.php')) ?>"><span aria-hidden="true">☕</span><strong>จัดการเมนู</strong><small>ราคาและสถานะการขาย</small></a>
        <a class="quick-action" href="<?= e(app_url('admin/purchase.php')) ?>"><span aria-hidden="true">✦</span><strong>บันทึกยอดซื้อ</strong><small>เพิ่มแต้มหน้าร้านแบบ manual</small></a>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
