<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pageTitle = 'เพิ่มเมนู';
$pathPrefix = '../';
$errors = [];
$values = ['name' => '', 'description' => '', 'price' => '', 'status' => 'active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['name'] = trim((string) ($_POST['name'] ?? ''));
    $values['description'] = trim((string) ($_POST['description'] ?? ''));
    $values['price'] = trim((string) ($_POST['price'] ?? ''));
    $values['status'] = trim((string) ($_POST['status'] ?? ''));
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'คำขอไม่ถูกต้อง กรุณาลองใหม่';
    }
    if ($values['name'] === '' || strlen($values['name']) > 120) {
        $errors[] = 'ชื่อเมนูต้องมีความยาว 1-120 ตัวอักษร';
    }
    if (strlen($values['description']) > 255) {
        $errors[] = 'รายละเอียดเมนูยาวได้ไม่เกิน 255 ตัวอักษร';
    }
    $price = parse_positive_amount($values['price']);
    if ($price === null || $price > 99999999.99) {
        $errors[] = 'ราคาต้องเป็นตัวเลขมากกว่า 0 และไม่เกิน 99,999,999.99';
    }
    if (!in_array($values['status'], ['active', 'inactive'], true)) {
        $errors[] = 'สถานะเมนูไม่ถูกต้อง';
    }
    if ($errors === []) {
        admin_create_menu_item($pdo, ['name' => $values['name'], 'description' => $values['description'], 'price' => $price, 'status' => $values['status']]);
        flash('success', 'เพิ่มเมนูเรียบร้อยแล้ว');
        redirect(app_url('admin/menu.php'));
    }
}

require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading"><div><p class="eyebrow">Admin Menu</p><h1>เพิ่มเมนูอาหาร</h1><p class="muted">เมนูใหม่จะแสดงให้ User เห็นเมื่อสถานะเป็นเปิดขาย</p></div><a class="button button-muted" href="<?= e(app_url('admin/menu.php')) ?>">กลับเมนู</a></section>
<section class="card form-card"><?php if ($errors !== []): ?><ul class="error-list" role="alert"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php endif; ?><form method="post" action="<?= e(app_url('admin/menu_create.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="form-grid"><div class="field field-full"><label for="name">ชื่อเมนู</label><input id="name" name="name" value="<?= e($values['name']) ?>" maxlength="120" required></div><div class="field field-full"><label for="description">รายละเอียด</label><input id="description" name="description" value="<?= e($values['description']) ?>" maxlength="255"></div><div class="field"><label for="price">ราคา (บาท)</label><input id="price" name="price" value="<?= e($values['price']) ?>" inputmode="decimal" pattern="[0-9]+(\.[0-9]{1,2})?" required></div><div class="field"><label for="status">สถานะ</label><select id="status" name="status"><option value="active" <?= $values['status'] === 'active' ? 'selected' : '' ?>>เปิดขาย</option><option value="inactive" <?= $values['status'] === 'inactive' ? 'selected' : '' ?>>ปิดขาย</option></select></div></div><div class="actions form-actions"><button class="button" type="submit">บันทึกเมนู</button><a class="button button-muted" href="<?= e(app_url('admin/menu.php')) ?>">ยกเลิก</a></div></form></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
