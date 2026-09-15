<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('user');

$pageTitle = 'สั่งอาหาร';
$pathPrefix = '../';
$menuItems = active_menu_items($pdo);
$orderErrors = $_SESSION['order_errors'] ?? [];
$selectedQuantities = $_SESSION['order_quantities'] ?? [];
unset($_SESSION['order_errors'], $_SESSION['order_quantities']);

require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Order from our café</p>
        <h1>เลือกเมนูที่ชอบ</h1>
        <p class="muted">เลือกจำนวนแล้วกดยืนยัน ระบบจะคำนวณราคาจากเมนูปัจจุบันบน server</p>
    </div>
    <a class="button button-muted" href="<?= e(app_url('user/orders.php')) ?>">ออเดอร์ของฉัน</a>
</section>

<?php if ($orderErrors !== []): ?>
    <ul class="error-list" role="alert">
        <?php foreach ($orderErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($menuItems === []): ?>
    <section class="card empty">ขณะนี้ยังไม่มีเมนูที่เปิดขาย</section>
<?php else: ?>
    <form method="post" action="<?= e(app_url('user/order_create.php')) ?>" id="menu-order-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="menu-grid">
            <?php foreach ($menuItems as $menu): ?>
                <?php $menuId = (int) $menu['id']; ?>
                <article class="menu-card">
                    <div class="menu-card-icon" aria-hidden="true">☕</div>
                    <div class="menu-card-content">
                        <h2><?= e($menu['name']) ?></h2>
                        <p class="muted"><?= e($menu['description']) ?></p>
                        <div class="menu-card-bottom">
                            <strong class="menu-price"><?= e(money($menu['price'])) ?> บาท</strong>
                            <label class="quantity-field" for="quantity-<?= $menuId ?>">
                                <span>จำนวน</span>
                                <input id="quantity-<?= $menuId ?>" name="quantities[<?= $menuId ?>]" type="number" min="0" max="99" step="1" value="<?= e((string) ($selectedQuantities[$menuId] ?? '0')) ?>">
                            </label>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <section class="card order-submit-card">
            <div>
                <p class="eyebrow">พร้อมแล้วใช่ไหม?</p>
                <h2>ยืนยันรายการสั่งอาหาร</h2>
                <p class="muted">ออเดอร์จะเริ่มที่สถานะรอดำเนินการ และรอ Admin ยืนยันขั้นตอนต่อไป</p>
            </div>
            <button class="button" type="submit">ส่งออเดอร์</button>
        </section>
    </form>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
