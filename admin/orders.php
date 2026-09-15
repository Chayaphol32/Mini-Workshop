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
<section class="page-heading">
    <div>
        <p class="eyebrow">Admin Orders</p>
        <h1>จัดการออเดอร์ทั้งหมด</h1>
        <p class="muted">ดูคำสั่งซื้อของสมาชิกทุกคนและอัปเดตสถานะการเตรียมอาหาร</p>
    </div>
    <a class="button button-muted" href="<?= e(app_url('admin/index.php')) ?>">กลับ Dashboard</a>
</section>

<section class="card filter-card">
    <form class="filter-grid" method="get" action="<?= e(app_url('admin/orders.php')) ?>">
        <div class="field">
            <label for="q">ค้นหา</label>
            <input id="q" name="q" value="<?= e($term) ?>" placeholder="เลขออเดอร์ ชื่อ หรือเลขสมาชิก">
        </div>
        <div class="field">
            <label for="status">สถานะ</label>
            <select id="status" name="status">
                <option value="">ทุกสถานะ</option>
                <?php foreach ($allowedStatuses as $option): ?>
                    <option value="<?= e($option) ?>" <?= $statusFilter === $option ? 'selected' : '' ?>><?= e(order_status_label($option)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="actions filter-actions">
            <button class="button" type="submit">ค้นหา</button>
            <?php if ($term !== '' || $statusFilter !== null): ?><a class="button button-muted" href="<?= e(app_url('admin/orders.php')) ?>">ล้าง</a><?php endif; ?>
        </div>
    </form>
</section>

<section class="card">
    <div class="section-heading">
        <div>
            <h2>รายการออเดอร์</h2>
            <p class="muted">พบ <?= count($orders) ?> รายการ</p>
        </div>
    </div>
    <div class="table-wrap">
        <?php if ($orders === []): ?>
            <div class="empty">ไม่พบออเดอร์ตามเงื่อนไขนี้</div>
        <?php else: ?>
            <table>
                <thead><tr><th>ออเดอร์</th><th>สมาชิก</th><th>วันเวลา</th><th>ยอดรวม</th><th>สถานะ</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong>#<?= str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                        <td>
                            <strong><?= e($order['member_name']) ?></strong>
                            <small class="table-subtext"><?= e($order['member_no']) ?> · <?= e($order['username']) ?></small>
                        </td>
                        <td><?= e(thai_datetime((string) $order['created_at'])) ?></td>
                        <td><?= e(money($order['total_amount'])) ?> บาท</td>
                        <td><span class="badge badge-status-<?= e((string) $order['status']) ?>"><?= e(order_status_label((string) $order['status'])) ?></span></td>
                        <td><a class="button button-small button-muted" href="<?= e(app_url('admin/order_detail.php?id=' . (int) $order['id'])) ?>">จัดการ</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
