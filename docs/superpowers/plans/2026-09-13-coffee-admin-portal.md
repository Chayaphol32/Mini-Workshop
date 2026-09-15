# Admin Portal and Rewards Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give Admin a complete all-data dashboard for members, users, menus, orders, status changes, manual purchases, and one-time points awarding when an order completes.

**Architecture:** Add Admin-scoped repositories and pages under `admin/`, then turn the old root Workshop pages into protected compatibility redirects. A single transaction owns each order status transition: completing an order creates exactly one active `purchases` row and records its id; cancelling a pre-completion order creates no points. Existing manual purchases continue to use the original cancellation rule.

**Tech Stack:** PHP 8.1+, MySQL 5.7+/MariaDB, PDO MySQL, existing role guards, server-rendered PHP, existing CSS.

## Global Constraints

- Admin can see all members, orders, menus, users, and active/cancelled purchases.
- User cannot access Admin pages or Admin-wide queries.
- Order prices and points are calculated on the server.
- Completing an order is idempotent: one order creates at most one purchase.
- `85.00` earns `8` points and `125.00` earns `12` points; cancelled purchases do not count.
- Every mutation uses CSRF, prepared statements, validation, and POST-redirect-GET.
- Never drop existing tables or delete unrelated user data during migration/testing.
- Build nested-page links, form actions, and redirects with `app_url('...')` so the app works at the PHP server root and under an Apache subdirectory.

## File Map

- Create: `includes/admin_repository.php` — Admin-wide members/users/menu/orders/status queries.
- Create: `includes/order_status.php` — transition validation and reward eligibility used by Admin; status labels remain in the shared `order_helpers.php` from the User plan.
- Create: `admin/index.php` — Admin metrics dashboard.
- Create: `admin/members.php` — member search/list.
- Create: `admin/member_create.php` — protected version of Workshop create.
- Create: `admin/member_edit.php` — protected version of Workshop edit.
- Create: `admin/member_detail.php` — all-history member detail and manual purchase cancellation.
- Create: `admin/purchase.php` — protected version of Workshop purchase entry.
- Create: `admin/orders.php` — all-order search/filter.
- Create: `admin/order_detail.php` — customer/order detail and status transition form.
- Create: `admin/menu.php`, `admin/menu_create.php`, `admin/menu_edit.php` — menu CRUD/status.
- Create: `admin/users.php` — account list and User enable/disable.
- Modify: `index.php` and root Workshop pages — role-aware compatibility redirects.
- Modify: `assets/style.css` — Admin tables, filters, and status controls.
- Create: `tests/admin_order_unit_test.php` — status transition and point eligibility tests.
- Create: `tests/admin_repository_smoke.php` — completion/cancellation idempotency checks.
- Modify: `README.md` — final route map and Admin workflow.

### Task 1: Define status and reward contracts with failing tests

**Files:**
- Create: `tests/admin_order_unit_test.php`
- Test: `tests/admin_order_unit_test.php`

**Interfaces:**
- Consumes: `can_transition_order(string,string): bool` and `status_can_earn_points(string): bool` from `includes/order_status.php`, plus the shared `order_status_label(string): string` from `includes/order_helpers.php`.
- Produces: exact allowed transitions and reward eligibility.

- [ ] **Step 1: Write the failing test**

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/order_helpers.php';
require_once __DIR__ . '/../includes/order_status.php';

function admin_order_check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "PASS: {$message}\n");
}

admin_order_check(can_transition_order('pending', 'preparing'), 'pending can move to preparing');
admin_order_check(can_transition_order('preparing', 'ready'), 'preparing can move to ready');
admin_order_check(can_transition_order('ready', 'completed'), 'ready can move to completed');
admin_order_check(can_transition_order('pending', 'cancelled'), 'pending can be cancelled');
admin_order_check(can_transition_order('preparing', 'cancelled'), 'preparing can be cancelled');
admin_order_check(!can_transition_order('completed', 'preparing'), 'completed cannot move backward');
admin_order_check(!can_transition_order('cancelled', 'pending'), 'cancelled cannot be reopened');
admin_order_check(status_can_earn_points('completed'), 'completed earns points');
admin_order_check(!status_can_earn_points('cancelled'), 'cancelled does not earn points');
admin_order_check(order_status_label('ready') === 'พร้อมรับ', 'ready has a Thai label');

