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

$totalPoints = (int) $member['total_points'];
$level = member_level($totalPoints);

require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Account Settings</p>
        <h1>ข้อมูลส่วนตัว</h1>
        <p class="muted">ตรวจสอบและแก้ไขข้อมูลสมาชิกของคุณ</p>
    </div>
    <a class="button button-muted" href="<?= e(app_url('user/index.php')) ?>">← กลับหน้าหลัก</a>
</section>

<div class="grid grid-2">
    <section class="card">
        <p class="eyebrow">Membership Status</p>
        <h2>สถานะบัตรสมาชิก</h2>
        <div style="margin: 20px 0; display: flex; align-items: center; gap: 16px;">
            <div class="nav-avatar" style="width: 54px; height: 54px; font-size: 1.4rem;">
                <?= e(text_initial((string) $member['name'])) ?>
            </div>
            <div>
                <strong style="font-size: 1.2rem; color: var(--espresso-950); display: block;"><?= e((string) $member['name']) ?></strong>
                <span class="badge badge-<?= e($level['key']) ?>"><?= e($level['label']) ?></span>
            </div>
        </div>
        <div style="display: grid; gap: 12px; font-size: 0.92rem; border-top: 1px solid var(--border-light); padding-top: 16px;">
            <div style="display: flex; justify-content: space-between;">
                <span class="muted">เลขที่สมาชิก</span>
                <strong><?= e((string) $member['member_no']) ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span class="muted">แต้มสะสมทั้งหมด</span>
                <strong style="color: var(--amber-500);"><?= number_format($totalPoints) ?> แต้ม</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span class="muted">ชื่อบัญชีผู้ใช้</span>
                <strong><?= e((string) $user['username']) ?></strong>
            </div>
        </div>
    </section>

    <section class="card form-card">
        <h2>แก้ไขข้อมูล</h2>
        <p class="muted" style="margin-bottom: 20px;">อัปเดตชื่อและเบอร์โทรติดต่อสำหรับรับการแจ้งเตือน</p>
        <?php if ($errors !== []): ?>
            <ul class="error-list" role="alert">
                <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form method="post" action="<?= e(app_url('user/profile.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="form-grid" style="grid-template-columns: 1fr;">
                <div class="field">
                    <label for="name">ชื่อ-นามสกุล</label>
                    <input id="name" name="name" value="<?= e($values['name']) ?>" maxlength="120" placeholder="ชื่อ นามสกุล" required>
                </div>
                <div class="field">
                    <label for="phone">เบอร์โทรศัพท์</label>
                    <input id="phone" name="phone" value="<?= e($values['phone']) ?>" maxlength="30" inputmode="tel" placeholder="08x-xxx-xxxx" required>
                    <span class="field-hint">ใช้สำหรับตรวจสอบความเป็นเจ้าของบัญชี</span>
                </div>
            </div>
            <div class="actions" style="margin-top: 24px;">
                <button class="button button-primary" type="submit">บันทึกข้อมูล</button>
                <a class="button button-muted" href="<?= e(app_url('user/index.php')) ?>">ยกเลิก</a>
            </div>
        </form>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
