# User Ordering Portal Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give a logged-in User a private menu, cart/checkout flow, order history, order cancellation for pending orders, and profile page.

**Architecture:** Add menu and order repositories beside the existing repository, with all User queries scoped by `current_user()['id']` and `member_id`. Order creation re-reads active menu prices inside a transaction and stores item snapshots. This plan depends on the authentication/RBAC plan and leaves completion-to-points processing to the Admin plan.

**Tech Stack:** PHP 8.1+, MySQL 5.7+/MariaDB, PDO MySQL, server-rendered PHP, existing CSS and session/CSRF helpers.

## Global Constraints

- User sees only their own member, order, and rewards data.
- Only active menu items can be ordered.
- Prices, quantities, totals, and order ownership are validated on the server.
- Every state-changing form uses CSRF and redirects after a successful POST.
- Existing `members` and `purchases` data remains intact.
- An order starts as `pending`; points are created only when Admin marks it `completed` in the Admin plan.
- Build nested-page links, form actions, and redirects with `app_url('...')` so the app works at the PHP server root and under an Apache subdirectory.

## File Map

- Create: `database_upgrade_orders.sql` — `menu_items`, `orders`, and `order_items` tables plus menu seed.
- Create: `includes/order_repository.php` — User-scoped menu/order reads and order mutations.
- Create: `includes/order_helpers.php` — quantity parsing, status labels, and server total calculation.
- Modify: `includes/bootstrap.php` — include the new order files.
- Modify: `user/index.php` — show the real User dashboard.
- Create: `user/menu.php` — active menu and quantity form.
- Create: `user/order_create.php` — server-side checkout.
- Create: `user/orders.php` — private order list and status filter.
- Create: `user/order_detail.php` — private order detail and pending cancellation.
- Create: `user/profile.php` — private profile editing.
- Modify: `assets/style.css` — menu cards, quantity controls, status badges, and order summary.
- Create: `tests/order_unit_test.php` — pure order calculation/status tests.
- Create: `tests/order_repository_smoke.php` — database ownership and lifecycle smoke checks.

### Task 1: Define the order calculation contract with failing tests

**Files:**
- Create: `tests/order_unit_test.php`
- Test: `tests/order_unit_test.php`

**Interfaces:**
- Consumes: `parse_quantity()`, `calculate_order_total()`, `allowed_user_order_status()` and `order_status_label()` from `includes/order_helpers.php`.
- Produces: exact rules for valid quantities, server totals, and pending cancellation.

- [ ] **Step 1: Write the failing test**

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/order_helpers.php';

function order_check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "PASS: {$message}\n");
}

order_check(parse_quantity('1') === 1, 'quantity one is valid');
order_check(parse_quantity('99') === 99, 'quantity 99 is valid');
order_check(parse_quantity('0') === null, 'quantity zero is rejected');
order_check(parse_quantity('1.5') === null, 'decimal quantity is rejected');
order_check(parse_quantity('100') === null, 'quantity over 99 is rejected');

$items = [
    ['unit_price' => '65.00', 'quantity' => 2],
    ['unit_price' => '45.50', 'quantity' => 1],
];
order_check(calculate_order_total($items) === 175.50, 'order total is calculated from price snapshots');
order_check(allowed_user_order_status('pending'), 'pending can be cancelled by User');
order_check(!allowed_user_order_status('preparing'), 'preparing cannot be cancelled by User');
order_check(order_status_label('completed') === 'สำเร็จ', 'completed has a Thai label');

fwrite(STDOUT, "Order unit checks passed.\n");
```

- [ ] **Step 2: Run the failing test**

Run:

```powershell
$php = 'C:\MAMP\bin\php\php8.3.1\php.exe'
$ext = 'C:\MAMP\bin\php\php8.3.1\ext'
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll tests/order_unit_test.php
```

Expected: FAIL with an undefined function for `parse_quantity()`.

- [ ] **Step 3: Implement the pure helpers**

Create `includes/order_helpers.php`:

```php
<?php
declare(strict_types=1);

function parse_quantity(mixed $value): ?int
{
    if (!is_string($value) && !is_int($value)) {
        return null;
    }
    $text = trim((string) $value);
    if (!preg_match('/^[1-9][0-9]?$/', $text)) {
        return null;
    }
    $quantity = (int) $text;
    return $quantity >= 1 && $quantity <= 99 ? $quantity : null;
}

function calculate_order_total(array $items): float
{
    $total = 0.0;
    foreach ($items as $item) {
        $total += round((float) $item['unit_price'] * (int) $item['quantity'], 2);
    }
    return round($total, 2);
}

function allowed_user_order_status(string $status): bool
{
    return $status === 'pending';
}