fwrite(STDOUT, "Admin order unit checks passed.\n");
```

- [ ] **Step 2: Run the failing test**

Run:

```powershell
$php = 'C:\MAMP\bin\php\php8.3.1\php.exe'
$ext = 'C:\MAMP\bin\php\php8.3.1\ext'
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll tests/admin_order_unit_test.php
```

Expected: FAIL with an undefined function for `can_transition_order()`.

- [ ] **Step 3: Implement the exact transition table**

Create `includes/order_status.php`:

```php
<?php
declare(strict_types=1);

function can_transition_order(string $from, string $to): bool
{
    return in_array($to, [
        'pending' => ['preparing', 'cancelled'],
        'preparing' => ['ready', 'cancelled'],
        'ready' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ][$from] ?? [], true);
}

function status_can_earn_points(string $status): bool
{
    return $status === 'completed';
}

```

- [ ] **Step 4: Run the test to verify it passes**

Expected: all transition/reward checks pass.

### Task 2: Implement Admin repository and idempotent completion transaction

**Files:**
- Create: `includes/admin_repository.php`
- Modify: `includes/bootstrap.php`
- Test: `tests/admin_repository_smoke.php`

**Interfaces:**
- Consumes: `$pdo`, `can_transition_order()`, `calculate_points()`, `find_member()`, `create_purchase()`, and `cancel_purchase()`.
- Produces: `admin_order_list(PDO,string,?string): array`, `admin_find_order(PDO,int): ?array`, `admin_find_order_items(PDO,int): array`, `admin_update_order_status(PDO,int,string): bool`, `admin_set_user_status(PDO,int,string): bool`, and Admin menu/member query functions.

- [ ] **Step 1: Add Admin-wide read queries**

`admin_order_list()` joins `orders`, `members`, and `users`, supports a search pattern against order id, member number, member name, and username, and accepts only the five known statuses. `admin_find_order()` must return customer/member fields and `purchase_id`; `admin_find_order_items()` returns item snapshots ordered by item id.

- [ ] **Step 2: Implement the status transition transaction**

Implement `admin_update_order_status(PDO $pdo, int $orderId, string $nextStatus): bool` with this transaction shape:

```php
$pdo->beginTransaction();
try {
    $lock = $pdo->prepare('SELECT id, member_id, status, total_amount, purchase_id FROM orders WHERE id = :id FOR UPDATE');
    $lock->execute(['id' => $orderId]);
    $order = $lock->fetch();
    if ($order === false || !can_transition_order((string) $order['status'], $nextStatus)) {
        $pdo->rollBack();
        return false;
    }

    $purchaseId = $order['purchase_id'] === null ? null : (int) $order['purchase_id'];
    if ($nextStatus === 'completed') {
        if ($purchaseId === null) {
            $points = calculate_points((float) $order['total_amount']);
            $purchase = $pdo->prepare('INSERT INTO purchases (member_id, amount, points, status) VALUES (:member_id, :amount, :points, \'active\')');
            $purchase->execute([
                'member_id' => (int) $order['member_id'],
                'amount' => $order['total_amount'],
                'points' => $points,
            ]);
            $purchaseId = (int) $pdo->lastInsertId();
        }
        $update = $pdo->prepare("UPDATE orders SET status = 'completed', purchase_id = :purchase_id, completed_at = NOW() WHERE id = :id AND status = :from_status");
        $update->execute(['purchase_id' => $purchaseId, 'id' => $orderId, 'from_status' => $order['status']]);
    } elseif ($nextStatus === 'cancelled') {
        $update = $pdo->prepare("UPDATE orders SET status = 'cancelled', cancelled_at = NOW() WHERE id = :id AND status = :from_status");
        $update->execute(['id' => $orderId, 'from_status' => $order['status']]);
    } else {
        $update = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id AND status = :from_status');
        $update->execute(['status' => $nextStatus, 'id' => $orderId, 'from_status' => $order['status']]);
    }

    if ($update->rowCount() !== 1) {
        $pdo->rollBack();
        return false;
    }
    $pdo->commit();
    return true;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}
