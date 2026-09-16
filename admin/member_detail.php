<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$memberId = parse_id($_GET['id'] ?? null);
if ($memberId === null) {
    http_response_code(400);
    exit('รหัสสมาชิกไม่ถูกต้อง');
}
$member = find_member($pdo, $memberId);
if ($member === null) {
    http_response_code(404);
    exit('ไม่พบสมาชิก');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'คำขอไม่ถูกต้อง กรุณาลองใหม่');
        redirect(app_url('admin/member_detail.php?id=' . $memberId));
    }
    $purchaseId = parse_id($_POST['purchase_id'] ?? null);
    if ($purchaseId === null) {
        flash('error', 'รหัสรายการซื้อไม่ถูกต้อง');
    } elseif (!cancel_purchase($pdo, $memberId, $purchaseId)) {
        flash('error', 'รายการนี้อาจถูกยกเลิกแล้วหรือไม่ใช่ของสมาชิกคนนี้');
    } else {
        flash('success', 'ยกเลิกรายการซื้อเรียบร้อยแล้ว ระบบคำนวณแต้มใหม่ให้แล้ว');
    }
    redirect(app_url('admin/member_detail.php?id=' . $memberId));
}

$pageTitle = 'รายละเอียดสมาชิก: ' . $member['name'];
$pathPrefix = '../';
$purchases = find_purchases($pdo, $memberId);
$account = admin_find_user_for_member($pdo, $memberId);
$totalPoints = (int) $member['total_points'];
$level = member_level($totalPoints);

require __DIR__ . '/../includes/header.php';
?>
<section class="page-header page-heading detail-header">
    <div>
        <p class="eyebrow" style="color: #10b981;">Member Profile</p>
        <h1><?= e($member['name']) ?></h1>
        <div class="member-meta">
            <span>🏷️ รหัสสมาชิก: <strong><?= e($member['member_no']) ?></strong></span>
            <span>📱 โทร: <a href="tel:<?= e($member['phone']) ?>"><?= e($member['phone']) ?></a></span>
            <span>📅 สมัครเมื่อ: <?= e(thai_date((string) $member['joined_at'])) ?></span>
        </div>
    </div>
    <div class="actions">
        <a class="button button-primary" href="<?= e(app_url('admin/purchase.php?member_id=' . $memberId)) ?>">+ บันทึกยอดซื้อ</a>
        <a class="button button-muted" href="<?= e(app_url('admin/member_edit.php?id=' . $memberId)) ?>">แก้ไขข้อมูล</a>
    </div>
</section>

<!-- STATS -->
<section class="stats-grid" aria-label="สรุปแต้มสมาชิก">
    <div class="stat-card">
        <p>แต้มสะสมปัจจุบัน</p>
        <strong style="color: var(--amber-500);"><?= number_format($totalPoints) ?> แต้ม</strong>
    </div>
    <div class="stat-card">
        <p>ระดับสมาชิก</p>
        <strong><span class="badge badge-<?= e($level['key']) ?>"><?= e($level['label']) ?></span></strong>
    </div>
    <div class="stat-card">
        <p>ประวัติการซื้อสะสม</p>
        <strong><?= number_format(count($purchases)) ?> รายการ</strong>
    </div>
</section>

<div class="grid grid-2">
    <!-- ACCOUNT INFO -->
    <section class="card">
        <div class="section-heading">
            <div>
                <h2>บัญชีผู้ใช้ระบบ (Login Account)</h2>
                <p class="muted">ข้อมูลสำหรับเข้าสู่ระบบของสมาชิกคนนี้</p>
            </div>
        </div>
        <?php if ($account === null): ?>
            <p class="muted" style="margin-bottom: 16px;">สมาชิกคนนี้ยังไม่มีบัญชีเข้าสู่ระบบ (Guest Member)</p>
            <a class="button button-small button-primary" href="<?= e(app_url('admin/users.php?member_id=' . $memberId)) ?>">
                + สร้างบัญชีผู้ใช้ให้สมาชิก
            </a>
        <?php else: ?>
            <div style="display: grid; gap: 12px;">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-light); padding-bottom: 8px;">
                    <span class="muted">Username</span>
                    <strong>@<?= e($account['username']) ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span class="muted">สถานะการใช้งาน</span>
                    <span>
                        <?php if ($account['status'] === 'active'): ?>
                            <span class="badge badge-active">เปิดใช้งาน</span>
                        <?php else: ?>
                            <span class="badge badge-status-cancelled">ระงับใช้งาน</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- QUICK MANUAL ACTION -->
    <section class="card">
        <div class="section-heading">
            <div>
                <h2>สะสมแต้มหน้าร้าน</h2>
                <p class="muted">บันทึกยอดซื้อเงินสดหรือยอดนอกระบบ (10 ฿ = 1 แต้ม)</p>
            </div>
        </div>
        <p class="muted" style="margin-bottom: 16px;">สามารถระบุยอดเงินเพื่อเพิ่มแต้มให้สมาชิกได้ทันที</p>
        <a class="button button-primary" href="<?= e(app_url('admin/purchase.php?member_id=' . $memberId)) ?>">
            + บันทึกยอดซื้อให้สมาชิกคนนี้
        </a>
    </section>
