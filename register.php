<?php
declare(strict_types=1);

define('COFFEE_SKIP_DATABASE', true);
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user() !== null) {
    $user = current_user();
    redirect(app_url(role_home((string) $user['role'])));
}

$pageTitle = 'สมัครสมาชิก';
$errors = [];
$values = [
    'username' => '',
    'name' => '',
    'phone' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/config/database.php';

    $values['username'] = normalize_username((string) ($_POST['username'] ?? ''));
    $values['name'] = trim((string) ($_POST['name'] ?? ''));
    $values['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'คำขอไม่ถูกต้อง กรุณาลองใหม่';
    }
    if (!valid_username($values['username'])) {
        $errors[] = 'ชื่อผู้ใช้ต้องมี 3-40 ตัวอักษร ใช้ภาษาอังกฤษ ตัวเลข จุด ขีดกลาง หรือขีดล่าง';
    }
    if ($values['name'] === '' || strlen($values['name']) > 240) {
        $errors[] = 'กรุณากรอกชื่อให้ถูกต้อง';
    }
    if ($values['phone'] === '' || !preg_match('/^[0-9+()\-\s]{6,30}$/', $values['phone'])) {
        $errors[] = 'กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง';
    }
    if (!valid_password($password)) {
        $errors[] = 'รหัสผ่านต้องมีความยาว 8-255 ตัวอักษร';
    }
    if ($password !== $passwordConfirmation) {
        $errors[] = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน';
    }

    if ($errors === []) {
        $duplicate = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
        $duplicate->execute(['username' => $values['username']]);
        if ($duplicate->fetch() !== false) {
            $errors[] = 'ไม่สามารถใช้ชื่อผู้ใช้นี้ได้ กรุณาเลือกชื่อใหม่';
        }
    }

    if ($errors === []) {
        $pdo->beginTransaction();
        try {
            $temporaryNo = 'TEMP-' . bin2hex(random_bytes(6));
            $memberInsert = $pdo->prepare(
                'INSERT INTO members (member_no, name, phone, joined_at) VALUES (:member_no, :name, :phone, :joined_at)'
            );
            $memberInsert->execute([
                'member_no' => $temporaryNo,
                'name' => $values['name'],
                'phone' => $values['phone'],
                'joined_at' => date('Y-m-d'),
            ]);
            $memberId = (int) $pdo->lastInsertId();
            $memberNo = 'M' . str_pad((string) $memberId, 4, '0', STR_PAD_LEFT);

            $memberUpdate = $pdo->prepare('UPDATE members SET member_no = :member_no WHERE id = :id');
            $memberUpdate->execute(['member_no' => $memberNo, 'id' => $memberId]);

            $account = $pdo->prepare(
                "INSERT INTO users (member_id, username, password_hash, role, status)
                 VALUES (:member_id, :username, :password_hash, 'user', 'active')"
            );
            $account->execute([
                'member_id' => $memberId,
                'username' => $values['username'],
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            $pdo->commit();
            flash('success', 'สมัครสมาชิกเรียบร้อยแล้ว กรุณาเข้าสู่ระบบ');
            redirect(app_url('login.php'));
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception->getCode() === '23000') {
                $errors[] = 'ไม่สามารถสร้างบัญชีได้ ข้อมูลอาจซ้ำกับสมาชิกที่มีอยู่';
            } else {
                throw $exception;
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">สมาชิกใหม่</p>
        <h1>สมัครบัญชี User</h1>
        <p class="muted">ระบบจะสร้างเลขสมาชิกให้อัตโนมัติหลังสมัครสำเร็จ</p>
    </div>
    <a class="button button-muted" href="<?= e(app_url('login.php')) ?>">กลับเข้าสู่ระบบ</a>
</section>

<section class="card form-card">
    <?php if ($errors !== []): ?>
        <ul class="error-list" role="alert">
            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form method="post" action="<?= e(app_url('register.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-grid">
            <div class="field">
                <label for="username">ชื่อผู้ใช้</label>
                <input id="username" name="username" value="<?= e($values['username']) ?>" maxlength="40" autocomplete="username" required>
                <p class="field-hint">ใช้ภาษาอังกฤษ ตัวเลข จุด ขีดกลาง หรือขีดล่าง</p>
            </div>
            <div class="field">
                <label for="phone">เบอร์โทรศัพท์</label>
                <input id="phone" name="phone" value="<?= e($values['phone']) ?>" maxlength="30" inputmode="tel" required>
            </div>
            <div class="field field-full">
                <label for="name">ชื่อ-นามสกุล</label>
                <input id="name" name="name" value="<?= e($values['name']) ?>" maxlength="120" autocomplete="name" required>
            </div>
            <div class="field">
                <label for="password">รหัสผ่าน</label>
                <input id="password" name="password" type="password" minlength="8" maxlength="255" autocomplete="new-password" required>
            </div>
            <div class="field">
                <label for="password_confirmation">ยืนยันรหัสผ่าน</label>
                <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" maxlength="255" autocomplete="new-password" required>
            </div>
        </div>
        <div class="actions form-actions">
            <button class="button" type="submit">สร้างบัญชี</button>
            <a class="button button-muted" href="<?= e(app_url('login.php')) ?>">ยกเลิก</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
