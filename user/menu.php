<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_role('user');

$pageTitle = 'สั่งอาหารและเครื่องดื่ม';
$pathPrefix = '../';
$menuItems = active_menu_items($pdo);
$orderErrors = $_SESSION['order_errors'] ?? [];
$selectedQuantities = $_SESSION['order_quantities'] ?? [];
unset($_SESSION['order_errors'], $_SESSION['order_quantities']);

$getMenuIcon = static function (string $name): string {
    $n = text_lower($name);
    if (str_contains($n, 'ชา') || str_contains($n, 'tea') || str_contains($n, 'matcha') || str_contains($n, 'มัทฉะ')) return '🍵';
    if (str_contains($n, 'ครัวซอง') || str_contains($n, 'croissant') || str_contains($n, 'ขนม') || str_contains($n, 'cake') || str_contains($n, 'เค้ก') || str_contains($n, 'เบเกอรี่') || str_contains($n, 'cookie')) return '🥐';
    if (str_contains($n, 'เย็น') || str_contains($n, 'iced') || str_contains($n, 'frappe') || str_contains($n, 'ปั่น') || str_contains($n, 'โซดา')) return '🧋';
    if (str_contains($n, 'latte') || str_contains($n, 'ลาเต้') || str_contains($n, 'นม') || str_contains($n, 'milk')) return '🥛';
    if (str_contains($n, 'โกโก้') || str_contains($n, 'ช็อกโกแลต') || str_contains($n, 'chocolate')) return '🍫';
    return '☕';
};

$getMenuCategory = static function (string $name): string {
    $n = text_lower($name);
    if (str_contains($n, 'ครัวซอง') || str_contains($n, 'croissant') || str_contains($n, 'ขนม') || str_contains($n, 'cake') || str_contains($n, 'เค้ก') || str_contains($n, 'เบเกอรี่') || str_contains($n, 'cookie')) return 'bakery';
    if (str_contains($n, 'ชา') || str_contains($n, 'tea') || str_contains($n, 'matcha') || str_contains($n, 'มัทฉะ') || str_contains($n, 'โซดา') || str_contains($n, 'โกโก้')) return 'tea';
    return 'coffee';
};

require __DIR__ . '/../includes/header.php';
?>
<section class="page-header page-heading page-heading-premium">
    <div>
        <span class="eyebrow">ORDER SOMETHING GOOD</span>
        <h1>เลือกแก้วโปรดของคุณ</h1>
        <p class="muted">สั่งง่าย สะสมแต้มทุกแก้ว — ทุก 10 บาทรับ 1 แต้ม</p>
    </div>
    <div class="page-header-actions">
        <a class="button button-muted" href="<?= e(app_url('user/orders.php')) ?>"><span aria-hidden="true">◷</span> ออเดอร์ของฉัน</a>
    </div>
</section>

<section class="menu-welcome hero-card" aria-label="แนะนำเมนูประจำวัน">
    <div class="menu-welcome-copy">
        <span class="menu-welcome-kicker">TODAY'S BREW</span>
        <h2>พักสักครู่ แล้วเลือกสิ่งที่ใช่</h2>
        <p>เมนูที่คัดมาให้พร้อมเสิร์ฟในแบบของคุณ</p>
    </div>
    <div class="menu-welcome-art" aria-hidden="true">
        <span class="menu-welcome-steam menu-welcome-steam-one"></span>
        <span class="menu-welcome-steam menu-welcome-steam-two"></span>
        <span class="menu-welcome-cup">☕</span>
    </div>
</section>

