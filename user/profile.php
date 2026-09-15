<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('user');

$user = current_user();
$memberId = (int) ($user['member_id'] ?? 0);
$member = $memberId > 0 ? find_member($pdo, $memberId) : null;
if ($member === null) {
    http_response_code(500);
    exit('ไม่พบข้อมูลสมาชิกที่ผูกกับบัญชี');
}

$pageTitle = 'ข้อมูลส่วนตัว';
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
        $errors[] = 'กรุณากรอกชื่อให้ถูกต้อง';
    }
    if ($values['phone'] === '' || !preg_match('/^[0-9+()\-\s]{6,30}$/', $values['phone'])) {
        $errors[] = 'กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง';
    }

    if ($errors === []) {
        update_member_profile($pdo, $memberId, $values['name'], $values['phone']);
        $_SESSION['auth_user']['name'] = $values['name'];
        $_SESSION['auth_user']['phone'] = $values['phone'];
        flash('success', 'บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว');
        redirect(app_url('user/profile.php'));
    }
}

require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">My Profile</p>
        <h1>ข้อมูลส่วนตัว</h1>
        <p class="muted">แก้ไขได้เฉพาะชื่อและเบอร์โทรศัพท์</p>
    </div>
    <a class="button button-muted" href="<?= e(app_url('user/index.php')) ?>">กลับหน้าหลัก</a>
</section>

<section class="card form-card">
    <?php if ($errors !== []): ?>
        <ul class="error-list" role="alert">
            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form method="post" action="<?= e(app_url('user/profile.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-grid">
            <div class="field">
                <label for="username">ชื่อผู้ใช้</label>
                <div class="readonly-value" id="username"><?= e((string) $user['username']) ?></div>
            </div>
            <div class="field">
                <label for="member_no">เลขสมาชิก</label>
                <div class="readonly-value" id="member_no"><?= e((string) $member['member_no']) ?></div>
            </div>
            <div class="field field-full">
                <label for="name">ชื่อ-นามสกุล</label>
                <input id="name" name="name" value="<?= e($values['name']) ?>" maxlength="120" required>
            </div>
            <div class="field field-full">
                <label for="phone">เบอร์โทรศัพท์</label>
                <input id="phone" name="phone" value="<?= e($values['phone']) ?>" maxlength="30" inputmode="tel" required>
            </div>
        </div>
        <div class="actions form-actions">
            <button class="button" type="submit">บันทึกข้อมูล</button>
            <a class="button button-muted" href="<?= e(app_url('user/index.php')) ?>">ยกเลิก</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