</div>

<!-- PURCHASES TABLE -->
<section class="card">
    <div class="section-heading">
        <div>
            <h2>ประวัติการซื้อและแต้มสะสม</h2>
            <p class="muted">รายการที่ยกเลิกจะถูกเก็บไว้เป็นประวัติแต่ไม่นำมาคำนวณแต้ม</p>
        </div>
    </div>
    <div class="table-wrap data-table-desktop">
        <?php if ($purchases === []): ?>
            <div class="empty">สมาชิกคนนี้ยังไม่มีประวัติการซื้อ</div>
        <?php else: ?>
            <table>
                <caption class="sr-only">ประวัติการซื้อและแต้มสะสม</caption>
                <thead>
                    <tr>
                        <th scope="col">วันเวลา</th>
                        <th scope="col">ยอดซื้อ</th>
                        <th scope="col">แต้มที่ได้รับ</th>
                        <th scope="col">สถานะรายการ</th>
                        <th scope="col" style="text-align: right;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($purchases as $purchase): ?>
                    <tr>
                        <td><?= e(thai_datetime((string) $purchase['purchased_at'])) ?></td>
                        <td><strong><?= e(money($purchase['amount'])) ?> ฿</strong></td>
                        <td><span class="badge badge-gold">+<?= (int) $purchase['points'] ?> แต้ม</span></td>
                        <td>
                            <?php if ($purchase['status'] === 'active'): ?>
                                <span class="badge badge-active">ใช้งาน</span>
                            <?php else: ?>
                                <span class="badge badge-status-cancelled">ยกเลิกแล้ว</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <?php if ($purchase['status'] === 'active'): ?>
                                <form method="post" action="<?= e(app_url('admin/member_detail.php?id=' . $memberId)) ?>" onsubmit="return confirm('ยืนยันยกเลิกรายการนี้หรือไม่? แต้มจะถูกหักออก');" style="display: inline-block;">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="purchase_id" value="<?= (int) $purchase['id'] ?>">
                                    <button class="button button-small button-danger" type="submit">ยกเลิกรายการ</button>
                                </form>
                            <?php else: ?>
                                <span class="muted">ยกเลิกแล้ว</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php if ($purchases !== []): ?>
        <div class="data-list-mobile purchase-data-list" aria-label="ประวัติการซื้อของสมาชิกบนมือถือ">
            <?php foreach ($purchases as $purchase): ?>
                <article class="data-card purchase-data-card">
                    <div class="data-card-top">
                        <div>
                            <span class="data-card-label">PURCHASE</span>
                            <strong><?= e(thai_datetime((string) $purchase['purchased_at'])) ?></strong>
                        </div>
                        <?php if ($purchase['status'] === 'active'): ?>
                            <span class="badge badge-active">ใช้งาน</span>
                        <?php else: ?>
                            <span class="badge badge-status-cancelled">ยกเลิกแล้ว</span>
                        <?php endif; ?>
                    </div>
                    <div class="data-card-grid">
                        <div>
                            <span class="data-card-label">ยอดซื้อ</span>
                            <strong><?= e(money($purchase['amount'])) ?> ฿</strong>
                            <small>รายการแต้ม #<?= (int) $purchase['id'] ?></small>
                        </div>
                        <div class="data-card-total">
                            <span class="data-card-label">แต้ม</span>
                            <strong>+<?= (int) $purchase['points'] ?></strong>
                            <small>แต้มที่ได้รับ</small>
                        </div>
                    </div>
                    <?php if ($purchase['status'] === 'active'): ?>
                        <form method="post" action="<?= e(app_url('admin/member_detail.php?id=' . $memberId)) ?>" onsubmit="return confirm('ยืนยันยกเลิกรายการนี้หรือไม่? แต้มจะถูกหักออก');">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="purchase_id" value="<?= (int) $purchase['id'] ?>">
                            <button class="button button-small button-danger data-card-action" type="submit">ยกเลิกรายการ</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<div class="actions">
    <a class="button button-muted" href="<?= e(app_url('admin/members.php')) ?>">← กลับรายชื่อสมาชิก</a>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
