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
$pageTitle = 'รายละเอียดออเดอร์ #' . str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT);
$pathPrefix = '../';

$status = (string) $order['status'];
$steps = [
    ['key' => 'pending', 'icon' => '📝', 'label' => 'รอดำเนินการ', 'desc' => 'รับคำสั่งซื้อแล้ว'],
    ['key' => 'preparing', 'icon' => '☕', 'label' => 'กำลังเตรียม', 'desc' => 'บาริสต้ากำลังชง'],
    ['key' => 'ready', 'icon' => '✨', 'label' => 'พร้อมเสิร์ฟ', 'desc' => 'รับเครื่องดื่มได้'],
    ['key' => 'completed', 'icon' => '🎉', 'label' => 'สำเร็จแล้ว', 'desc' => 'รับแต้มสะสมแล้ว'],
];
$statusOrder = ['pending' => 0, 'preparing' => 1, 'ready' => 2, 'completed' => 3];
$currentStepIndex = $statusOrder[$status] ?? -1;
$progressWidth = match ($status) {
    'pending' => '0%',
    'preparing' => '33%',
    'ready' => '66%',
    'completed' => '76%',
    default => '0%',
};

$earnedPoints = (int) floor((float) $order['total_amount'] / 10);

require __DIR__ . '/../includes/header.php';
?>
<section class="page-header page-heading detail-header">
    <div>
        <p class="eyebrow">Live Order Tracking</p>
        <h1>ออเดอร์ #<?= str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT) ?></h1>
        <p class="muted">สั่งซื้อเมื่อ <?= e(thai_datetime((string) $order['created_at'])) ?></p>
    </div>
    <span class="badge badge-status-<?= e($status) ?>"><?= e(order_status_label($status)) ?></span>
</section>

<!-- ORDER STATUS TIMELINE STEPPER -->
<section class="order-timeline-card">
    <h2>ติดตามสถานะออเดอร์</h2>
    <?php if ($status === 'cancelled'): ?>
        <div class="timeline-cancelled-banner">
            <span style="font-size: 1.5rem;">⚠️</span>
            <div>
                <strong>ออเดอร์นี้ถูกยกเลิกแล้ว</strong>
                <p style="margin: 0; font-size: 0.88rem; font-weight: normal;">รายการนี้จะไม่ถูกคิดค่าบริการและไม่ได้รับแต้มสะสม</p>
            </div>
        </div>
    <?php else: ?>
        <div class="order-timeline">
            <div class="order-timeline-progress" style="width: <?= $progressWidth ?>;"></div>
            <?php foreach ($steps as $idx => $step): ?>
                <?php
                $stepClass = '';
                if ($idx < $currentStepIndex) {
                    $stepClass = 'completed';
                } elseif ($idx === $currentStepIndex) {
                    $stepClass = 'active';
                }
                ?>
                <div class="timeline-step <?= $stepClass ?>">
                    <div class="step-node" aria-hidden="true">
                        <?php if ($idx < $currentStepIndex): ?>✓<?php else: ?><?= $step['icon'] ?><?php endif; ?>
                    </div>
                    <div class="step-label"><?= $step['label'] ?></div>
                    <div class="step-subtext"><?= $step['desc'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- RECEIPT CARD -->
<section class="receipt-card">
    <div class="section-heading">
        <div>
            <h2>รายการอาหารในออเดอร์</h2>
            <p class="muted">บันทึกราคาและรายการ ณ เวลาสั่งซื้อ</p>
        </div>
        <span class="badge badge-gold">แต้มสะสม: +<?= $earnedPoints ?> แต้ม</span>
    </div>
    <div class="table-wrap data-table-desktop">
        <table>
            <caption class="sr-only">รายการอาหารในออเดอร์</caption>
            <thead>
                <tr>
                    <th scope="col">เมนู</th>
                    <th scope="col">ราคาต่อชิ้น</th>
                    <th scope="col">จำนวน</th>
                    <th scope="col" style="text-align: right;">รวม</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><strong><?= e($item['item_name']) ?></strong></td>
                    <td><?= e(money($item['unit_price'])) ?> ฿</td>
                    <td><?= (int) $item['quantity'] ?></td>
                    <td style="text-align: right;"><strong><?= e(money($item['line_total'])) ?> ฿</strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="total-label">ยอดสุทธิ</th>
                    <th style="text-align: right; color: var(--amber-500);"><?= e(money($order['total_amount'])) ?> ฿</th>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="receipt-list data-list-mobile" aria-label="รายการอาหารในออเดอร์บนมือถือ">
        <?php foreach ($items as $item): ?>
            <div class="receipt-item">
                <div>
                    <strong><?= e($item['item_name']) ?></strong>
                    <small><?= (int) $item['quantity'] ?> × <?= e(money($item['unit_price'])) ?> ฿</small>
                </div>
                <strong><?= e(money($item['line_total'])) ?> ฿</strong>
            </div>
        <?php endforeach; ?>
        <div class="receipt-total">
            <span>ยอดสุทธิ</span>
            <strong><?= e(money($order['total_amount'])) ?> ฿</strong>
        </div>
    </div>
</section>

<!-- ORDER ACTIONS -->
<section class="card">
    <div class="section-heading">
        <div>
            <h2>การจัดการออเดอร์</h2>
            <p class="muted">หากออเดอร์ยังไม่เริ่มเตรียมอาหาร สามารถกดยกเลิกได้</p>
        </div>
    </div>
    <div class="actions">
        <?php if ($order['status'] === 'pending'): ?>
            <form method="post" action="<?= e(app_url('user/order_detail.php?id=' . $orderId)) ?>" onsubmit="return confirm('ยืนยันยกเลิกออเดอร์นี้หรือไม่?');">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button class="button button-danger" type="submit">ยกเลิกออเดอร์นี้</button>
            </form>
        <?php elseif ($order['status'] === 'cancelled'): ?>
            <span class="badge badge-status-cancelled">ออเดอร์ถูกยกเลิกแล้ว</span>
        <?php else: ?>
            <span class="muted">🔒 กำลังเตรียมอาหาร ไม่สามารถยกเลิกได้</span>
        <?php endif; ?>
        <a class="button button-muted" href="<?= e(app_url('user/orders.php')) ?>">← กลับไปออเดอร์ของฉัน</a>
        <a class="button button-primary" href="<?= e(app_url('user/menu.php')) ?>">☕ สั่งเพิ่ม</a>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
