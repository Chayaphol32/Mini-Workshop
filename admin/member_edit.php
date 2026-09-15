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

$pageTitle = 'แก้ไขสมาชิก';
$pathPrefix = '../';
$errors = [];
$values = ['name' => (string) $member['name'], 'phone' => (string) $member['phone']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['name'] = trim((string) ($_POST['name'] ?? ''));
    $values['phone'] = trim((string) ($_POST['phone'] ?? ''));
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'คำขอไม่ถูกต้อง กรุณาลองใหม่';
    }
    if ($values['name'] === '' || strlen($values['name']) > 240) {
        $errors[] = 'กรุณากรอกชื่อสมาชิกให้ถูกต้อง';
    }
    if ($values['phone'] === '' || !preg_match('/^[0-9+()\-\s]{6,30}$/', $values['phone'])) {
        $errors[] = 'กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง';
    }
    if ($errors === []) {
        update_member($pdo, $memberId, $values);
        flash('success', 'แก้ไขข้อมูลสมาชิกเรียบร้อยแล้ว');
        redirect(app_url('admin/member_detail.php?id=' . $memberId));
    }
}

require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading"><div><p class="eyebrow">Admin Members</p><h1>แก้ไขข้อมูลสมาชิก</h1><p class="muted">แก้ไขได้เฉพาะชื่อและเบอร์โทร แต้มจะคำนวณจากประวัติซื้อ</p></div><a class="button button-muted" href="<?= e(app_url('admin/member_detail.php?id=' . $memberId)) ?>">กลับรายละเอียด</a></section>
<section class="card form-card">
    <?php if ($errors !== []): ?><ul class="error-list" role="alert"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <form method="post" action="<?= e(app_url('admin/member_edit.php?id=' . $memberId)) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-grid">
            <div class="field field-full"><label>เลขสมาชิก</label><div class="readonly-value"><?= e($member['member_no']) ?></div><p class="field-hint">เลขสมาชิกและแต้มสะสมไม่สามารถแก้จากหน้านี้ได้</p></div>
            <div class="field field-full"><label for="name">ชื่อสมาชิก</label><input id="name" name="name" value="<?= e($values['name']) ?>" maxlength="120" required></div>
            <div class="field field-full"><label for="phone">เบอร์โทรศัพท์</label><input id="phone" name="phone" value="<?= e($values['phone']) ?>" maxlength="30" inputmode="tel" required></div>
        </div>
        <div class="actions form-actions"><button class="button" type="submit">บันทึกการแก้ไข</button><a class="button button-muted" href="<?= e(app_url('admin/member_detail.php?id=' . $memberId)) ?>">ยกเลิก</a></div>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
