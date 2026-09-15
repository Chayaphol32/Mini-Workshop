<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pageTitle = 'บันทึกยอดซื้อ';
$pathPrefix = '../';
$errors = [];
$term = trim((string) ($_GET['q'] ?? ''));
$selectedMemberId = parse_id($_GET['member_id'] ?? null);
$amountInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $term = trim((string) ($_POST['q'] ?? ''));
    $selectedMemberId = parse_id($_POST['member_id'] ?? null);
    $amountInput = trim((string) ($_POST['amount'] ?? ''));
}

$selectedMember = $selectedMemberId === null ? null : find_member($pdo, $selectedMemberId);
if ($selectedMemberId !== null && $selectedMember === null) {
    $errors[] = 'ไม่พบสมาชิกที่เลือก';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'คำขอไม่ถูกต้อง กรุณาลองใหม่';
    }
    if ($selectedMemberId === null) {
        $errors[] = 'กรุณาเลือกสมาชิก';
    }
    $amount = parse_positive_amount($amountInput);
    if ($amount === null) {
        $errors[] = 'ยอดซื้อต้องเป็นตัวเลขมากกว่า 0 และมีทศนิยมไม่เกิน 2 ตำแหน่ง';
    }
    if ($errors === []) {
        $points = calculate_points($amount);
        create_purchase($pdo, $selectedMemberId, $amount, $points);
        flash('success', 'บันทึกยอดซื้อ ' . money($amount) . ' บาท ได้รับ ' . $points . ' แต้ม');
        redirect(app_url('admin/member_detail.php?id=' . $selectedMemberId));
    }
}

$members = search_members($pdo, $term);
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading"><div><p class="eyebrow">Manual Rewards</p><h1>บันทึกยอดซื้อ</h1><p class="muted">ค้นหาสมาชิก ตรวจสอบตัวตน แล้วบันทึกยอดซื้อแบบ Manual</p></div><a class="button button-muted" href="<?= e(app_url('admin/members.php')) ?>">กลับสมาชิก</a></section>
<section class="card"><h2>1. ค้นหาและเลือกสมาชิก</h2><form class="actions" method="get" action="<?= e(app_url('admin/purchase.php')) ?>"><label class="sr-only" for="q">ค้นหาเลขสมาชิกหรือชื่อ</label><input id="q" name="q" value="<?= e($term) ?>" placeholder="ค้นหาเลขสมาชิกหรือชื่อ..."><button class="button" type="submit">ค้นหา</button><?php if ($term !== ''): ?><a class="button button-muted" href="<?= e(app_url('admin/purchase.php')) ?>">ล้าง</a><?php endif; ?></form><?php if ($members === []): ?><div class="empty">ไม่พบสมาชิกจากคำค้นนี้</div><?php else: ?><div class="table-wrap member-picker"><table><thead><tr><th>เลขสมาชิก</th><th>ชื่อ</th><th>เบอร์โทร</th><th>แต้มรวม</th><th></th></tr></thead><tbody><?php foreach ($members as $member): ?><?php $selectUrl = app_url('admin/purchase.php?' . http_build_query(['q' => $term, 'member_id' => (int) $member['id']])); ?><tr class="<?= $selectedMemberId === (int) $member['id'] ? 'selected-row' : '' ?>"><td><strong><?= e($member['member_no']) ?></strong></td><td><?= e($member['name']) ?></td><td><?= e($member['phone']) ?></td><td><?= (int) $member['total_points'] ?> แต้ม</td><td><a class="button button-small" href="<?= e($selectUrl) ?>"><?= $selectedMemberId === (int) $member['id'] ? 'เลือกแล้ว' : 'เลือกสมาชิก' ?></a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<section class="card"><h2>2. บันทึกยอดซื้อ</h2><?php if ($errors !== []): ?><ul class="error-list" role="alert"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php endif; ?><?php if ($selectedMember !== null): ?><div class="selected-member"><p class="eyebrow">สมาชิกที่เลือก</p><h3><?= e($selectedMember['name']) ?></h3><p class="member-meta"><span>เลขสมาชิก <?= e($selectedMember['member_no']) ?></span><span>โทร <?= e($selectedMember['phone']) ?></span><span>แต้มเดิม <?= (int) $selectedMember['total_points'] ?> แต้ม</span></p></div><form method="post" action="<?= e(app_url('admin/purchase.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="member_id" value="<?= (int) $selectedMember['id'] ?>"><input type="hidden" name="q" value="<?= e($term) ?>"><div class="field"><label for="amount">ยอดซื้อ (บาท)</label><input id="amount" name="amount" value="<?= e($amountInput) ?>" inputmode="decimal" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="เช่น 125.00" required><p class="field-hint">ระบบจะปัดเศษลงและคำนวณแต้มจากยอดซื้อรายการนี้</p></div><div class="preview-box"><span>แต้มที่จะได้รับจากรายการนี้</span><strong><span id="points-preview">0</span> แต้ม</strong></div><div class="actions form-actions"><button class="button" type="submit">ยืนยันบันทึกยอดซื้อ</button><a class="button button-muted" href="<?= e(app_url('admin/purchase.php')) ?>">เลือกสมาชิกใหม่</a></div></form><?php else: ?><div class="empty">เลือกสมาชิกจากตารางด้านบนก่อนกรอกยอดซื้อ</div><?php endif; ?></section>
<script>
const amountInput = document.getElementById('amount');
const pointsPreview = document.getElementById('points-preview');
if (amountInput && pointsPreview) {
    const updatePointsPreview = () => {
        const amount = Number(amountInput.value);
        pointsPreview.textContent = Number.isFinite(amount) && amount > 0 ? String(Math.floor(amount / 10)) : '0';
    };
    amountInput.addEventListener('input', updatePointsPreview);
    updatePointsPreview();
}
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