function order_status_label(string $status): string
{
    return [
        'pending' => 'รอดำเนินการ',
        'preparing' => 'กำลังเตรียม',
        'ready' => 'พร้อมรับ',
        'completed' => 'สำเร็จ',
        'cancelled' => 'ยกเลิกแล้ว',
    ][$status] ?? 'ไม่ทราบสถานะ';
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run the command from Step 2. Expected: all PASS lines and `Order unit checks passed.`

### Task 2: Add the order schema and menu seed without dropping data

**Files:**
- Create: `database_upgrade_orders.sql`

**Interfaces:**
- Consumes: `users`, `members`, and `purchases` created by the auth plan and original Workshop.
- Produces: tables used by `includes/order_repository.php`.

- [ ] **Step 1: Create the schema**

Create `database_upgrade_orders.sql`:

```sql
USE coffee_rewards;

CREATE TABLE IF NOT EXISTS menu_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(255) NOT NULL DEFAULT '',
  price DECIMAL(10,2) NOT NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_menu_status_name (status, name),
  CONSTRAINT chk_menu_price_positive CHECK (price > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  member_id INT UNSIGNED NOT NULL,
  status ENUM('pending', 'preparing', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  total_amount DECIMAL(10,2) NOT NULL,
  purchase_id INT UNSIGNED NULL,
  completed_at DATETIME NULL,
  cancelled_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_orders_purchase (purchase_id),
  KEY idx_orders_user_created (user_id, created_at),
  KEY idx_orders_member_status (member_id, status),
  KEY idx_orders_status_created (status, created_at),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_orders_member FOREIGN KEY (member_id) REFERENCES members(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_orders_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_orders_total_positive CHECK (total_amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  menu_item_id INT UNSIGNED NOT NULL,
  item_name VARCHAR(120) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  quantity TINYINT UNSIGNED NOT NULL,
  line_total DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_order_items_order (order_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_order_items_menu FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_order_items_quantity CHECK (quantity BETWEEN 1 AND 99)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO menu_items (name, description, price, status)
SELECT seed.name, seed.description, seed.price, 'active'
FROM (
  SELECT 'อเมริกาโน่' AS name, 'กาแฟดำหอมเข้ม' AS description, 55.00 AS price
  UNION ALL SELECT 'ลาเต้', 'กาแฟนมเนื้อนุ่ม', 65.00
  UNION ALL SELECT 'คาปูชิโน่', 'กาแฟนมพร้อมฟองนม', 70.00
  UNION ALL SELECT 'ชาไทย', 'ชาไทยหอมหวาน', 50.00
  UNION ALL SELECT 'ครัวซองต์เนยสด', 'อบใหม่กรอบนอกนุ่มใน', 45.00
) seed
WHERE NOT EXISTS (SELECT 1 FROM menu_items LIMIT 1);
```

- [ ] **Step 2: Apply and verify the schema**

Run:

```powershell
$mysql = 'C:\MAMP\bin\mysql\bin\mysql.exe'
& $mysql -h 127.0.0.1 -P 3306 -u root -proot coffee_rewards < database_upgrade_orders.sql
& $mysql -N -B -h 127.0.0.1 -P 3306 -u root -proot -e "SELECT COUNT(*) FROM coffee_rewards.menu_items;"
```

Expected: command succeeds and the menu count is at least 5; existing members/purchases remain.

### Task 3: Implement User-scoped menu/order repository

**Files:**
- Create: `includes/order_repository.php`
- Modify: `includes/bootstrap.php`
- Test: `tests/order_repository_smoke.php`

**Interfaces:**
- Consumes: `$pdo`, `current_user()`, `calculate_order_total()`, and the schema from Task 2.
- Produces: `active_menu_items(PDO): array`, `find_order_for_user(PDO,int,int): ?array`, `find_order_items(PDO,int): array`, `find_user_orders(PDO,int,?string): array`, `create_user_order(PDO,int,int,array): int`, `cancel_user_order(PDO,int,int): bool`, and `update_member_profile(PDO,int,string,string): bool`.

- [ ] **Step 1: Add the repository interfaces**

Implement `active_menu_items()` with `WHERE status = 'active'`. Implement `find_order_for_user()` with both `o.id = :order_id` and `o.user_id = :user_id`; never fetch by id alone. Implement `find_user_orders()` with `o.user_id = :user_id` and an optional validated status filter.

- [ ] **Step 2: Implement server-authoritative order creation**

`create_user_order(PDO $pdo, int $userId, int $memberId, array $requestedQuantities): int` must:

```php
$pdo->beginTransaction();
try {
    $ids = array_map('intval', array_keys($requestedQuantities));
    if ($ids === []) {
        throw new InvalidArgumentException('ต้องเลือกเมนูอย่างน้อยหนึ่งรายการ');
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, name, price FROM menu_items WHERE status = 'active' AND id IN ({$placeholders}) FOR UPDATE");
    $stmt->execute($ids);
    $menuById = [];
    foreach ($stmt->fetchAll() as $menu) {
        $menuById[(int) $menu['id']] = $menu;
    }
    if (count($menuById) !== count($ids)) {
        throw new InvalidArgumentException('มีเมนูบางรายการปิดการขายแล้ว');
    }
    $items = [];
    foreach ($requestedQuantities as $menuId => $rawQuantity) {
        $quantity = parse_quantity($rawQuantity);
        if ($quantity === null || !isset($menuById[(int) $menuId])) {
            throw new InvalidArgumentException('จำนวนหรือเมนูไม่ถูกต้อง');
        }
        $items[] = [
            'menu_item_id' => (int) $menuId,
            'item_name' => $menuById[(int) $menuId]['name'],
            'unit_price' => (float) $menuById[(int) $menuId]['price'],
            'quantity' => $quantity,
            'line_total' => round((float) $menuById[(int) $menuId]['price'] * $quantity, 2),
        ];
    }
    $total = calculate_order_total($items);
    $order = $pdo->prepare('INSERT INTO orders (user_id, member_id, status, total_amount) VALUES (:user_id, :member_id, \'pending\', :total_amount)');
    $order->execute(['user_id' => $userId, 'member_id' => $memberId, 'total_amount' => $total]);
    $orderId = (int) $pdo->lastInsertId();
    $item = $pdo->prepare('INSERT INTO order_items (order_id, menu_item_id, item_name, unit_price, quantity, line_total) VALUES (:order_id, :menu_item_id, :item_name, :unit_price, :quantity, :line_total)');
    foreach ($items as $row) {
        $item->execute(['order_id' => $orderId] + $row);
    }
    $pdo->commit();
    return $orderId;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}
```

- [ ] **Step 3: Implement ownership-safe cancellation and profile update**

Use one update condition for cancellation: `id = :order_id AND user_id = :user_id AND status = 'pending'`. Return `rowCount() === 1`. Profile update must target `members.id = :member_id` from the session and update only `name` and `phone`.

- [ ] **Step 4: Include the repository and run database smoke checks**

Add `require_once __DIR__ . '/order_helpers.php';` and `require_once __DIR__ . '/order_repository.php';` to `includes/bootstrap.php`. The smoke test must create an order for `user1`, verify the order has `user_id` for user1 and `status = pending`, verify a lookup with a different user id returns null, cancel once successfully, reject the second cancel, and delete only the test order/items by its captured id.

### Task 4: Build the User Portal pages

**Files:**
- Modify: `user/index.php`
- Create: `user/menu.php`
- Create: `user/order_create.php`
- Create: `user/orders.php`
- Create: `user/order_detail.php`
- Create: `user/profile.php`
- Modify: `assets/style.css`

**Interfaces:**
- Consumes: `require_role('user')`, `current_user()`, all repository functions from Task 3, and existing view helpers `e()`, `money()`, `thai_datetime()`, `flash()`, `pull_flash()`.
- Produces: complete private User ordering flow.

- [ ] **Step 1: Render active menu with quantity inputs**

`user/menu.php` calls `require_role('user')`, loads `active_menu_items($pdo)`, and submits `quantities[<menu_id>]` to `user/order_create.php` with a CSRF token. Use a quantity input with `min="0" max="99" step="1"`; zero means not selected. Do not send prices as trusted form values.

- [ ] **Step 2: Implement checkout validation and PRG**

`user/order_create.php` accepts only POST, verifies CSRF, removes zero/blank quantities, validates each quantity with `parse_quantity()`, and calls `create_user_order()` using the current session user id/member id. On success flash the order number and redirect to `app_url('user/order_detail.php?id=' . $id)`. On `InvalidArgumentException`, show the message and preserve the submitted quantities.

- [ ] **Step 3: Implement private order list/detail/cancel**

`user/orders.php` filters only `pending|preparing|ready|completed|cancelled`. `user/order_detail.php` fetches using `find_order_for_user($pdo, $orderId, (int) current_user()['id'])`; a missing result returns 404. The cancel form appears only for `pending`, includes CSRF and a confirmation dialog, and posts back to the same order id. No User page may call an Admin-wide order query.

- [ ] **Step 4: Implement profile and dashboard**

`user/profile.php` displays username/member number read-only and updates only name/phone after validation. `user/index.php` loads the current member with `find_member()` using the session member id, displays total active points and level, and shows the latest five private orders.

- [ ] **Step 5: Add visual states and run browser checks**

Add menu cards, order summary, status badges, responsive one-column layout under 760px, and disabled/empty states to `assets/style.css`. Browser checks:

```text
1. Login as user1.
2. Open Menu, choose Americano x2, submit, and confirm a pending order appears.
3. Open My Orders and confirm only user1's order appears.
4. Change the order id to a different existing order id and confirm 404/denied.
5. Cancel the pending order and confirm the list shows cancelled.
6. Edit name/phone in Profile and confirm the member number and points are unchanged.
```

### Task 5: Finish User Portal verification and handoff

**Files:**
- Modify: `README.md`

- [ ] **Step 1: Run unit, repository, and syntax tests**

Run `tests/order_unit_test.php`, `tests/order_repository_smoke.php`, and PHP lint over all PHP files. Expected: all tests pass and no syntax errors.

- [ ] **Step 2: Verify cancelled orders do not affect points**

Query the test member's active purchase sum before and after creating/cancelling a pending order. Expected: the sum is unchanged because completion-to-purchase is intentionally implemented in the Admin plan.

- [ ] **Step 3: Document the dependency on Admin completion**

README must state that a User order remains pending until Admin changes its status, and points are awarded only during Admin completion.