```

The second attempt to complete an already completed order returns false and does not create another purchase. A completed order cannot be cancelled through the order status form; its linked purchase remains subject to the original Admin-only purchase cancellation workflow.

- [ ] **Step 3: Implement Admin menu/user/member helpers**

Use prepared statements for `admin_list_users()`, `admin_set_user_status()` (allow only `active|disabled` and only target `role = 'user'`), `admin_list_menu_items()`, `admin_create_menu_item()`, `admin_update_menu_item()`, and the existing member CRUD functions. Menu updates must validate price > 0 and preserve existing order item snapshots.

- [ ] **Step 4: Run the repository smoke test**

The smoke test must create one test order for user1 using the User repository, advance it pending→preparing→ready→completed, assert one linked purchase with `points = floor(total/10)`, call completion again and assert false with purchase count unchanged, then delete only the captured test order and purchase after assertions. Also test pending→cancelled and assert no purchase id.

### Task 3: Build Admin dashboard and all-data order management

**Files:**
- Create: `admin/index.php`
- Create: `admin/orders.php`
- Create: `admin/order_detail.php`
- Modify: `assets/style.css`

**Interfaces:**
- Consumes: `require_role('admin')`, Admin repository functions, shared `order_status_label()`, `money()`, `thai_datetime()`, `e()`, `csrf_token()`, and `flash()`.
- Produces: Admin-visible order queue and safe status updates.

- [ ] **Step 1: Add Admin metrics**

`admin/index.php` must query total members, pending/preparing/ready order count, completed sales total, and active points using aggregate SQL. The queries must not use the User session member id; the page is intentionally Admin-wide.

- [ ] **Step 2: Add order list/search/filter**

`admin/orders.php` accepts GET `q` and `status`, validates status against the known list, calls `admin_order_list()`, and renders customer name/member number, order number, total, created time, and status. Empty and invalid-filter states are explicit.

- [ ] **Step 3: Add order detail and status mutation**

`admin/order_detail.php?id=...` validates the id, loads order/items, and on POST verifies CSRF, validates the requested next status against `can_transition_order()`, calls `admin_update_order_status()`, flashes success/failure, and redirects back. The form offers only legal next statuses for the current state; server validation remains authoritative.

- [ ] **Step 4: Run the Admin browser flow**

```text
1. Login as admin.
2. Confirm dashboard shows all members and the order queue.
3. Open a pending User order and move it to preparing, ready, then completed.
4. Open the member detail and confirm exactly one purchase and the correct points.
5. Repeat the completed POST and confirm no duplicate purchase.
6. Confirm a User session cannot access admin/orders.php (403 or role redirect).
```

### Task 4: Move the original Workshop CRUD under Admin

**Files:**
- Create: `admin/members.php`
- Create: `admin/member_create.php`
- Create: `admin/member_edit.php`
- Create: `admin/member_detail.php`
- Create: `admin/purchase.php`
- Modify: `includes/repository.php` only for reusable Admin queries that do not change existing contracts.

**Interfaces:**
- Consumes: existing member/purchase repository functions and Admin guard.
- Produces: protected versions of every original Workshop operation.

- [ ] **Step 1: Move member list/search and detail views**

Copy the existing search/detail behavior into Admin paths, add `require_role('admin')` at the top of every file, and keep the original search rules: member number/name partial search, duplicate names show number and phone, active purchases only count.

- [ ] **Step 2: Move create/edit/purchase/cancel POST flows**

Keep the existing server validations, CSRF checks, prepared statements, confirmation before purchase cancellation, transaction, and POST-redirect-GET behavior. Every mutation must be available only under Admin paths.

- [ ] **Step 3: Add Admin member/user account controls**

On `admin/member_detail.php`, show whether the member has a User account and link to `admin/users.php`. On `admin/users.php`, allow only an Admin to enable/disable User accounts; do not expose password hashes. If a member has no account, provide a form to create a User username/password using `password_hash()` and the member id.

- [ ] **Step 4: Verify the original Workshop rules still pass**

Run the original function/repository tests plus browser checks for 85→8, 125→12, duplicate member number rejection, manual purchase, one-time cancellation, and cancelled purchase excluded from total.

### Task 5: Build Admin menu management

**Files:**
- Create: `admin/menu.php`
- Create: `admin/menu_create.php`
- Create: `admin/menu_edit.php`

**Interfaces:**
- Consumes: menu repository helpers from Task 2 and Admin guard.
- Produces: complete active/inactive menu CRUD for Admin.

- [ ] **Step 1: Add menu list with status filter**

Render name, description, price, status, created/updated dates, and edit links. Include an explicit inactive state; inactive items remain visible to Admin but not to User.

- [ ] **Step 2: Add create/edit POST forms**

Validate name 1–120 characters, description max 255, price as a positive two-decimal amount, and status in `active|inactive`. Use CSRF and redirect after success. Never update `order_items.item_name` or `unit_price` when a menu is edited.

- [ ] **Step 3: Verify User/Admin separation**

As User, confirm inactive menu is absent and direct POST with its id is rejected. As Admin, confirm the item is visible and editable.

### Task 6: Complete redirects, styling, documentation, and full verification

**Files:**
- Modify: `index.php`
- Modify: root `member_create.php`, `member_edit.php`, `member_detail.php`, `purchase.php`
- Modify: `includes/header.php`
- Modify: `assets/style.css`
- Modify: `README.md`
- Create: `tests/full_smoke.php`

- [ ] **Step 1: Replace unprotected root pages with compatibility redirects**

Each old root Workshop page must load bootstrap, inspect the current role, and redirect with `app_url('...')` to its `admin/...` equivalent for Admin or `app_url('login.php')` for unauthenticated/User access. There must be no duplicate mutation logic left in an unguarded root page.

- [ ] **Step 2: Finish navigation and responsive Admin styles**

Make navigation role-aware, add tables/filter bars/status badges/confirmation styles, and preserve the existing warm coffee theme. Verify at 360px-equivalent narrow layout that tables scroll rather than overflow the viewport.

- [ ] **Step 3: Write the end-to-end smoke test**

`tests/full_smoke.php` must assert: demo admin/user login, User member scoping, active menu visibility, order creation, legal status transitions, one purchase on completion, no points on cancellation, and existing manual purchase totals. Test rows are created with a unique `QA` member/order prefix and removed by exact captured ids in a `finally` block.

- [ ] **Step 4: Run final verification**

Run:

```powershell
$php = 'C:\MAMP\bin\php\php8.3.1\php.exe'
$ext = 'C:\MAMP\bin\php\php8.3.1\ext'
$session = 'C:\Users\Chaya\verse\y4.1\wap\code\LAP7\tmp\sessions'
Get-ChildItem -Recurse -Filter '*.php' | ForEach-Object { & $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll -l $_.FullName }
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll tests/functions_test.php
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll -d session.save_path=$session tests/repository_smoke.php
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll tests/auth_unit_test.php
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll -d session.save_path=$session tests/auth_repository_smoke.php
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll tests/order_unit_test.php
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll -d session.save_path=$session tests/order_repository_smoke.php
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll tests/admin_order_unit_test.php
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll -d session.save_path=$session tests/admin_repository_smoke.php
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll -d session.save_path=$session tests/full_smoke.php
```

Expected: every PHP file reports no syntax errors and every test exits successfully.

- [ ] **Step 5: Run final browser acceptance matrix**

```text
Unauthenticated: root→login; Admin URL denied.
User: login→User dashboard; own profile/orders only; can order active menu; pending cancellation works.
Admin: login→Admin dashboard; sees all users/members/orders; menu CRUD works; order lifecycle works; completed order awards once.
Security: wrong password rejected; disabled User rejected; CSRF failure rejected; guessed foreign order/member id denied.
Regression: existing member search, duplicate-name display, manual purchase, 85/125 points, and one-time cancellation still work.
```

- [ ] **Step 6: Update README with final usage**

Document URLs, local demo credentials, role capabilities, database upgrade order (`database_upgrade_auth.sql` then `database_upgrade_orders.sql`), how to run tests, and the fact that credentials are workshop-only.
