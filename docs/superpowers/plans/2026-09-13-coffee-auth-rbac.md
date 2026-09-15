# Authentication and Role-Based Access Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add secure login, registration, logout, session handling, and User/Admin route protection while preserving the existing member/rewards data.

**Architecture:** Keep the existing plain PHP + PDO application and add a small shared authentication boundary in `includes/auth.php`. The `users` table links one User account to one existing `members` row; Admin accounts are not linked to a member. `index.php` becomes a role-aware dispatcher, and the existing root CRUD is reachable only through Admin routes.

**Tech Stack:** PHP 8.1+, MySQL 5.7+/MariaDB, PDO MySQL, PHP sessions, `password_hash()`/`password_verify()`, HTML/CSS.

## Global Constraints

- User sees only their own member, order, and rewards data.
- Admin sees and manages all members, orders, menus, and rewards data.
- Passwords are stored only as `password_hash()` output.
- Every state-changing form uses a CSRF token and redirects after a successful POST.
- All user-controlled SQL values use PDO prepared statements.
- Existing `members` and `purchases` rows must survive the upgrade; do not use `DROP TABLE`.
- Demo credentials are for local workshop use only: `admin / admin123` and `user1 / user123`.
- This workspace is not a Git repository; use test/verification checkpoints instead of commit commands.

## File Map

- Create: `database_upgrade_auth.sql` — idempotent `users` table and required indexes/foreign key.
- Create: `includes/auth.php` — session user accessors, login/logout, and role guards.
- Modify: `includes/bootstrap.php` — secure session cookie options and auth include.
- Modify: `includes/functions.php` — username/password validation helpers and redirect helpers used by auth pages.
- Create: `login.php` — shared login form for both roles.
- Create: `register.php` — public User registration that creates `members` + `users` atomically.
- Create: `logout.php` — CSRF-protected POST logout.
- Create: `admin/index.php` — initial Admin dashboard shell.
- Create: `user/index.php` — initial User dashboard shell scoped to the logged-in member.
- Modify: `index.php` — role-aware dispatcher.
- Modify: `includes/header.php` — role-aware navigation and logout form.
- Create: `tests/auth_unit_test.php` — pure auth helper checks.
- Create: `tests/auth_repository_smoke.php` — login lookup and role/member linkage checks.
- Modify: `README.md` — login setup, demo credentials, and protected routes.

### Task 1: Define failing auth behavior tests

**Files:**
- Create: `tests/auth_unit_test.php`
- Test: `tests/auth_unit_test.php`

**Interfaces:**
- Consumes: `includes/functions.php` functions `normalize_username()`, `valid_username()`, `valid_password()` and `role_home()`.
- Produces: executable assertions that fix the exact auth validation contract for later tasks.

- [ ] **Step 1: Write the failing test**

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

$checks = 0;

function check(bool $condition, string $message): void
{
    global $checks;
    $checks++;
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "PASS: {$message}\n");
}

check(normalize_username('  User.One  ') === 'user.one', 'username is trimmed and lowercased');
check(valid_username('user.one_2'), 'valid username is accepted');
check(!valid_username('ab'), 'username shorter than three characters is rejected');
check(!valid_username('bad name'), 'username containing a space is rejected');
check(valid_password('secret123'), 'password with eight characters is accepted');
check(!valid_password('short'), 'password shorter than eight characters is rejected');
check(role_home('user') === 'user/index.php', 'User role goes to User dashboard');
check(role_home('admin') === 'admin/index.php', 'Admin role goes to Admin dashboard');
check(role_home('unknown') === 'login.php', 'unknown role goes to login');

fwrite(STDOUT, "Auth unit checks passed: {$checks}\n");
```

- [ ] **Step 2: Run the test to verify it fails**

Run:

```powershell
$php = 'C:\MAMP\bin\php\php8.3.1\php.exe'
$ext = 'C:\MAMP\bin\php\php8.3.1\ext'
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll tests/auth_unit_test.php
```

Expected: FAIL with `Call to undefined function normalize_username()`.

- [ ] **Step 3: Keep the test as the contract**

Do not weaken the test to make the current code pass. The implementation in Task 2 must satisfy all nine checks without database access.

### Task 2: Implement shared auth helpers and secure session bootstrap

**Files:**
- Modify: `includes/functions.php`
- Create: `includes/auth.php`
- Modify: `includes/bootstrap.php`

**Interfaces:**
- Consumes: the assertions in `tests/auth_unit_test.php` and the existing `e()`, `csrf_token()`, `verify_csrf()`, and `redirect()` helpers.
- Produces: `normalize_username(string): string`, `valid_username(string): bool`, `valid_password(string): bool`, `role_home(string): string`, `app_url(string): string`, `current_user(): ?array`, `login_user(PDO,string,string): bool`, `require_login(): void`, `require_role(string): void`, and `logout_user(): void`.

- [ ] **Step 1: Write the minimal helper implementation**

Append these exact pure helpers to `includes/functions.php`:

```php
function normalize_username(string $username): string
{
    return strtolower(trim($username));
}

