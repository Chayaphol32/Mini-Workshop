<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pageTitle = 'จัดการเมนูอาหาร';
$pathPrefix = '../';
$menuItems = admin_list_menu_items($pdo);

require __DIR__ . '/../includes/header.php';
?>
<section class="page-header page-heading">
    <div>
        <span class="eyebrow">MENU CATALOG</span>
        <h1>เมนูอาหารและเครื่องดื่ม</h1>
        <p class="muted">ควบคุมรายการ ราคา และสถานะการขายจากพื้นที่เดียว</p>
    </div>
    <div class="page-header-actions">
        <a class="button button-primary" href="<?= e(app_url('admin/menu_create.php')) ?>"><span aria-hidden="true">+</span> เพิ่มเมนู</a>
    </div>
</section>

<section class="card catalog-card">
    <div class="section-heading">
        <div>
            <span class="eyebrow">CATALOG</span>
            <h2>รายการในร้าน</h2>
            <p class="muted">เมนูปิดขายจะไม่แสดงให้ User สั่ง แต่ออเดอร์เดิมยังคงใช้ราคาตามประวัติ</p>
        </div>
        <span class="badge badge-user"><?= number_format(count($menuItems)) ?> รายการ</span>
    </div>
    <?php if ($menuItems === []): ?>
        <div class="empty-state">
            <span class="empty-state-icon" aria-hidden="true">☕</span>
            <h2>ยังไม่มีเมนูอาหาร</h2>
            <p>เพิ่มเมนูแรกเพื่อให้สมาชิกเริ่มสั่งอาหารได้</p>
            <a class="button button-primary" href="<?= e(app_url('admin/menu_create.php')) ?>">เพิ่มเมนูใหม่</a>
        </div>
    <?php else: ?>
        <div class="table-wrap data-table-desktop">
            <table>
                <caption class="sr-only">รายการเมนูอาหารและเครื่องดื่ม</caption>
                <thead>
                    <tr>
                        <th scope="col">ชื่อเมนู</th>
                        <th scope="col">คำอธิบาย</th>
                        <th scope="col">ราคาขาย</th>
                        <th scope="col">สถานะการขาย</th>
                        <th scope="col">อัปเดตล่าสุด</th>
                        <th scope="col" style="text-align: right;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($menuItems as $menu): ?>
                    <tr>
                        <td>
                            <div class="table-person">
                                <span class="menu-mini-icon" aria-hidden="true">☕</span>
                                <strong><?= e($menu['name']) ?></strong>
                            </div>
                        </td>
                        <td><span class="muted"><?= e($menu['description']) ?></span></td>
                        <td><strong><?= e(money($menu['price'])) ?> ฿</strong></td>
                        <td>
                            <?php if ($menu['status'] === 'active'): ?>
                                <span class="badge badge-active">เปิดขาย</span>
                            <?php else: ?>
                                <span class="badge badge-status-cancelled">ปิดการขาย</span>
                            <?php endif; ?>
                        </td>
                        <td><small class="muted"><?= e(thai_datetime((string) $menu['updated_at'])) ?></small></td>
                        <td style="text-align: right;">
                            <a class="button button-small button-muted" href="<?= e(app_url('admin/menu_edit.php?id=' . (int) $menu['id'])) ?>">แก้ไขเมนู</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="data-list-mobile menu-data-list" aria-label="รายการเมนูบนมือถือ">
            <?php foreach ($menuItems as $menu): ?>
                <article class="data-card menu-data-card">
                    <div class="data-card-top">
                        <div class="table-person">
                            <span class="menu-mini-icon" aria-hidden="true">☕</span>
                            <div>
                                <span class="data-card-label">MENU ITEM</span>
                                <strong><?= e($menu['name']) ?></strong>
                            </div>
                        </div>
                        <?php if ($menu['status'] === 'active'): ?>
                            <span class="badge badge-active">เปิดขาย</span>
                        <?php else: ?>
                            <span class="badge badge-status-cancelled">ปิดขาย</span>
                        <?php endif; ?>
                    </div>
                    <p class="data-card-description"><?= e($menu['description']) ?></p>
                    <div class="data-card-grid">
                        <div>
                            <span class="data-card-label">ราคา</span>
                            <strong><?= e(money($menu['price'])) ?> ฿</strong>
                        </div>
                        <div class="data-card-total">
                            <span class="data-card-label">อัปเดต</span>
                            <small><?= e(thai_datetime((string) $menu['updated_at'])) ?></small>
                        </div>
                    </div>
                    <a class="button button-small button-primary data-card-action" href="<?= e(app_url('admin/menu_edit.php?id=' . (int) $menu['id'])) ?>">แก้ไขเมนู</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
