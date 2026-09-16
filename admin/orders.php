<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pageTitle = 'จัดการออเดอร์';
$pathPrefix = '../';
$term = trim((string) ($_GET['q'] ?? ''));
$allowedStatuses = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];
$status = trim((string) ($_GET['status'] ?? ''));
$statusFilter = in_array($status, $allowedStatuses, true) ? $status : null;
$orders = admin_order_list($pdo, $term, $statusFilter);

require __DIR__ . '/../includes/header.php';
?>
<section class="page-header page-heading">
    <div>
        <p class="eyebrow" style="color: #10b981;">Order Management</p>
        <h1>จัดการออเดอร์ทั้งหมด</h1>
        <p class="muted">ตรวจสอบคำสั่งซื้อ อัปเดตสถานะการทำอาหาร และดูแลลูกค้า</p>
    </div>
    <div class="page-header-actions">
        <a class="button button-muted" href="<?= e(app_url('admin/index.php')) ?>">กลับภาพรวม</a>
    </div>
</section>

<!-- QUICK STATUS PILLS -->
<nav class="filter-pills segmented-control" aria-label="กรองสถานะออเดอร์">
    <a href="<?= e(app_url('admin/orders.php' . ($term !== '' ? '?q=' . urlencode($term) : ''))) ?>"
       class="pill-btn <?= $statusFilter === null ? 'active' : '' ?>"<?= $statusFilter === null ? ' aria-current="page"' : '' ?>>
       ทั้งหมด
    </a>
    <?php foreach ($allowedStatuses as $option): ?>
        <a href="<?= e(app_url('admin/orders.php?status=' . $option . ($term !== '' ? '&q=' . urlencode($term) : ''))) ?>"
           class="pill-btn <?= $statusFilter === $option ? 'active' : '' ?>"<?= $statusFilter === $option ? ' aria-current="page"' : '' ?>>
           <?= e(order_status_label($option)) ?>
        </a>
    <?php endforeach; ?>
</nav>

<section class="card filter-card">
    <form class="filter-grid" method="get" action="<?= e(app_url('admin/orders.php')) ?>">
        <?php if ($statusFilter !== null): ?>
            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
        <?php endif; ?>
        <div class="field">
            <label for="q">ค้นหาคำสั่งซื้อ</label>
            <input id="q" name="q" value="<?= e($term) ?>" placeholder="พิมพ์เลขออเดอร์, ชื่อลูกค้า, หรือเลขสมาชิก...">
        </div>
        <div class="actions filter-actions">
            <button class="button button-primary" type="submit">ค้นหา</button>
            <?php if ($term !== '' || $statusFilter !== null): ?>
                <a class="button button-muted" href="<?= e(app_url('admin/orders.php')) ?>">ล้างตัวกรอง</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="card">
    <div class="section-heading">
        <div>
            <h2>รายการคำสั่งซื้อ</h2>
            <p class="muted">พบทั้งหมด <?= count($orders) ?> รายการ</p>
        </div>
    </div>
    <div class="table-wrap data-table-desktop">
        <?php if ($orders === []): ?>
            <div class="empty">ไม่พบออเดอร์ตามเงื่อนไขนี้</div>
        <?php else: ?>
            <table>
                <caption class="sr-only">รายการคำสั่งซื้อทั้งหมด</caption>
                <thead>
                    <tr>
                        <th scope="col">เลขออเดอร์</th>
                        <th scope="col">ลูกค้า / สมาชิก</th>
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
                            <a href="<?= e(app_url('admin/order_detail.php?id=' . (int) $order['id'])) ?>">
                                <strong>#<?= str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT) ?></strong>
                            </a>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="nav-avatar" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                    <?= e(text_initial((string) $order['member_name'])) ?>
                                </span>
                                <div>
                                    <strong><?= e($order['member_name']) ?></strong>
                                    <small class="table-subtext"><?= e($order['member_no']) ?> · @<?= e($order['username']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= e(thai_datetime((string) $order['created_at'])) ?></td>
                        <td><strong><?= e(money($order['total_amount'])) ?> ฿</strong></td>
                        <td><span class="badge badge-status-<?= e((string) $order['status']) ?>"><?= e(order_status_label((string) $order['status'])) ?></span></td>
                        <td style="text-align: right;">
                            <a class="button button-small button-muted" href="<?= e(app_url('admin/order_detail.php?id=' . (int) $order['id'])) ?>">
                                จัดการออเดอร์ →
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php if ($orders !== []): ?>
        <div class="data-list-mobile order-data-list" aria-label="รายการออเดอร์บนมือถือ">
            <?php foreach ($orders as $order): ?>
                <article class="data-card order-data-card">
                    <div class="data-card-top">
                        <a href="<?= e(app_url('admin/order_detail.php?id=' . (int) $order['id'])) ?>">
                            <span class="data-card-label">ORDER</span>
                            <strong>#<?= str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT) ?></strong>
                        </a>
                        <span class="badge badge-status-<?= e((string) $order['status']) ?>"><?= e(order_status_label((string) $order['status'])) ?></span>
                    </div>
                    <div class="data-card-grid">
                        <div>
                            <span class="data-card-label">ลูกค้า</span>
                            <strong><?= e($order['member_name']) ?></strong>
                            <small><?= e($order['member_no']) ?> · @<?= e($order['username']) ?></small>
                        </div>
                        <div class="data-card-total">
                            <span class="data-card-label">ยอดรวม</span>
                            <strong><?= e(money($order['total_amount'])) ?> ฿</strong>
                            <small><?= e(thai_datetime((string) $order['created_at'])) ?></small>
                        </div>
                    </div>
                    <a class="button button-small button-primary data-card-action" href="<?= e(app_url('admin/order_detail.php?id=' . (int) $order['id'])) ?>">เปิดออเดอร์</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
