<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$orderId = parse_id($_GET['id'] ?? null);
if ($orderId === null) {
    http_response_code(400);
    exit('รหัสออเดอร์ไม่ถูกต้อง');
}

$order = admin_find_order($pdo, $orderId);
if ($order === null) {
    http_response_code(404);
    exit('ไม่พบออเดอร์');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nextStatus = trim((string) ($_POST['next_status'] ?? ''));
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'คำขอไม่ถูกต้อง กรุณาลองใหม่');
    } elseif (!admin_update_order_status($pdo, $orderId, $nextStatus)) {
        flash('error', 'ไม่สามารถเปลี่ยนสถานะจากสถานะปัจจุบันได้');
    } else {
        flash('success', 'อัปเดตสถานะออเดอร์เรียบร้อยแล้ว');
    }
    redirect(app_url('admin/order_detail.php?id=' . $orderId));
}

$items = admin_find_order_items($pdo, $orderId);
$nextStatuses = [
    'pending' => ['preparing', 'cancelled'],
    'preparing' => ['ready', 'cancelled'],
    'ready' => ['completed', 'cancelled'],
    'completed' => [],
    'cancelled' => [],
][$order['status']] ?? [];
$pageTitle = 'จัดการออเดอร์ #' . str_pad((string) $orderId, 6, '0', STR_PAD_LEFT);
$pathPrefix = '../';

require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading detail-header">
    <div>
        <p class="eyebrow">Admin Order Detail</p>
        <h1>ออเดอร์ #<?= str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT) ?></h1>
        <p class="muted">สั่งเมื่อ <?= e(thai_datetime((string) $order['created_at'])) ?></p>
    </div>
    <span class="badge badge-status-<?= e((string) $order['status']) ?>"><?= e(order_status_label((string) $order['status'])) ?></span>
</section>

<section class="grid grid-2">
    <section class="card">
        <p class="eyebrow">ลูกค้า</p>
        <h2><?= e($order['member_name']) ?></h2>
        <div class="detail-list">
            <div><span>เลขสมาชิก</span><strong><?= e($order['member_no']) ?></strong></div>
            <div><span>เบอร์โทร</span><strong><?= e($order['phone']) ?></strong></div>
            <div><span>บัญชี</span><strong><?= e($order['username']) ?></strong></div>
        </div>
    </section>
    <section class="card">
        <p class="eyebrow">การดำเนินการ</p>
        <h2>อัปเดตสถานะ</h2>
        <?php if ($nextStatuses === []): ?>
            <p class="muted">สถานะนี้สิ้นสุดแล้ว ไม่สามารถย้อนกลับได้</p>
        <?php else: ?>
            <form method="post" action="<?= e(app_url('admin/order_detail.php?id=' . $orderId)) ?>" class="status-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label for="next_status">เปลี่ยนเป็น</label>
                <select id="next_status" name="next_status" required>
                    <option value="">เลือกสถานะ</option>
                    <?php foreach ($nextStatuses as $nextStatus): ?>
                        <option value="<?= e($nextStatus) ?>"><?= e(order_status_label($nextStatus)) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button" type="submit">บันทึกสถานะ</button>
            </form>
        <?php endif; ?>
        <?php if ($order['purchase_id'] !== null): ?>
            <p class="field-hint">ออเดอร์นี้ผูกกับรายการแต้มแล้ว #<?= (int) $order['purchase_id'] ?></p>
        <?php elseif ($order['status'] === 'completed'): ?>
            <p class="field-hint">ระบบควรสร้างรายการแต้มให้แล้ว กรุณาตรวจสอบข้อมูล</p>
        <?php endif; ?>
    </section>
</section>

<section class="card">
    <div class="section-heading">
        <div>
            <h2>รายการอาหาร</h2>
            <p class="muted">ข้อมูลราคาเป็น snapshot ตอนลูกค้าสั่ง</p>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>เมนู</th><th>ราคา/ชิ้น</th><th>จำนวน</th><th>รวม</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['item_name']) ?></td>
                    <td><?= e(money($item['unit_price'])) ?> บาท</td>
                    <td><?= (int) $item['quantity'] ?></td>
                    <td><?= e(money($item['line_total'])) ?> บาท</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr><th colspan="3" class="total-label">ยอดรวม</th><th><?= e(money($order['total_amount'])) ?> บาท</th></tr></tfoot>
        </table>
    </div>
</section>

<div class="actions">
    <a class="button button-muted" href="<?= e(app_url('admin/orders.php')) ?>">กลับรายการออเดอร์</a>
    <a class="button button-muted" href="<?= e(app_url('admin/member_detail.php?id=' . (int) $order['member_id'])) ?>">ดูข้อมูลสมาชิก</a>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