<?php if ($orderErrors !== []): ?>
    <ul class="error-list error-summary" role="alert" aria-label="เกิดข้อผิดพลาดในการสั่งอาหาร">
        <?php foreach ($orderErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($menuItems === []): ?>
    <section class="empty-state card" aria-labelledby="menu-empty-title">
        <span class="empty-state-icon" aria-hidden="true">☕</span>
        <h2 id="menu-empty-title">ยังไม่มีเมนูที่เปิดขาย</h2>
        <p>กรุณากลับมาใหม่อีกครั้ง หรือดูประวัติออเดอร์ของคุณ</p>
        <a class="button button-muted" href="<?= e(app_url('user/orders.php')) ?>">ดูออเดอร์ของฉัน</a>
    </section>
<?php else: ?>
    <section class="menu-controls" aria-label="ค้นหาและกรองเมนู">
        <div class="menu-search">
            <label for="menu-search">ค้นหาเมนู</label>
            <span class="menu-search-icon" aria-hidden="true">⌕</span>
            <input id="menu-search" type="search" placeholder="ค้นหากาแฟ ชา หรือขนม..." autocomplete="off">
        </div>
        <div class="filter-pills" aria-label="หมวดหมู่เมนู">
            <button class="pill-btn active" type="button" aria-pressed="true" data-filter="all">ทั้งหมด <span><?= count($menuItems) ?></span></button>
            <button class="pill-btn" type="button" aria-pressed="false" data-filter="coffee">กาแฟ</button>
            <button class="pill-btn" type="button" aria-pressed="false" data-filter="tea">ชาและเครื่องดื่ม</button>
            <button class="pill-btn" type="button" aria-pressed="false" data-filter="bakery">เบเกอรี่</button>
        </div>
    </section>

    <form method="post" action="<?= e(app_url('user/order_create.php')) ?>" id="menu-order-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="menu-grid" id="menu-grid" aria-live="polite">
            <?php foreach ($menuItems as $menu): ?>
                <?php
                $menuId = (int) $menu['id'];
                $price = (float) $menu['price'];
                $qty = (int) ($selectedQuantities[$menuId] ?? 0);
                $cat = $getMenuCategory((string) $menu['name']);
                $icon = $getMenuIcon((string) $menu['name']);
                $menuName = (string) $menu['name'];
                $menuDescription = (string) $menu['description'];
                ?>
                <article class="menu-card" data-category="<?= e($cat) ?>" data-name="<?= e(text_lower($menuName)) ?>" data-description="<?= e(text_lower($menuDescription)) ?>">
                    <div class="menu-card-visual menu-card-visual-<?= e($cat) ?>" aria-hidden="true">
                        <span class="menu-card-glow"></span>
                        <span class="menu-card-icon"><?= $icon ?></span>
                        <span class="menu-card-tag"><?= $cat === 'coffee' ? 'BREW' : ($cat === 'tea' ? 'FRESH' : 'BAKED') ?></span>
                    </div>
                    <div class="menu-card-content">
                        <div class="menu-card-copy">
                            <h2><?= e($menuName) ?></h2>
                            <p><?= e($menuDescription) ?></p>
                        </div>
                        <div class="menu-card-bottom">
                            <strong class="menu-price"><?= e(money($price)) ?> <small>฿</small></strong>
                            <div class="stepper" role="group" aria-label="จำนวนของ <?= e($menuName) ?>">
                                <button type="button" class="stepper-btn btn-decrease" aria-label="ลดจำนวน <?= e($menuName) ?>">−</button>
                                <input id="quantity-<?= $menuId ?>"
                                       class="stepper-input"
                                       name="quantities[<?= $menuId ?>]"
                                       type="number"
                                       min="0"
                                       max="99"
                                       step="1"
                                       value="<?= $qty ?>"
                                       data-price="<?= $price ?>"
                                       data-name="<?= e($menuName) ?>"
                                       aria-label="จำนวน <?= e($menuName) ?>">
                                <button type="button" class="stepper-btn btn-increase" aria-label="เพิ่มจำนวน <?= e($menuName) ?>">+</button>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="menu-no-results" id="menu-no-results" hidden>
            <span aria-hidden="true">⌕</span>
            <strong>ไม่พบเมนูที่ค้นหา</strong>
            <p>ลองใช้คำค้นอื่น หรือเลือกหมวดหมู่ทั้งหมด</p>
        </div>

        <section class="cart-bar floating-order-bar" id="floating-bar" aria-label="สรุปรายการสั่งซื้อ">
            <div class="cart-bar-info">
                <div class="cart-bar-count">
                    <span class="count-bubble" id="summary-count">0</span>
                    <div>
                        <strong>ตะกร้าของคุณ</strong>
                        <small id="summary-label">ยังไม่ได้เลือกเมนู</small>
                    </div>
                </div>
                <div class="cart-bar-total">
                    <small>ยอดรวม</small>
                    <strong id="summary-total">0.00 ฿</strong>
                </div>
            </div>
            <div class="cart-bar-actions">
                <details class="cart-sheet" id="cart-sheet">
                    <summary class="cart-sheet-trigger"><span aria-hidden="true">▤</span> ดูรายการ <span class="cart-sheet-count" id="cart-sheet-count">0</span></summary>
                    <div class="cart-sheet-panel">
                        <div class="cart-sheet-heading">
                            <div>
                                <span class="eyebrow">YOUR ORDER</span>
                                <h2>รายการที่เลือก</h2>
                            </div>
                            <span class="cart-sheet-points" id="summary-points">+0 แต้ม</span>
                        </div>
                        <div class="cart-items" id="cart-items" aria-live="polite">
                            <p class="cart-empty" id="cart-empty">ยังไม่มีรายการในตะกร้า</p>
                        </div>
                    </div>
                </details>
                <button class="button button-primary cart-submit" type="submit" id="submit-order-btn" disabled>
                    ตรวจสอบและส่งออเดอร์ <span aria-hidden="true">→</span>
                </button>
            </div>
        </section>
    </form>

    <script>
    (function() {
        const form = document.getElementById('menu-order-form');
        const inputs = Array.from(document.querySelectorAll('.stepper-input'));
        const summaryCount = document.getElementById('summary-count');
        const summaryTotal = document.getElementById('summary-total');
        const summaryPoints = document.getElementById('summary-points');
        const summaryLabel = document.getElementById('summary-label');
        const cartSheetCount = document.getElementById('cart-sheet-count');
        const cartItems = document.getElementById('cart-items');
        const cartEmpty = document.getElementById('cart-empty');
        const submitBtn = document.getElementById('submit-order-btn');
        const searchInput = document.getElementById('menu-search');
        const filterBtns = Array.from(document.querySelectorAll('.pill-btn'));
        const menuCards = Array.from(document.querySelectorAll('.menu-card'));
        const noResults = document.getElementById('menu-no-results');
        let activeFilter = 'all';

        function updateCartItems() {
            cartItems.querySelectorAll('.cart-item').forEach(item => item.remove());
            let hasItems = false;

            inputs.forEach(input => {
                const qty = parseInt(input.value, 10) || 0;
                if (qty < 1) return;

                hasItems = true;
                const item = document.createElement('div');
                item.className = 'cart-item';
                const itemName = document.createElement('strong');
                itemName.textContent = input.dataset.name || 'เมนู';
                const itemMeta = document.createElement('span');
                itemMeta.textContent = qty + ' × ' + Number(input.dataset.price || 0).toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' ฿';
                item.append(itemName, itemMeta);
                cartItems.appendChild(item);
            });

            cartEmpty.hidden = hasItems;
        }

        function updateSummary() {
            let totalQty = 0;
            let totalPrice = 0;

            inputs.forEach(input => {
                const qty = parseInt(input.value, 10) || 0;
                const price = parseFloat(input.dataset.price) || 0;
                if (qty > 0) {
                    totalQty += qty;
                    totalPrice += qty * price;
                }
            });

            const pointsEarned = Math.floor(totalPrice / 10);
            summaryCount.textContent = totalQty;
            cartSheetCount.textContent = totalQty;
            summaryTotal.textContent = totalPrice.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ฿';
            summaryPoints.textContent = '+' + pointsEarned + ' แต้ม';
            summaryLabel.textContent = totalQty === 0 ? 'ยังไม่ได้เลือกเมนู' : totalQty + ' รายการพร้อมสั่ง';
            submitBtn.disabled = totalQty === 0;
            updateCartItems();
        }

        function updateVisibleCards() {
            const query = (searchInput.value || '').trim().toLocaleLowerCase();
            let visibleCount = 0;

            menuCards.forEach(card => {
                const matchesFilter = activeFilter === 'all' || card.dataset.category === activeFilter;
                const searchable = (card.dataset.name || '') + ' ' + (card.dataset.description || '');
                const matchesSearch = query === '' || searchable.includes(query);
                const isVisible = matchesFilter && matchesSearch;
                card.hidden = !isVisible;
                if (isVisible) visibleCount++;
            });

            noResults.hidden = visibleCount > 0;
        }

        document.querySelectorAll('.stepper').forEach(stepper => {
            const input = stepper.querySelector('.stepper-input');
            const btnDec = stepper.querySelector('.btn-decrease');
            const btnInc = stepper.querySelector('.btn-increase');

            btnDec.addEventListener('click', () => {
                const current = parseInt(input.value, 10) || 0;
                input.value = Math.max(0, current - 1);
                updateSummary();
            });

            btnInc.addEventListener('click', () => {
                const current = parseInt(input.value, 10) || 0;
                input.value = Math.min(99, current + 1);
                updateSummary();
            });

            input.addEventListener('input', () => {
                let value = parseInt(input.value, 10);
                if (Number.isNaN(value) || value < 0) value = 0;
                input.value = Math.min(99, value);
                updateSummary();
            });
        });

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(item => {
                    item.classList.remove('active');
                    item.setAttribute('aria-pressed', 'false');
                });
                btn.classList.add('active');
                btn.setAttribute('aria-pressed', 'true');
                activeFilter = btn.dataset.filter || 'all';
                updateVisibleCards();
            });
        });

        searchInput.addEventListener('input', updateVisibleCards);

        form.addEventListener('submit', event => {
            const totalQty = inputs.reduce((sum, input) => sum + (parseInt(input.value, 10) || 0), 0);
            if (totalQty === 0) {
                event.preventDefault();
                submitBtn.focus();
            }
        });

        updateSummary();
        updateVisibleCards();
    })();
    </script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
