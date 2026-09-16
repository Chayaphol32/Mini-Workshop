<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pageTitle = 'เพิ่มสมาชิก';
$pathPrefix = '../';
$errors = [];
$values = [
    'member_no' => '',
    'name' => '',
    'phone' => '',
    'joined_at' => date('Y-m-d'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['member_no'] = trim((string) ($_POST['member_no'] ?? ''));
    $values['name'] = trim((string) ($_POST['name'] ?? ''));
    $values['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $values['joined_at'] = trim((string) ($_POST['joined_at'] ?? ''));

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'คำขอไม่ถูกต้อง กรุณาลองใหม่';
    }
    if ($values['member_no'] === '' || !preg_match('/^[A-Za-z0-9_-]{1,20}$/', $values['member_no'])) {
        $errors[] = 'เลขสมาชิกต้องมี 1-20 ตัวอักษร และใช้ตัวอักษรภาษาอังกฤษ ตัวเลข _ หรือ - เท่านั้น';
    }
    if ($values['name'] === '' || strlen($values['name']) > 240) {
        $errors[] = 'กรุณากรอกชื่อสมาชิกให้ถูกต้อง';
    }
    if ($values['phone'] === '' || !preg_match('/^[0-9+()\-\s]{6,30}$/', $values['phone'])) {
        $errors[] = 'กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง';
    }
    $joinedDate = DateTime::createFromFormat('!Y-m-d', $values['joined_at']);
    if ($joinedDate === false || $joinedDate->format('Y-m-d') !== $values['joined_at']) {
        $errors[] = 'กรุณาเลือกวันที่สมัครให้ถูกต้อง';
    }
    if ($errors === [] && find_member_by_no($pdo, $values['member_no']) !== null) {
        $errors[] = 'เลขสมาชิกนี้มีอยู่แล้ว';
    }

    if ($errors === []) {
        try {
            $memberId = create_member($pdo, $values);
            flash('success', 'เพิ่มสมาชิกเรียบร้อยแล้ว');
            redirect(app_url('admin/member_detail.php?id=' . $memberId));
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] = 'เลขสมาชิกนี้มีอยู่แล้ว';
            } else {
                throw $exception;
            }
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>
<section class="page-header page-heading">
    <div><span class="eyebrow">MEMBER DIRECTORY</span><h1>เพิ่มสมาชิก</h1><p class="muted">สร้างโปรไฟล์สมาชิกก่อน แล้วค่อยผูกบัญชี User ได้ภายหลัง</p></div>
    <div class="page-header-actions"><a class="button button-muted" href="<?= e(app_url('admin/members.php')) ?>">กลับสมาชิก</a></div>
</section>
<section class="card form-card form-shell">
    <div class="section-heading"><div><span class="eyebrow">NEW MEMBER</span><h2>ข้อมูลสมาชิก</h2><p class="muted">ข้อมูลนี้ใช้สำหรับค้นหา ติดต่อ และคำนวณแต้มสะสม</p></div></div>
    <?php if ($errors !== []): ?><ul class="error-list error-summary" role="alert" aria-label="เกิดข้อผิดพลาด"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <form method="post" action="<?= e(app_url('admin/member_create.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-grid">
            <div class="field"><label for="member_no">เลขสมาชิก</label><input id="member_no" name="member_no" value="<?= e($values['member_no']) ?>" maxlength="20" required></div>
            <div class="field"><label for="joined_at">วันที่สมัคร</label><input id="joined_at" type="date" name="joined_at" value="<?= e($values['joined_at']) ?>" required></div>
            <div class="field field-full"><label for="name">ชื่อสมาชิก</label><input id="name" name="name" value="<?= e($values['name']) ?>" maxlength="120" required></div>
            <div class="field field-full"><label for="phone">เบอร์โทรศัพท์</label><input id="phone" name="phone" value="<?= e($values['phone']) ?>" maxlength="30" inputmode="tel" required></div>
        </div>
        <div class="actions form-actions"><button class="button" type="submit">บันทึกสมาชิก</button><a class="button button-muted" href="<?= e(app_url('admin/members.php')) ?>">ยกเลิก</a></div>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