function valid_username(string $username): bool
{
    return preg_match('/^[a-z0-9][a-z0-9._-]{2,39}$/', $username) === 1;
}

function valid_password(string $password): bool
{
    return strlen($password) >= 8 && strlen($password) <= 255;
}

function role_home(string $role): string
{
    return match ($role) {
        'admin' => 'admin/index.php',
        'user' => 'user/index.php',
        default => 'login.php',
    };
}

function app_url(string $path): string
{
    $configuredBase = trim((string) (getenv('COFFEE_APP_BASE') ?: ''), '/');
    $base = $configuredBase === '' ? '' : '/' . $configuredBase;
    return $base . '/' . ltrim($path, '/');
}
```

- [ ] **Step 2: Add the auth repository/guard implementation**

Create `includes/auth.php` with these exact interfaces and behavior:

```php
<?php
declare(strict_types=1);

function current_user(): ?array
{
    $user = $_SESSION['auth_user'] ?? null;
    return is_array($user) ? $user : null;
}

function login_user(PDO $pdo, string $username, string $password): bool
{
    $stmt = $pdo->prepare(
        'SELECT u.id, u.member_id, u.username, u.password_hash, u.role, u.status,
                m.member_no, m.name, m.phone
         FROM users u
         LEFT JOIN members m ON m.id = u.member_id
         WHERE u.username = :username
         LIMIT 1'
    );
    $stmt->execute(['username' => normalize_username($username)]);
    $user = $stmt->fetch();

    if ($user === false || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['auth_user'] = [
        'id' => (int) $user['id'],
        'member_id' => $user['member_id'] === null ? null : (int) $user['member_id'],
        'username' => $user['username'],
        'role' => $user['role'],
        'member_no' => $user['member_no'],
        'name' => $user['name'],
        'phone' => $user['phone'],
    ];

    $update = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
    $update->execute(['id' => (int) $user['id']]);
    return true;
}

function require_login(): void
{
    if (current_user() === null) {
        flash('error', 'กรุณาเข้าสู่ระบบก่อนใช้งาน');
        redirect(app_url('login.php'));
    }
}

function require_role(string $role): void
{
    require_login();
    $user = current_user();
    if ($user === null || $user['role'] !== $role) {
        http_response_code(403);
        exit('ไม่มีสิทธิ์เข้าถึงหน้านี้');
    }
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}
```

- [ ] **Step 3: Harden session startup before `session_start()`**

Replace the top of `includes/bootstrap.php` with this ordering so cookie flags are applied before the session begins:

```php
<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/auth.php';
```

- [ ] **Step 4: Run the unit test to verify it passes**

Run the command from Task 1. Expected: nine `PASS` lines and `Auth unit checks passed: 9`.

### Task 3: Add the users table and local demo accounts

**Files:**
- Create: `database_upgrade_auth.sql`
- Create: `scripts/seed_demo_accounts.php`
- Test: `tests/auth_repository_smoke.php`

**Interfaces:**
- Consumes: `users.member_id` linkage expected by `login_user()`.
- Produces: idempotent auth schema and two usable local accounts.

- [ ] **Step 1: Write the idempotent schema upgrade**

Create `database_upgrade_auth.sql`:

```sql
USE coffee_rewards;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id INT UNSIGNED NULL,
  username VARCHAR(40) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_member (member_id),
  KEY idx_users_role_status (role, status),
  CONSTRAINT fk_users_member FOREIGN KEY (member_id) REFERENCES members(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 2: Apply the schema without dropping existing data**

Run:

```powershell
$mysql = 'C:\MAMP\bin\mysql\bin\mysql.exe'
& $mysql -h 127.0.0.1 -P 3306 -u root -proot coffee_rewards < database_upgrade_auth.sql
```

Expected: command exits with code 0; existing `members` and `purchases` counts remain unchanged.

- [ ] **Step 3: Seed accounts through PHP password hashing**

Create `scripts/seed_demo_accounts.php`:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$pdo->beginTransaction();
try {
    $admin = $pdo->prepare(
        "INSERT INTO users (member_id, username, password_hash, role)
         VALUES (NULL, :username, :password_hash, 'admin')
         ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'admin', status = 'active'"
    );
    $admin->execute([
        'username' => 'admin',
        'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
    ]);

    $member = find_member_by_no($pdo, 'M0001');
    if ($member === null) {
        throw new RuntimeException('ต้องมีสมาชิก M0001 ก่อน seed user1');
    }
    $user = $pdo->prepare(
        "INSERT INTO users (member_id, username, password_hash, role)
         VALUES (:member_id, :username, :password_hash, 'user')
         ON DUPLICATE KEY UPDATE member_id = VALUES(member_id), password_hash = VALUES(password_hash), role = 'user', status = 'active'"
    );
    $user->execute([
        'member_id' => (int) $member['id'],
        'username' => 'user1',
        'password_hash' => password_hash('user123', PASSWORD_DEFAULT),
    ]);
    $pdo->commit();
    fwrite(STDOUT, "Demo accounts seeded.\n");
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}
```

Run:

```powershell
$php = 'C:\MAMP\bin\php\php8.3.1\php.exe'
$ext = 'C:\MAMP\bin\php\php8.3.1\ext'
$session = 'C:\Users\Chaya\verse\y4.1\wap\code\LAP7\tmp\sessions'
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll -d session.save_path=$session scripts/seed_demo_accounts.php
```

Expected: `Demo accounts seeded.` and no change to existing member/purchase counts.

- [ ] **Step 4: Test login lookup and role linkage**

Create `tests/auth_repository_smoke.php`:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (!login_user($pdo, 'ADMIN', 'admin123')) {
    throw new RuntimeException('admin login failed');
}
$admin = current_user();
if ($admin === null || $admin['role'] !== 'admin' || $admin['member_id'] !== null) {
    throw new RuntimeException('admin role/linkage is incorrect');
}
logout_user();

if (!login_user($pdo, 'USER1', 'user123')) {
    throw new RuntimeException('user login failed');
}
$user = current_user();
if ($user === null || $user['role'] !== 'user' || $user['member_id'] === null || $user['member_no'] !== 'M0001') {
    throw new RuntimeException('user role/linkage is incorrect');
}
logout_user();

if (login_user($pdo, 'user1', 'wrong-password')) {
    throw new RuntimeException('wrong password was accepted');
}
fwrite(STDOUT, "Auth repository smoke checks passed.\n");
```

Run with the session save path from Step 3. Expected: `Auth repository smoke checks passed.`

### Task 4: Build shared login/register/logout and role dashboards

**Files:**
- Create: `login.php`
- Create: `register.php`
- Create: `logout.php`
- Create: `admin/index.php`
- Create: `user/index.php`
- Modify: `index.php`
- Modify: `includes/header.php`
- Modify: `README.md`

**Interfaces:**
- Consumes: `login_user()`, `current_user()`, `require_role()`, `logout_user()`, `create_member()`, `find_member_by_no()`, and `csrf_token()`.
- Produces: working browser entry flow and protected role-specific dashboards for the ordering plan.

- [ ] **Step 1: Implement login form and POST flow**

Use `POST` only for credential submission. Validate CSRF, normalize username, call `login_user($pdo, $username, $password)`, set a generic failure message (`ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง`) and redirect to `app_url(role_home(current_user()['role']))` after success. Preserve the submitted username on failure and escape it in the form.

The form must contain:

```html
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<input id="username" name="username" autocomplete="username" required>
<input id="password" name="password" type="password" autocomplete="current-password" required>
```

- [ ] **Step 2: Implement atomic registration**

Validate username with `valid_username()`, password with `valid_password()`, matching confirmation, name, and phone. Check duplicate username before the transaction, but keep the database unique key as the final authority. Generate a collision-free member number from the just-created auto-increment id by inserting a random temporary number and replacing it inside the same transaction:

```php
$pdo->beginTransaction();
try {
    $temporaryNo = 'TEMP-' . bin2hex(random_bytes(12));
    $memberInsert = $pdo->prepare('INSERT INTO members (member_no, name, phone, joined_at) VALUES (:member_no, :name, :phone, :joined_at)');
    $memberInsert->execute([
        'member_no' => $temporaryNo,
        'name' => $name,
        'phone' => $phone,
        'joined_at' => date('Y-m-d'),
    ]);
    $memberId = (int) $pdo->lastInsertId();
    $memberNo = 'M' . str_pad((string) $memberId, 4, '0', STR_PAD_LEFT);
    $memberUpdate = $pdo->prepare('UPDATE members SET member_no = :member_no WHERE id = :id');
    $memberUpdate->execute(['member_no' => $memberNo, 'id' => $memberId]);

    $account = $pdo->prepare("INSERT INTO users (member_id, username, password_hash, role, status) VALUES (:member_id, :username, :password_hash, 'user', 'active')");
    $account->execute([
        'member_id' => $memberId,
        'username' => normalize_username($username),
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    $pdo->commit();
    flash('success', 'สมัครสมาชิกเรียบร้อยแล้ว กรุณาเข้าสู่ระบบ');
    redirect(app_url('login.php'));
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}
```

Catch duplicate-key `PDOException` to show a form error instead of a database page. On any other exception rethrow it. The form must never accept a client-supplied `member_id` or `member_no`.

- [ ] **Step 3: Implement logout and the two dashboard shells**

`logout.php` must accept only POST, verify CSRF, call `logout_user()`, start a fresh session if needed for the flash, and redirect to `app_url('login.php')`. `user/index.php` must call `require_role('user')`; `admin/index.php` must call `require_role('admin')`. Each page shows the current username, role, and a link to the next portal work.

- [ ] **Step 4: Make the root dispatcher role-aware**

Replace the old member-list body of `index.php` with:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user();
if ($user === null) {
    redirect(app_url('login.php'));
}
redirect(app_url(role_home((string) $user['role'])));
```

- [ ] **Step 5: Add role-aware navigation and nested-page URLs**

Update `includes/header.php` so unauthenticated pages show Login/Register, User pages show Dashboard/Menu/My Orders/Profile/Logout, and Admin pages show Dashboard/Members/Orders/Menu/Users/Logout. Build every stylesheet, navigation link, form action, and redirect target with `app_url('...')`; this keeps `/user` and `/admin` pages working both at the PHP built-in server root and under an Apache subdirectory configured by `COFFEE_APP_BASE`. Logout is a compact POST form with a CSRF hidden input, not a state-changing GET link.

- [ ] **Step 6: Run syntax and browser smoke tests**

Run:

```powershell
$php = 'C:\MAMP\bin\php\php8.3.1\php.exe'
$ext = 'C:\MAMP\bin\php\php8.3.1\ext'
Get-ChildItem -Recurse -Filter '*.php' | ForEach-Object { & $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll -l $_.FullName }
```

Expected: no syntax errors. In the browser verify login as `user1` lands on User Dashboard, login as `admin` lands on Admin Dashboard, logout returns to login, and an unauthenticated request to `admin/index.php` redirects to login.

### Task 5: Finish auth verification and handoff

**Files:**
- Modify: `tests/auth_unit_test.php` only if a discovered contract mismatch is fixed in implementation.
- Modify: `README.md`

- [ ] **Step 1: Run both auth test files**

Run the MAMP PHP commands for `tests/auth_unit_test.php` and `tests/auth_repository_smoke.php`. Expected: all PASS lines and no warnings/notices.

- [ ] **Step 2: Verify data preservation**

Run:

```powershell
$mysql = 'C:\MAMP\bin\mysql\bin\mysql.exe'
& $mysql -N -B -h 127.0.0.1 -P 3306 -u root -proot -e "SELECT COUNT(*) AS members FROM coffee_rewards.members; SELECT COUNT(*) AS purchases FROM coffee_rewards.purchases;"
```

Expected: the member and purchase counts are the same as before the auth upgrade, plus rows in `users` for `admin` and `user1`.

- [ ] **Step 3: Document the exact next-plan dependency**

README must say that `user/menu.php` and Admin order management are added by the next plans, while the current login/RBAC routes are already protected and testable.
