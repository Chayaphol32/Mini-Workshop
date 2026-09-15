<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('user');

$user = current_user();
$orderId = parse_id($_GET['id'] ?? null);
if ($orderId === null) {
    http_response_code(400);
    exit('รหัสออเดอร์ไม่ถูกต้อง');
}

$order = find_order_for_user($pdo, $orderId, (int) $user['id']);
if ($order === null) {
    http_response_code(404);
    exit('ไม่พบออเดอร์ของบัญชีนี้');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'คำขอไม่ถูกต้อง กรุณาลองใหม่');
    } elseif (!cancel_user_order($pdo, $orderId, (int) $user['id'])) {
        flash('error', 'ออเดอร์นี้ไม่อยู่ในสถานะที่ยกเลิกได้');
    } else {
        flash('success', 'ยกเลิกออเดอร์เรียบร้อยแล้ว');
    }
    redirect(app_url('user/order_detail.php?id=' . $orderId));
}

$items = find_order_items($pdo, $orderId);
$pageTitle = 'รายละเอียดออเดอร์';
$pathPrefix = '../';

require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading detail-header">
    <div>
        <p class="eyebrow">Order detail</p>
        <h1>ออเดอร์ #<?= str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT) ?></h1>
        <p class="muted">สั่งเมื่อ <?= e(thai_datetime((string) $order['created_at'])) ?></p>
    </div>
    <span class="badge badge-status-<?= e((string) $order['status']) ?>"><?= e(order_status_label((string) $order['status'])) ?></span>
</section>

<section class="card">
    <div class="section-heading">
        <div>
            <h2>รายการอาหาร</h2>
            <p class="muted">รายการและราคาเป็นข้อมูลที่บันทึกไว้ตอนสั่ง</p>
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

<section class="card order-actions-card">
    <div>
        <h2>สถานะออเดอร์</h2>
        <p class="muted">หากออเดอร์ยังไม่เริ่มเตรียมอาหาร คุณสามารถยกเลิกได้</p>
    </div>
    <div class="actions">
        <?php if ($order['status'] === 'pending'): ?>
            <form method="post" action="<?= e(app_url('user/order_detail.php?id=' . $orderId)) ?>" onsubmit="return confirm('ยืนยันยกเลิกออเดอร์นี้หรือไม่?');">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button class="button button-danger" type="submit">ยกเลิกออเดอร์</button>
            </form>
        <?php elseif ($order['status'] === 'cancelled'): ?>
            <span class="muted">ออเดอร์นี้ถูกยกเลิกแล้ว</span>
        <?php else: ?>
            <span class="muted">ไม่สามารถยกเลิกหลังเริ่มเตรียมอาหาร</span>
        <?php endif; ?>
        <a class="button button-muted" href="<?= e(app_url('user/orders.php')) ?>">กลับออเดอร์ของฉัน</a>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
