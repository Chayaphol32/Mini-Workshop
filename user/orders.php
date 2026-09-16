<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('user');

$user = current_user();
$allowedStatuses = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];
$status = trim((string) ($_GET['status'] ?? ''));
$statusFilter = in_array($status, $allowedStatuses, true) ? $status : null;
$orders = find_user_orders($pdo, (int) $user['id'], $statusFilter);
$pageTitle = 'ออเดอร์ของฉัน';
$pathPrefix = '../';

require __DIR__ . '/../includes/header.php';
?>
<section class="page-header page-heading">
    <div>
        <span class="eyebrow">YOUR ORDERS</span>
        <h1>ออเดอร์ของฉัน</h1>
        <p class="muted">ติดตามรายการที่กำลังเตรียม และดูประวัติการสั่งซื้อทั้งหมด</p>
    </div>
    <div class="page-header-actions">
        <a class="button button-primary" href="<?= e(app_url('user/menu.php')) ?>"><span aria-hidden="true">+</span> สั่งอาหารใหม่</a>
    </div>
</section>

<nav class="filter-pills segmented-control" aria-label="กรองสถานะออเดอร์">
    <a href="<?= e(app_url('user/orders.php')) ?>" class="pill-btn <?= $statusFilter === null ? 'active' : '' ?>"<?= $statusFilter === null ? ' aria-current="page"' : '' ?>>ทั้งหมด</a>
    <?php foreach ($allowedStatuses as $option): ?>
        <a href="<?= e(app_url('user/orders.php?status=' . $option)) ?>" class="pill-btn <?= $statusFilter === $option ? 'active' : '' ?>"<?= $statusFilter === $option ? ' aria-current="page"' : '' ?>>
            <?= e(order_status_label($option)) ?>
        </a>
    <?php endforeach; ?>
</nav>

<section class="card order-history-card">
    <div class="section-heading">
        <div>
            <span class="eyebrow">ORDER HISTORY</span>
            <h2><?= $statusFilter === null ? 'รายการทั้งหมด' : e(order_status_label($statusFilter)) ?></h2>
            <p class="muted">พบ <?= number_format(count($orders)) ?> รายการ</p>
        </div>
    </div>
    <?php if ($orders === []): ?>
        <div class="empty-state">
            <span class="empty-state-icon" aria-hidden="true">◷</span>
            <h2>ยังไม่มีออเดอร์ตามเงื่อนไขนี้</h2>
            <p>เริ่มต้นช่วงเวลาพิเศษของคุณด้วยเครื่องดื่มแก้วโปรด</p>
            <a class="button button-primary" href="<?= e(app_url('user/menu.php')) ?>">เปิดเมนูอาหาร</a>
        </div>
    <?php else: ?>
        <div class="table-wrap data-table-desktop">
            <table>
                <caption class="sr-only">รายการออเดอร์ของฉัน</caption>
                <thead>
                    <tr>
                        <th scope="col">เลขออเดอร์</th>
                        <th scope="col">วันเวลาที่สั่ง</th>
                        <th scope="col">ยอดรวม</th>
                        <th scope="col">สถานะ</th>
                        <th scope="col" style="text-align: right;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <a href="<?= e(app_url('user/order_detail.php?id=' . (int) $order['id'])) ?>">
                                <strong>#<?= str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT) ?></strong>
                            </a>
                        </td>
                        <td><?= e(thai_datetime((string) $order['created_at'])) ?></td>
                        <td><strong><?= e(money($order['total_amount'])) ?> ฿</strong></td>
                        <td><span class="badge badge-status-<?= e((string) $order['status']) ?>"><?= e(order_status_label((string) $order['status'])) ?></span></td>
                        <td style="text-align: right;">
                            <a class="button button-small button-muted" href="<?= e(app_url('user/order_detail.php?id=' . (int) $order['id'])) ?>">ดูรายละเอียด</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="data-list-mobile order-data-list" aria-label="รายการออเดอร์ของฉันบนมือถือ">
            <?php foreach ($orders as $order): ?>
                <article class="data-card order-data-card">
                    <div class="data-card-top">
                        <a href="<?= e(app_url('user/order_detail.php?id=' . (int) $order['id'])) ?>">
                            <span class="data-card-label">ORDER</span>
                            <strong>#<?= str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT) ?></strong>
                        </a>
                        <span class="badge badge-status-<?= e((string) $order['status']) ?>"><?= e(order_status_label((string) $order['status'])) ?></span>
                    </div>
                    <div class="data-card-grid">
                        <div>
                            <span class="data-card-label">สั่งเมื่อ</span>
                            <strong><?= e(thai_datetime((string) $order['created_at'])) ?></strong>
                            <small>ติดตามสถานะได้จากรายละเอียด</small>
                        </div>
                        <div class="data-card-total">
                            <span class="data-card-label">ยอดรวม</span>
                            <strong><?= e(money($order['total_amount'])) ?> ฿</strong>
                        </div>
                    </div>
                    <a class="button button-small button-primary data-card-action" href="<?= e(app_url('user/order_detail.php?id=' . (int) $order['id'])) ?>">ดูรายละเอียดออเดอร์</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
