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
        flash('success', 'อัปเดตสถานะออเดอร์เป็น ' . order_status_label($nextStatus) . ' เรียบร้อยแล้ว');
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

$status = (string) $order['status'];
$steps = [
    ['key' => 'pending', 'icon' => '📝', 'label' => 'รอดำเนินการ'],
    ['key' => 'preparing', 'icon' => '☕', 'label' => 'กำลังเตรียม'],
    ['key' => 'ready', 'icon' => '✨', 'label' => 'พร้อมเสิร์ฟ'],
    ['key' => 'completed', 'icon' => '🎉', 'label' => 'สำเร็จแล้ว'],
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

require __DIR__ . '/../includes/header.php';
?>
<section class="page-header page-heading detail-header">
    <div>
        <p class="eyebrow" style="color: #10b981;">Admin Order Management</p>
        <h1>ออเดอร์ #<?= str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT) ?></h1>
        <p class="muted">สั่งซื้อเมื่อ <?= e(thai_datetime((string) $order['created_at'])) ?></p>
    </div>
    <span class="badge badge-status-<?= e($status) ?>"><?= e(order_status_label($status)) ?></span>
</section>

<!-- ORDER STATUS STEPPER -->
<section class="order-timeline-card">
    <div class="section-heading">
        <h2>สถานะขั้นตอนการทำงาน</h2>
        <span class="muted">ลำดับ: รอดำเนินการ ➔ กำลังเตรียม ➔ พร้อมเสิร์ฟ ➔ สำเร็จ</span>
    </div>
    <?php if ($status === 'cancelled'): ?>
        <div class="timeline-cancelled-banner">
            <span style="font-size: 1.5rem;">⚠️</span>
            <div>
                <strong>ออเดอร์นี้ถูกยกเลิกแล้ว</strong>
                <p style="margin: 0; font-size: 0.88rem; font-weight: normal;">รายการนี้สิ้นสุดและไม่สามารถแก้ไขสถานะได้อีก</p>
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
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<div class="grid grid-2">
    <!-- CUSTOMER CARD -->
    <section class="card">
        <p class="eyebrow">Customer Information</p>
        <h2><?= e($order['member_name']) ?></h2>
        <div style="display: grid; gap: 12px; margin-top: 16px;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-light); padding-bottom: 8px;">
                <span class="muted">เลขที่สมาชิก</span>
                <strong><?= e($order['member_no']) ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-light); padding-bottom: 8px;">
                <span class="muted">เบอร์โทรติดต่อ</span>
                <strong><a href="tel:<?= e($order['phone']) ?>"><?= e($order['phone']) ?></a></strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span class="muted">บัญชีผู้ใช้</span>
                <strong>@<?= e($order['username']) ?></strong>
            </div>
        </div>
        <div style="margin-top: 18px;">
            <a class="button button-small button-muted" href="<?= e(app_url('admin/member_detail.php?id=' . (int) $order['member_id'])) ?>">
                ดูประวัติสมาชิกคนนี้ →
            </a>
        </div>
    </section>

    <!-- STATUS ACTIONS -->
    <section class="card">
        <p class="eyebrow">Action Required</p>
        <h2>อัปเดตสถานะออเดอร์</h2>
        <?php if ($nextStatuses === []): ?>
            <p class="muted" style="margin-top: 12px;">✅ ออเดอร์นี้สิ้นสุดแล้ว (<?= e(order_status_label($status)) ?>) ไม่สามารถย้อนกลับสถานะได้</p>
        <?php else: ?>
            <p class="muted" style="margin-bottom: 16px;">กดปุ่มเพื่อเปลี่ยนสถานะไปยังขั้นตอนถัดไป:</p>
            <form method="post" action="<?= e(app_url('admin/order_detail.php?id=' . $orderId)) ?>" class="actions">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <?php foreach ($nextStatuses as $nextStatus): ?>
                    <?php
                    $isDanger = $nextStatus === 'cancelled';
                    $btnClass = $isDanger ? 'button-danger' : 'button-primary';
                    $icon = match($nextStatus) {
                        'preparing' => '☕ ',
                        'ready' => '✨ ',
                        'completed' => '🎉 ',
                        'cancelled' => '❌ ',
                        default => '',
                    };
                    ?>
                    <button class="button <?= $btnClass ?>"
                            type="submit"
                            name="next_status"
                            value="<?= e($nextStatus) ?>"
                            <?= $isDanger ? "onclick=\"return confirm('ยืนยันยกเลิกออเดอร์นี้หรือไม่?');\"" : "" ?>>
                        <?= $icon ?>เปลี่ยนเป็น: <?= e(order_status_label($nextStatus)) ?>
                    </button>
                <?php endforeach; ?>
            </form>
        <?php endif; ?>

        <?php if ($order['purchase_id'] !== null): ?>
            <div style="margin-top: 16px; padding: 10px 14px; background: var(--success-bg); border-radius: var(--radius-sm); color: var(--success); font-size: 0.86rem; font-weight: 700;">
                ✓ ออเดอร์นี้ผูกกับรายการแต้มสะสมแล้ว (Purchase #<?= (int) $order['purchase_id'] ?>)
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- ITEMS TABLE -->
<section class="card">
    <div class="section-heading">
        <div>
            <h2>รายการอาหารที่สั่ง</h2>
            <p class="muted">ราคา snapshot ณ เวลาที่สมาชิกส่งคำสั่งซื้อ</p>
        </div>
    </div>
    <div class="table-wrap data-table-desktop">
        <table>
            <caption class="sr-only">รายการอาหารในออเดอร์</caption>
            <thead>
                <tr>
                    <th scope="col">เมนู</th>
                    <th scope="col">ราคา/ชิ้น</th>
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
                    <th colspan="3" class="total-label">ยอดรวมสุทธิ</th>
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
            <span>ยอดรวมสุทธิ</span>
            <strong><?= e(money($order['total_amount'])) ?> ฿</strong>
        </div>
    </div>
</section>

<div class="actions">
    <a class="button button-muted" href="<?= e(app_url('admin/orders.php')) ?>">← กลับหน้ารายการออเดอร์</a>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
