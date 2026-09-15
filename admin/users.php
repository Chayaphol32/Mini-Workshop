<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pageTitle = 'จัดการบัญชี';
$pathPrefix = '../';
$errors = [];
$term = trim((string) ($_GET['q'] ?? ''));
$prefillMemberId = parse_id($_GET['member_id'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'คำขอไม่ถูกต้อง กรุณาลองใหม่';
    } elseif ($action === 'toggle') {
        $userId = parse_id($_POST['user_id'] ?? null);
        $status = trim((string) ($_POST['status'] ?? ''));
        if ($userId === null || !admin_set_user_status($pdo, $userId, $status)) {
            $errors[] = 'ไม่สามารถเปลี่ยนสถานะบัญชีนี้ได้';
        } else {
            flash('success', 'เปลี่ยนสถานะบัญชีเรียบร้อยแล้ว');
            redirect(app_url('admin/users.php'));
        }
    } elseif ($action === 'create') {
        $memberId = parse_id($_POST['member_id'] ?? null);
        $username = normalize_username((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');
        $prefillMemberId = $memberId;
        if ($memberId === null || find_member($pdo, $memberId) === null || admin_find_user_for_member($pdo, $memberId) !== null) {
            $errors[] = 'กรุณาเลือกสมาชิกที่ยังไม่มีบัญชี';
        }
        if (!valid_username($username)) {
            $errors[] = 'ชื่อผู้ใช้ต้องมี 3-40 ตัวอักษร และใช้ภาษาอังกฤษ ตัวเลข จุด ขีดกลาง หรือขีดล่าง';
        }
        if (!valid_password($password)) {
            $errors[] = 'รหัสผ่านต้องมีความยาว 8-255 ตัวอักษร';
        }
        if ($password !== $confirmation) {
            $errors[] = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน';
        }
        if ($errors === []) {
            $duplicate = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
            $duplicate->execute(['username' => $username]);
            if ($duplicate->fetch() !== false) {
                $errors[] = 'ไม่สามารถใช้ชื่อผู้ใช้นี้ได้ กรุณาเลือกชื่อใหม่';
            }
        }
        if ($errors === []) {
            try {
                admin_create_user_for_member($pdo, $memberId, $username, $password);
                flash('success', 'สร้างบัญชี User เรียบร้อยแล้ว');
                redirect(app_url('admin/users.php'));
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $errors[] = 'ข้อมูลบัญชีซ้ำ กรุณาตรวจสอบอีกครั้ง';
                } else {
                    throw $exception;
                }
            }
        }
    } else {
        $errors[] = 'การกระทำไม่ถูกต้อง';
    }
}

$users = admin_list_users($pdo, $term);
$availableMembers = admin_members_without_user($pdo);
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading"><div><p class="eyebrow">Admin Accounts</p><h1>จัดการบัญชีผู้ใช้</h1><p class="muted">เปิด/ปิดบัญชี User และสร้าง login ให้สมาชิกเดิม</p></div><a class="button button-muted" href="<?= e(app_url('admin/index.php')) ?>">กลับ Dashboard</a></section>

<?php if ($errors !== []): ?><ul class="error-list" role="alert"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php endif; ?>

<section class="card"><div class="section-heading"><div><h2>สร้างบัญชี User</h2><p class="muted">บัญชีหนึ่งผูกกับสมาชิกได้หนึ่งคน และ password จะถูก hash ก่อนบันทึก</p></div></div><?php if ($availableMembers === []): ?><div class="empty">สมาชิกทุกคนมีบัญชีแล้ว</div><?php else: ?><form method="post" action="<?= e(app_url('admin/users.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create"><div class="form-grid"><div class="field"><label for="member_id">สมาชิก</label><select id="member_id" name="member_id" required><option value="">เลือกสมาชิก</option><?php foreach ($availableMembers as $member): ?><option value="<?= (int) $member['id'] ?>" <?= $prefillMemberId === (int) $member['id'] ? 'selected' : '' ?>><?= e($member['member_no'] . ' · ' . $member['name']) ?></option><?php endforeach; ?></select></div><div class="field"><label for="username">ชื่อผู้ใช้</label><input id="username" name="username" maxlength="40" autocomplete="username" required></div><div class="field"><label for="password">รหัสผ่าน</label><input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required></div><div class="field"><label for="password_confirmation">ยืนยันรหัสผ่าน</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required></div></div><div class="actions form-actions"><button class="button" type="submit">สร้างบัญชี User</button></div></form><?php endif; ?></section>

<section class="card"><div class="section-heading"><div><h2>บัญชีทั้งหมด</h2><p class="muted">ค้นหาได้จาก username เลขสมาชิก หรือชื่อสมาชิก</p></div></div><form class="actions filter-card" method="get" action="<?= e(app_url('admin/users.php')) ?>"><label class="sr-only" for="q">ค้นหาบัญชี</label><input id="q" name="q" value="<?= e($term) ?>" placeholder="ค้นหา username หรือสมาชิก..."><button class="button" type="submit">ค้นหา</button><?php if ($term !== ''): ?><a class="button button-muted" href="<?= e(app_url('admin/users.php')) ?>">ล้าง</a><?php endif; ?></form><div class="table-wrap"><?php if ($users === []): ?><div class="empty">ไม่พบบัญชี</div><?php else: ?><table><thead><tr><th>Username</th><th>บทบาท</th><th>สมาชิก</th><th>สถานะ</th><th>เข้าใช้ล่าสุด</th><th></th></tr></thead><tbody><?php foreach ($users as $account): ?><tr><td><strong><?= e($account['username']) ?></strong></td><td><span class="badge <?= $account['role'] === 'admin' ? 'badge-admin' : 'badge-user' ?>"><?= $account['role'] === 'admin' ? 'Admin' : 'User' ?></span></td><td><?= $account['member_no'] === null ? '<span class="muted">—</span>' : e($account['member_no'] . ' · ' . $account['member_name']) ?></td><td><span class="badge <?= $account['status'] === 'active' ? 'badge-active' : 'badge-cancelled' ?>"><?= $account['status'] === 'active' ? 'ใช้งาน' : 'ปิดใช้งาน' ?></span></td><td><?= $account['last_login_at'] === null ? '<span class="muted">ยังไม่เคยเข้าใช้</span>' : e(thai_datetime((string) $account['last_login_at'])) ?></td><td><?php if ($account['role'] === 'user'): ?><form method="post" action="<?= e(app_url('admin/users.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="user_id" value="<?= (int) $account['id'] ?>"><input type="hidden" name="status" value="<?= $account['status'] === 'active' ? 'disabled' : 'active' ?>"><button class="button button-small <?= $account['status'] === 'active' ? 'button-danger' : '' ?>" type="submit"><?= $account['status'] === 'active' ? 'ปิดใช้งาน' : 'เปิดใช้งาน' ?></button></form><?php else: ?><span class="muted">บัญชีหลัก</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
