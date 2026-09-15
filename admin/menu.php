<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pageTitle = 'จัดการเมนู';
$pathPrefix = '../';
$menuItems = admin_list_menu_items($pdo);

require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading"><div><p class="eyebrow">Admin Menu</p><h1>จัดการเมนูอาหาร</h1><p class="muted">เปิด/ปิดการขายและแก้ไขราคาสำหรับออเดอร์ใหม่</p></div><div class="actions"><a class="button" href="<?= e(app_url('admin/menu_create.php')) ?>">+ เพิ่มเมนู</a><a class="button button-muted" href="<?= e(app_url('admin/index.php')) ?>">กลับ Dashboard</a></div></section>
<section class="card"><div class="section-heading"><div><h2>เมนูทั้งหมด</h2><p class="muted">เมนู inactive จะไม่แสดงในหน้า User แต่ประวัติออเดอร์เดิมยังใช้ราคา snapshot</p></div></div><div class="table-wrap"><?php if ($menuItems === []): ?><div class="empty">ยังไม่มีเมนูอาหาร</div><?php else: ?><table><thead><tr><th>เมนู</th><th>รายละเอียด</th><th>ราคา</th><th>สถานะ</th><th>อัปเดตล่าสุด</th><th></th></tr></thead><tbody><?php foreach ($menuItems as $menu): ?><tr><td><strong><?= e($menu['name']) ?></strong></td><td><?= e($menu['description']) ?></td><td><?= e(money($menu['price'])) ?> บาท</td><td><?php if ($menu['status'] === 'active'): ?><span class="badge badge-active">เปิดขาย</span><?php else: ?><span class="badge badge-cancelled">ปิดขาย</span><?php endif; ?></td><td><?= e(thai_datetime((string) $menu['updated_at'])) ?></td><td><a class="button button-small button-muted" href="<?= e(app_url('admin/menu_edit.php?id=' . (int) $menu['id'])) ?>">แก้ไข</a></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
