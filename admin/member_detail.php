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

$pageTitle = 'รายละเอียดสมาชิก';
$pathPrefix = '../';
$purchases = find_purchases($pdo, $memberId);
$account = admin_find_user_for_member($pdo, $memberId);
$totalPoints = (int) $member['total_points'];
$level = member_level($totalPoints);

require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading detail-header"><div><p class="eyebrow">Admin Member Detail</p><h1><?= e($member['name']) ?></h1><p class="member-meta"><span>เลขสมาชิก <?= e($member['member_no']) ?></span><span>โทร <?= e($member['phone']) ?></span><span>สมัครเมื่อ <?= e(thai_date((string) $member['joined_at'])) ?></span></p></div><div class="actions"><a class="button" href="<?= e(app_url('admin/purchase.php?member_id=' . $memberId)) ?>">+ บันทึกยอดซื้อ</a><a class="button button-muted" href="<?= e(app_url('admin/member_edit.php?id=' . $memberId)) ?>">แก้ไขข้อมูล</a></div></section>

<section class="stats-grid" aria-label="สรุปแต้มสมาชิก"><div class="stat-card"><p>แต้มสะสมปัจจุบัน</p><strong><?= $totalPoints ?> แต้ม</strong></div><div class="stat-card"><p>ระดับสมาชิก</p><strong><span class="badge badge-<?= e($level['key']) ?>"><?= e($level['label']) ?></span></strong></div><div class="stat-card"><p>จำนวนรายการทั้งหมด</p><strong><?= count($purchases) ?> รายการ</strong></div></section>

<section class="card"><div class="section-heading"><div><h2>บัญชี User</h2><p class="muted">บัญชีนี้ใช้สำหรับให้สมาชิกเข้าสู่หน้า User</p></div></div><?php if ($account === null): ?><p class="muted">สมาชิกคนนี้ยังไม่มีบัญชีเข้าสู่ระบบ</p><a class="button button-small" href="<?= e(app_url('admin/users.php?member_id=' . $memberId)) ?>">สร้างบัญชี User</a><?php else: ?><div class="detail-list"><div><span>ชื่อผู้ใช้</span><strong><?= e($account['username']) ?></strong></div><div><span>สถานะ</span><strong><span class="badge <?= $account['status'] === 'active' ? 'badge-active' : 'badge-cancelled' ?>"><?= $account['status'] === 'active' ? 'ใช้งาน' : 'ปิดใช้งาน' ?></span></strong></div></div><?php endif; ?></section>

<section class="card"><div class="section-heading"><div><h2>ประวัติการซื้อและแต้ม</h2><p class="muted">รายการที่ยกเลิกแล้วจะแสดงไว้ แต่ไม่นับแต้ม</p></div></div><div class="table-wrap"><?php if ($purchases === []): ?><div class="empty">สมาชิกคนนี้ยังไม่มีประวัติซื้อ</div><?php else: ?><table><thead><tr><th>วันเวลา</th><th>ยอดซื้อ</th><th>แต้ม</th><th>สถานะ</th><th>จัดการ</th></tr></thead><tbody><?php foreach ($purchases as $purchase): ?><tr><td><?= e(thai_datetime((string) $purchase['purchased_at'])) ?></td><td><?= e(money($purchase['amount'])) ?> บาท</td><td><?= (int) $purchase['points'] ?> แต้ม</td><td><?php if ($purchase['status'] === 'active'): ?><span class="badge badge-active">ใช้งาน</span><?php else: ?><span class="badge badge-cancelled">ยกเลิกแล้ว</span><?php endif; ?></td><td><?php if ($purchase['status'] === 'active'): ?><form method="post" action="<?= e(app_url('admin/member_detail.php?id=' . $memberId)) ?>" onsubmit="return confirm('ยืนยันยกเลิกรายการนี้หรือไม่?');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="purchase_id" value="<?= (int) $purchase['id'] ?>"><button class="button button-small button-danger" type="submit">ยกเลิก</button></form><?php else: ?><span class="muted">ดำเนินการแล้ว</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div></section>
<a class="button button-muted" href="<?= e(app_url('admin/members.php')) ?>">กลับรายชื่อสมาชิก</a>
<?php require __DIR__ . '/../includes/footer.php'; ?>
