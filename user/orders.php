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
<section class="page-heading">
    <div>
        <p class="eyebrow">My Orders</p>
        <h1>ออเดอร์ของฉัน</h1>
        <p class="muted">รายการนี้แสดงเฉพาะออเดอร์ของบัญชีที่กำลังเข้าสู่ระบบ</p>
    </div>
    <a class="button" href="<?= e(app_url('user/menu.php')) ?>">+ สั่งอาหาร</a>
</section>

<section class="card filter-card">
    <form class="actions" method="get" action="<?= e(app_url('user/orders.php')) ?>">
        <label for="status">กรองสถานะ</label>
        <select id="status" name="status">
            <option value="">ทุกสถานะ</option>
            <?php foreach ($allowedStatuses as $option): ?>
                <option value="<?= e($option) ?>" <?= $statusFilter === $option ? 'selected' : '' ?>><?= e(order_status_label($option)) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button" type="submit">กรอง</button>
        <?php if ($statusFilter !== null): ?><a class="button button-muted" href="<?= e(app_url('user/orders.php')) ?>">ล้าง</a><?php endif; ?>
    </form>
</section>

<section class="card">
    <div class="table-wrap">
        <?php if ($orders === []): ?>
            <div class="empty">ยังไม่มีออเดอร์ตามเงื่อนไขนี้</div>
        <?php else: ?>
            <table>
                <thead><tr><th>ออเดอร์</th><th>วันเวลา</th><th>ยอดรวม</th><th>สถานะ</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong>#<?= str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                        <td><?= e(thai_datetime((string) $order['created_at'])) ?></td>
                        <td><?= e(money($order['total_amount'])) ?> บาท</td>
                        <td><span class="badge badge-status-<?= e((string) $order['status']) ?>"><?= e(order_status_label((string) $order['status'])) ?></span></td>
                        <td><a class="button button-small button-muted" href="<?= e(app_url('user/order_detail.php?id=' . (int) $order['id'])) ?>">ดูรายละเอียด</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
