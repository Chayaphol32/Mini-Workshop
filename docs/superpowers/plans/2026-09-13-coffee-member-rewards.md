# Coffee Member Rewards Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** สร้างเว็บ Mini Workshop ระบบสะสมแต้มสมาชิกสำหรับร้านกาแฟด้วย PHP + MySQL ให้ครบฟังก์ชันตาม Lab 7 พร้อม validation, responsive UI และกรณีทดสอบจาก PDF

**Architecture:** ใช้ Plain PHP แบบหลายหน้า โดยให้แต่ละหน้าเป็นจุดรับผิดชอบของ use case หนึ่งชุด ใช้ `config/database.php` สำหรับ PDO, `includes/repository.php` สำหรับ query ที่มีชื่อชัดเจน, `includes/functions.php` สำหรับ helper ที่ทดสอบได้ และ `includes/header.php`/`footer.php` สำหรับ layout ร่วมกัน ทุก mutation ใช้ POST + CSRF + PRG ส่วนการค้นหาและการเปิดรายการใช้ GET

**Tech Stack:** PHP 8.1+, MySQL 8/MariaDB, PDO, HTML5, CSS3, JavaScript `confirm()`, UTF-8/`utf8mb4`

## Global Constraints

- ใช้ GET สำหรับค้นหาและเปิดรายการ และใช้ POST สำหรับเพิ่ม แก้ไข หรือยกเลิก
- ใช้ PDO และ Prepared Statement กับค่าที่รับจากผู้ใช้ทุกจุด
- คำนวณแต้มฝั่ง PHP ด้วย `floor(amount / 10)`; 85 บาทต้องได้ 8 แต้ม และ 125 บาทต้องได้ 12 แต้ม
- แต้มรวมคำนวณจากรายการซื้อที่มีสถานะ `active` เท่านั้น; รายการ `cancelled` ไม่นับแต้ม
- ระดับใช้แต้มรวมปัจจุบัน: 0–999 Member, 1,000–4,999 Silver, 5,000–9,999 Gold, ตั้งแต่ 10,000 Platinum
- เลขสมาชิกห้ามซ้ำและห้ามแก้ผ่านหน้าแก้ไข; หน้าแก้ไขแก้ได้เฉพาะชื่อและเบอร์โทร
- ยอดซื้อต้องเป็นตัวเลขมากกว่า 0 และรายการเดิมยกเลิกได้เพียงครั้งเดียว
- หลังเพิ่ม แก้ไข หรือยกเลิกต้อง Redirect เพื่อลดการส่ง POST ซ้ำเมื่อ Refresh
- ต้องรองรับภาษาไทยด้วย UTF-8, แสดงข้อความเมื่อไม่พบสมาชิก/ประวัติ และแสดงเลขสมาชิกกับเบอร์โทรเมื่อชื่อซ้ำ
- ไม่ทำ hard-delete สมาชิก เพราะต้องรักษาประวัติการซื้อ; การลบของ Mini Workshop คือการยกเลิกรายการซื้อด้วยสถานะ `cancelled`
- ไม่เพิ่ม framework หรือบริการภายนอก เพื่อให้รันบน XAMPP ได้ทันที

## File Map

- Create `coffee_db.sql`: สร้างฐานข้อมูล ตาราง foreign key index และข้อมูลตัวอย่างสำหรับสาธิต
- Create `config/database.php`: สร้าง PDO connection ด้วย `utf8mb4`, exception mode และ emulated prepares ปิด
- Create `includes/bootstrap.php`: โหลด connection/helper และเปิด session เพียงครั้งเดียว
- Create `includes/functions.php`: helper บริสุทธิ์และ helper สำหรับ CSRF, flash, redirect, escape, validation, แต้ม และระดับ
- Create `includes/repository.php`: query สำหรับ members และ purchases โดยรับ `PDO` เป็น dependency
- Create `includes/header.php`: document head, navigation, flash message และเปิด main content
- Create `includes/footer.php`: ปิด main/document และโหลด script เล็กน้อย
- Create `assets/style.css`: coffee theme, cards, tables, forms, badges และ responsive breakpoints
- Create `index.php`: ค้นหาและแสดงรายชื่อสมาชิกพร้อมแต้มรวม/ระดับ
- Create `member_create.php`: สมัครสมาชิก
- Create `member_edit.php`: แก้ชื่อและเบอร์โทร
- Create `purchase.php`: ค้นหา เลือกสมาชิก และบันทึกยอดซื้อ
- Create `member_detail.php`: รายละเอียดสมาชิก ประวัติซื้อ และยกเลิกรายการ
- Create `tests/functions_test.php`: executable unit checks สำหรับกติกาแต้ม ระดับ และ validation
- Create `tests/repository_smoke.php`: ตรวจ query สมาชิกและ aggregate แต้มกับฐานข้อมูลจริง
- Create `README.md`: วิธีติดตั้ง import ฐานข้อมูล ตั้งค่า connection และสาธิตกรณีทดสอบ

### Task 1: Bootstrap ฐานข้อมูลและการเชื่อมต่อ

**Files:**
- Create: `coffee_db.sql`
- Create: `config/database.php`
- Create: `includes/bootstrap.php`

**Interfaces:**
- Produces `PDO $pdo` from `config/database.php`
- Produces `require_once __DIR__ . '/../config/database.php'` and session initialization from `includes/bootstrap.php`
- Later pages include `includes/bootstrap.php` before calling repository/helper functions

- [ ] **Step 1: Write the database schema first**

Create `coffee_db.sql` with this exact schema. The two tables preserve the purchase history while making active points easy to aggregate.

```sql
CREATE DATABASE IF NOT EXISTS coffee_rewards
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE coffee_rewards;

DROP TABLE IF EXISTS purchases;
DROP TABLE IF EXISTS members;

CREATE TABLE members (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_no VARCHAR(20) NOT NULL,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  joined_at DATE NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_members_member_no (member_no),
  KEY idx_members_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchases (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id INT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  points INT UNSIGNED NOT NULL,
  status ENUM('active', 'cancelled') NOT NULL DEFAULT 'active',
  purchased_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cancelled_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_purchases_member_status (member_id, status),
  CONSTRAINT fk_purchases_member
    FOREIGN KEY (member_id) REFERENCES members(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT chk_purchases_amount_positive CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO members (member_no, name, phone, joined_at) VALUES
  ('M0001', 'สมชาย ใจดี', '0812345678', '2026-09-01'),
  ('M0002', 'สุดา ใจดี', '0898765432', '2026-09-02'),
  ('M0003', 'สมชาย ใจดี', '0861112222', '2026-09-03');

INSERT INTO purchases (member_id, amount, points, status, purchased_at) VALUES
  (1, 125.00, 12, 'active', '2026-09-10 09:30:00'),
  (1, 85.00, 8, 'active', '2026-09-11 14:15:00'),
  (2, 50.00, 5, 'cancelled', '2026-09-11 10:00:00');
```

- [ ] **Step 2: Verify the schema on MySQL**

Run:

```powershell
mysql -u root -p < coffee_db.sql
mysql -u root -p -e "USE coffee_rewards; SHOW TABLES; SELECT COUNT(*) AS members FROM members; SELECT COUNT(*) AS purchases FROM purchases;"
```

Expected: tables `members` and `purchases` exist, with 3 members and 3 purchases. If the local MySQL client is unavailable, import the file through phpMyAdmin and run the same two SELECT checks there.

- [ ] **Step 3: Implement the PDO connection**

Create `config/database.php` (the local MAMP defaults are root/root; environment variables can override them):

```php
<?php
declare(strict_types=1);

$host = getenv('COFFEE_DB_HOST') ?: '127.0.0.1';
$port = getenv('COFFEE_DB_PORT') ?: '3306';
$database = getenv('COFFEE_DB_NAME') ?: 'coffee_rewards';
$username = getenv('COFFEE_DB_USER') ?: 'root';
$password = getenv('COFFEE_DB_PASSWORD') ?: 'root';
$dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
```

- [ ] **Step 4: Add the shared bootstrap**

Create `includes/bootstrap.php`:

```php
<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/repository.php';
```

- [ ] **Step 5: Verify the bootstrap**

Run:

```powershell
php -r "require 'includes/bootstrap.php'; echo get_class($pdo), PHP_EOL;"
```

Expected: `PDO`. Do not continue to page work until the connection succeeds.

### Task 2: Core rules, validation และ test harness

**Files:**
- Create: `includes/functions.php`
- Create: `tests/functions_test.php`

**Interfaces:**
- Produces `calculate_points(float $amount): int`
- Produces `member_level(int $points): array{key:string,label:string}`
- Produces `parse_positive_amount(?string $value): ?float`
- Produces `parse_id(?string $value): ?int`
- Produces `e(mixed $value): string`, `csrf_token(): string`, `verify_csrf(?string $value): bool`, `flash(string $type, string $message): void`, `pull_flash(): ?array`, and `redirect(string $location): never`

- [ ] **Step 1: Write the failing unit checks**

Create `tests/functions_test.php` before the implementation:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

function expect_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException("FAIL: {$message}");
    }
    echo "PASS: {$message}\n";
}

expect_true(calculate_points(85.0) === 8, '85 baht gives 8 points');
expect_true(calculate_points(125.0) === 12, '125 baht gives 12 points');
expect_true(member_level(999)['key'] === 'member', '999 points is Member');
expect_true(member_level(1000)['key'] === 'silver', '1000 points is Silver');
expect_true(member_level(4999)['key'] === 'silver', '4999 points is Silver');
expect_true(member_level(5000)['key'] === 'gold', '5000 points is Gold');
expect_true(member_level(9999)['key'] === 'gold', '9999 points is Gold');
expect_true(member_level(10000)['key'] === 'platinum', '10000 points is Platinum');
expect_true(parse_positive_amount('125.00') === 125.0, 'positive amount is accepted');
expect_true(parse_positive_amount('0') === null, 'zero amount is rejected');
expect_true(parse_positive_amount('-1') === null, 'negative amount is rejected');
expect_true(parse_positive_amount('abc') === null, 'non-numeric amount is rejected');
expect_true(parse_id('5') === 5, 'numeric id is accepted');
expect_true(parse_id('abc') === null, 'text id is rejected');

echo "All function checks passed.\n";
```

- [ ] **Step 2: Run the checks and verify they fail**

Run: `php tests/functions_test.php`

Expected: FAIL with an undefined-function error, proving the test is exercising missing behavior rather than passing accidentally.

- [ ] **Step 3: Implement the minimal pure rules and validation**

Create `includes/functions.php` with these exact core functions and support helpers:

```php
<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function parse_id(?string $value): ?int
{
    if ($value === null || filter_var($value, FILTER_VALIDATE_INT) === false) {
        return null;
    }

    $id = (int) $value;
    return $id > 0 ? $id : null;
}

function parse_positive_amount(?string $value): ?float
{
    if ($value === null) {
        return null;
    }

    $value = trim($value);
    if (!preg_match('/^\\d+(?:\\.\\d{1,2})?$/', $value)) {
        return null;
    }

    $amount = (float) $value;
    return $amount > 0 ? $amount : null;
}

function calculate_points(float $amount): int
{
    return (int) floor($amount / 10);
}

function member_level(int $points): array
{
    if ($points >= 10000) {
        return ['key' => 'platinum', 'label' => 'Platinum'];
    }
    if ($points >= 5000) {
        return ['key' => 'gold', 'label' => 'Gold'];
    }
    if ($points >= 1000) {
        return ['key' => 'silver', 'label' => 'Silver'];
    }
    return ['key' => 'member', 'label' => 'Member'];
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $value): bool
{
    return is_string($value)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $value);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function pull_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($message) ? $message : null;
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}
```

- [ ] **Step 4: Run the checks and verify they pass**

Run: `php tests/functions_test.php`

Expected: 13 `PASS` lines followed by `All function checks passed.`

- [ ] **Step 5: Check syntax**

Run: `php -l includes/functions.php` and `php -l tests/functions_test.php`

Expected: `No syntax errors detected` for both files.

### Task 3: Repository queries and shared responsive layout

**Files:**
- Create: `includes/repository.php`
- Create: `includes/header.php`
- Create: `includes/footer.php`
- Create: `assets/style.css`

**Interfaces:**
- `search_members(PDO $pdo, string $term = ''): array`
- `find_member(PDO $pdo, int $memberId): ?array`
- `find_member_by_no(PDO $pdo, string $memberNo): ?array`
- `create_member(PDO $pdo, array $data): int`
- `update_member(PDO $pdo, int $memberId, array $data): bool`
- `find_purchases(PDO $pdo, int $memberId): array`
- `create_purchase(PDO $pdo, int $memberId, float $amount, int $points): int`
- `cancel_purchase(PDO $pdo, int $memberId, int $purchaseId): bool`

- [ ] **Step 1: Write query contracts as a smoke script**

Create `tests/repository_smoke.php`:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$members = search_members($pdo, '');
if (count($members) < 1 || !array_key_exists('total_points', $members[0])) {
    throw new RuntimeException('Member query did not return aggregate points.');
}

$member = find_member($pdo, (int) $members[0]['id']);
if ($member === null || !array_key_exists('total_points', $member)) {
    throw new RuntimeException('Member detail query failed.');
}

echo "Repository smoke checks passed.\n";
```

- [ ] **Step 2: Run the smoke script and verify it fails**

Run: `php tests/repository_smoke.php`

Expected: FAIL because repository functions do not exist yet.

- [ ] **Step 3: Implement repository functions with prepared SQL**

Create `includes/repository.php` using named parameters for all user-controlled values. The central member query must aggregate only active points:

```php
<?php
declare(strict_types=1);

function search_members(PDO $pdo, string $term = ''): array
{
    $sql = <<<'SQL'
SELECT m.id, m.member_no, m.name, m.phone, m.joined_at,
       COALESCE(SUM(CASE WHEN p.status = 'active' THEN p.points ELSE 0 END), 0) AS total_points
FROM members m
LEFT JOIN purchases p ON p.member_id = m.id
WHERE (:term = '' OR m.member_no LIKE :pattern_no OR m.name LIKE :pattern_name)
GROUP BY m.id, m.member_no, m.name, m.phone, m.joined_at
ORDER BY m.id DESC
SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'term' => $term,
        'pattern_no' => '%' . $term . '%',
        'pattern_name' => '%' . $term . '%',
    ]);
    return $stmt->fetchAll();
}

function find_member(PDO $pdo, int $memberId): ?array
{
    $stmt = $pdo->prepare(
        "SELECT m.id, m.member_no, m.name, m.phone, m.joined_at,
                COALESCE(SUM(CASE WHEN p.status = 'active' THEN p.points ELSE 0 END), 0) AS total_points
         FROM members m
         LEFT JOIN purchases p ON p.member_id = m.id
         WHERE m.id = :id
         GROUP BY m.id, m.member_no, m.name, m.phone, m.joined_at"
    );
    $stmt->execute(['id' => $memberId]);
    $member = $stmt->fetch();
    return $member === false ? null : $member;
}

function find_member_by_no(PDO $pdo, string $memberNo): ?array
{
    $stmt = $pdo->prepare('SELECT id, member_no, name, phone, joined_at FROM members WHERE member_no = :member_no');
    $stmt->execute(['member_no' => $memberNo]);
    $member = $stmt->fetch();
    return $member === false ? null : $member;
}

function create_member(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO members (member_no, name, phone, joined_at) VALUES (:member_no, :name, :phone, :joined_at)'
    );
    $stmt->execute([
        'member_no' => $data['member_no'],
        'name' => $data['name'],
        'phone' => $data['phone'],
        'joined_at' => $data['joined_at'],
    ]);
    return (int) $pdo->lastInsertId();
}

function update_member(PDO $pdo, int $memberId, array $data): bool
{
    $stmt = $pdo->prepare('UPDATE members SET name = :name, phone = :phone WHERE id = :id');
    $stmt->execute(['name' => $data['name'], 'phone' => $data['phone'], 'id' => $memberId]);
    return true;
}

function find_purchases(PDO $pdo, int $memberId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, amount, points, status, purchased_at, cancelled_at
         FROM purchases WHERE member_id = :member_id ORDER BY purchased_at DESC, id DESC'
    );
    $stmt->execute(['member_id' => $memberId]);
    return $stmt->fetchAll();
}

function create_purchase(PDO $pdo, int $memberId, float $amount, int $points): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO purchases (member_id, amount, points) VALUES (:member_id, :amount, :points)'
    );
    $stmt->execute(['member_id' => $memberId, 'amount' => $amount, 'points' => $points]);
    return (int) $pdo->lastInsertId();
}

function cancel_purchase(PDO $pdo, int $memberId, int $purchaseId): bool
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "UPDATE purchases
             SET status = 'cancelled', cancelled_at = NOW()
             WHERE id = :id AND member_id = :member_id AND status = 'active'"
        );
        $stmt->execute(['id' => $purchaseId, 'member_id' => $memberId]);
        if ($stmt->rowCount() !== 1) {
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
}
```

- [ ] **Step 4: Implement the shared page shell**

Create `includes/header.php` so every page gets the same navigation and flash message:

```php
<?php
$pageTitle = $pageTitle ?? 'Coffee Member Rewards';
$flashMessage = pull_flash();
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Coffee Member Rewards</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="index.php"><span class="brand-mark">☕</span> Coffee Rewards</a>
        <nav class="nav-links" aria-label="เมนูหลัก">
            <a href="index.php">สมาชิก</a>
            <a class="button button-small" href="member_create.php">+ สมัครสมาชิก</a>
            <a class="button button-small button-light" href="purchase.php">บันทึกยอดซื้อ</a>
        </nav>
    </div>
</header>
<main class="container page-shell">
<?php if ($flashMessage !== null): ?>
    <div class="flash flash-<?= e($flashMessage['type']) ?>" role="status">
        <?= e($flashMessage['message']) ?>
    </div>
<?php endif; ?>
```

Create `includes/footer.php`:

```php
</main>
<footer class="site-footer">Coffee Member Rewards · WebApp ENGCE306</footer>
</body>
</html>
```

- [ ] **Step 5: Add the responsive coffee theme**

Create `assets/style.css` with the following baseline and extend only where page components require it:

```css
:root {
  --coffee-900: #3f2417;
  --coffee-700: #70452c;
  --coffee-500: #a9683f;
  --cream: #fffaf2;
  --paper: #ffffff;
  --ink: #2d2926;
  --muted: #756b64;
  --line: #eaded2;
  --success: #216e54;
  --danger: #a33c36;
  --shadow: 0 14px 35px rgba(63, 36, 23, .10);
}

* { box-sizing: border-box; }
body { margin: 0; background: var(--cream); color: var(--ink); font: 16px/1.55 system-ui, -apple-system, "Segoe UI", sans-serif; }
a { color: var(--coffee-700); text-decoration: none; }
a:hover { text-decoration: underline; }
.container { width: min(1120px, calc(100% - 32px)); margin: 0 auto; }
.site-header { background: var(--coffee-900); color: #fff; }
.nav-wrap { min-height: 72px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.brand { color: #fff; font-weight: 800; font-size: 1.2rem; }
.brand:hover { text-decoration: none; }
.brand-mark { margin-right: 8px; }
.nav-links { display: flex; align-items: center; gap: 10px; }
.page-shell { padding: 36px 0 56px; }
.page-heading { display: flex; align-items: end; justify-content: space-between; gap: 20px; margin-bottom: 24px; }
.page-heading h1 { margin: 0; color: var(--coffee-900); font-size: clamp(1.8rem, 4vw, 2.7rem); }
.muted { color: var(--muted); }
.card { background: var(--paper); border: 1px solid var(--line); border-radius: 18px; box-shadow: var(--shadow); padding: 24px; margin-bottom: 20px; }
.grid { display: grid; gap: 20px; }
.grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.field { display: grid; gap: 7px; }
.field-full { grid-column: 1 / -1; }
label { font-weight: 700; }
input { width: 100%; border: 1px solid #d8c9bd; border-radius: 10px; padding: 11px 13px; font: inherit; background: #fff; }
input:focus { outline: 3px solid rgba(169, 104, 63, .20); border-color: var(--coffee-500); }
.actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.button { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 10px; padding: 10px 16px; background: var(--coffee-700); color: #fff; cursor: pointer; font: inherit; font-weight: 700; }
.button:hover { background: var(--coffee-900); text-decoration: none; }
.button-small { padding: 7px 11px; font-size: .92rem; }
.button-light { background: #fff; color: var(--coffee-900); }
.button-danger { background: var(--danger); }
.button-muted { background: #efe7e0; color: var(--coffee-900); }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 14px 12px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: middle; }
th { color: var(--coffee-900); background: #fbf4eb; font-size: .9rem; }
.badge { display: inline-flex; border-radius: 999px; padding: 4px 10px; font-size: .82rem; font-weight: 800; }
.badge-member { background: #eee8e3; color: #64564d; }
.badge-silver { background: #e6e9eb; color: #46505a; }
.badge-gold { background: #fff0bd; color: #805d00; }
.badge-platinum { background: #d9f0ef; color: #1f6563; }
.badge-active { background: #dff2e9; color: var(--success); }
.badge-cancelled { background: #f8dfdd; color: var(--danger); }
.stat { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; }
.stat strong { color: var(--coffee-900); font-size: 2rem; }
.flash { border-radius: 12px; padding: 13px 16px; margin-bottom: 20px; font-weight: 700; }
.flash-success { background: #dff2e9; color: var(--success); }
.flash-error { background: #f8dfdd; color: var(--danger); }
.error-list { margin: 0 0 18px; padding: 12px 16px 12px 34px; color: var(--danger); background: #fff0ee; border-radius: 12px; }
.empty { padding: 28px; text-align: center; color: var(--muted); }
.site-footer { padding: 22px 16px; text-align: center; color: var(--muted); }
@media (max-width: 720px) {
  .nav-wrap, .page-heading { align-items: stretch; flex-direction: column; }
  .nav-links { flex-wrap: wrap; }
  .grid-2, .form-grid { grid-template-columns: 1fr; }
  .field-full { grid-column: auto; }
  .card { padding: 18px; }
  th, td { padding: 11px 8px; font-size: .92rem; }
}
```

- [ ] **Step 6: Run repository smoke and syntax checks**

Run: `php tests/repository_smoke.php`, `php -l includes/repository.php`, `php -l includes/header.php`, `php -l includes/footer.php`

Expected: `Repository smoke checks passed.` and no syntax errors.

### Task 4: Member list, search, create, and edit

**Files:**
- Create: `index.php`
- Create: `member_create.php`
- Create: `member_edit.php`

**Interfaces:**
- Pages consume repository functions from Task 3 and pure rules from Task 2
- `index.php` reads `$_GET['q']` only
- Create/edit forms submit `csrf_token()` in hidden POST fields and redirect after success

- [ ] **Step 1: Write the member-flow acceptance checks**

Add these checks to `README.md` as a manual checklist before writing pages: empty search shows all members, partial Thai name finds matching rows, unknown term shows `ไม่พบสมาชิก`, duplicate member number is rejected, edit keeps member number unchanged, and duplicate names show member number plus phone.

- [ ] **Step 2: Implement the member list and GET search**

Create `index.php` with this request flow:

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'สมาชิกทั้งหมด';
$term = trim((string) ($_GET['q'] ?? ''));
$members = search_members($pdo, $term);
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
  <div>
    <p class="muted">Coffee Member Rewards</p>
    <h1>สมาชิกของร้าน</h1>
  </div>
  <a class="button" href="member_create.php">+ สมัครสมาชิกใหม่</a>
</section>
<section class="card">
  <form class="actions" method="get" action="index.php">
    <label class="sr-only" for="q">ค้นหาเลขสมาชิกหรือชื่อ</label>
    <input id="q" name="q" value="<?= e($term) ?>" placeholder="ค้นหาเลขสมาชิกหรือชื่อ...">
    <button class="button" type="submit">ค้นหา</button>
    <?php if ($term !== ''): ?><a class="button button-muted" href="index.php">ล้าง</a><?php endif; ?>
  </form>
</section>
<section class="card">
  <div class="table-wrap">
    <?php if ($members === []): ?>
      <div class="empty">ไม่พบสมาชิกจากคำค้นนี้</div>
    <?php else: ?>
      <table>
        <thead><tr><th>เลขสมาชิก</th><th>ชื่อ</th><th>เบอร์โทร</th><th>แต้มรวม</th><th>ระดับ</th><th>จัดการ</th></tr></thead>
        <tbody>
        <?php foreach ($members as $member): $level = member_level((int) $member['total_points']); ?>
          <tr>
            <td><a href="member_detail.php?id=<?= (int) $member['id'] ?>"><?= e($member['member_no']) ?></a></td>
            <td><?= e($member['name']) ?></td>
            <td><?= e($member['phone']) ?></td>
            <td><strong><?= (int) $member['total_points'] ?></strong> แต้ม</td>
            <td><span class="badge badge-<?= e($level['key']) ?>"><?= e($level['label']) ?></span></td>
            <td class="actions"><a href="member_edit.php?id=<?= (int) $member['id'] ?>">แก้ไข</a><a href="purchase.php?member_id=<?= (int) $member['id'] ?>">บันทึกซื้อ</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
```

Add `.sr-only` to `assets/style.css` as a visually hidden but accessible label:

```css
.sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
```

- [ ] **Step 3: Implement member creation with duplicate validation**

Create `member_create.php`. On POST, trim `member_no`, `name`, `phone`, and `joined_at`; require all fields; call `find_member_by_no()` before `create_member()`; catch SQLSTATE `23000` as a duplicate fallback; on success call `flash('success', 'สมัครสมาชิกเรียบร้อยแล้ว')` and `redirect('index.php')`. On failure render the same form with `error-list` and preserved values. The form must include:

```php
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
```

Use `verify_csrf($_POST['csrf_token'] ?? null)` before any database write and show `คำขอไม่ถูกต้อง กรุณาลองใหม่` when it fails.

- [ ] **Step 4: Implement member editing without exposing mutable fields**

Create `member_edit.php` with this sequence:

```php
$memberId = parse_id($_GET['id'] ?? null);
if ($memberId === null) {
    http_response_code(400);
    exit('รหัสสมาชิกไม่ถูกต้อง');
}

$member = find_member($pdo, $memberId);
if ($member === null) {
    http_response_code(404);
    exit('ไม่พบสมาชิก');
}
```

For POST, validate CSRF, name, and phone, then call `update_member($pdo, $memberId, ['name' => $name, 'phone' => $phone])` and redirect to `member_detail.php?id=<id>`. Display `member_no` as plain text or `readonly`; never accept it as an update value. Preserve submitted name/phone when validation fails.

- [ ] **Step 5: Verify member pages**

Run:

```powershell
php -l index.php
php -l member_create.php
php -l member_edit.php
```

Then open `http://localhost/LAP7/index.php` and verify: all rows display, `q` is retained in the search box, Thai partial search works, empty results show the message, create redirects after save, Refresh does not duplicate the member, duplicate number is rejected, and edit changes only name/phone.

### Task 5: Purchase, points, member detail, and cancellation

**Files:**
- Create: `purchase.php`
- Create: `member_detail.php`

**Interfaces:**
- `purchase.php` consumes `calculate_points`, `parse_positive_amount`, `search_members`, and `create_purchase`
- `member_detail.php` consumes `parse_id`, `find_member`, `find_purchases`, and `cancel_purchase`
- A cancelled purchase remains visible in history with its original amount/points and no longer contributes to the total

- [ ] **Step 1: Write the purchase/cancellation acceptance checks**

Use the exact cases in the approved spec: 85 -> 8, 125 -> 12, 0/negative rejected, 995 + 5 -> Silver, 4,990 + 10 -> Gold, 9,995 + 5 -> Platinum, cancelling 5 points from 1,000 returns to 995 Member, and a second cancellation does not change the total.

- [ ] **Step 2: Implement GET member search and preview calculation**

Create `purchase.php` so GET accepts `q` and optional `member_id`. Search results must show `member_no`, `name`, `phone`, and current total points. The purchase form posts `member_id` and `amount`; use a small inline script to preview points without making the preview authoritative:

```html
<label for="amount">ยอดซื้อ (บาท)</label>
<input id="amount" name="amount" inputmode="decimal" pattern="[0-9]+(\.[0-9]{1,2})?" required>
<p class="muted">แต้มที่จะได้รับ: <strong id="points-preview">0</strong> แต้ม</p>
<script>
  const amountInput = document.getElementById('amount');
  const pointsPreview = document.getElementById('points-preview');
  amountInput.addEventListener('input', () => {
    const amount = Number(amountInput.value);
    pointsPreview.textContent = Number.isFinite(amount) && amount > 0
      ? Math.floor(amount / 10)
      : '0';
  });
</script>
```

- [ ] **Step 3: Implement POST purchase validation and insert**

On POST in `purchase.php`, execute this exact server-side sequence:

```php
if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    $errors[] = 'คำขอไม่ถูกต้อง กรุณาลองใหม่';
}

$memberId = parse_id($_POST['member_id'] ?? null);
$amount = parse_positive_amount($_POST['amount'] ?? null);

if ($memberId === null) {
    $errors[] = 'กรุณาเลือกสมาชิก';
}
if ($amount === null) {
    $errors[] = 'ยอดซื้อต้องเป็นตัวเลขมากกว่า 0 และมีทศนิยมไม่เกิน 2 ตำแหน่ง';
}
if ($memberId !== null && find_member($pdo, $memberId) === null) {
    $errors[] = 'ไม่พบสมาชิกที่เลือก';
}

if ($errors === []) {
    $points = calculate_points($amount);
    create_purchase($pdo, $memberId, $amount, $points);
    flash('success', "บันทึกยอดซื้อ {$amount} บาท ได้รับ {$points} แต้ม");
    redirect('member_detail.php?id=' . $memberId);
}
```

The form must not contain a points input. Re-render errors without inserting when validation fails.

- [ ] **Step 4: Implement member detail and active-only cancellation**

Create `member_detail.php` with GET ID validation, `find_member()`, and `find_purchases()`. Display current total and `member_level(total_points)`. For each purchase display date/time, amount, points, status, and a POST cancel form only when status is `active`:

```php
<?php if ($purchase['status'] === 'active'): ?>
  <form method="post" onsubmit="return confirm('ยืนยันยกเลิกรายการนี้หรือไม่?');">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="purchase_id" value="<?= (int) $purchase['id'] ?>">
    <button class="button button-small button-danger" type="submit">ยกเลิก</button>
  </form>
<?php else: ?>
  <span class="badge badge-cancelled">ยกเลิกแล้ว</span>
<?php endif; ?>
```

On POST, validate CSRF and `purchase_id`, call `cancel_purchase($pdo, $memberId, $purchaseId)`, and redirect back to the same detail page. If it returns false, flash `รายการนี้อาจถูกยกเลิกแล้วหรือไม่ใช่ของสมาชิกคนนี้` and redirect without changing points. This enforces one cancellation and protects against a mismatched member/purchase ID.

- [ ] **Step 5: Verify point and cancellation boundaries**

Run syntax checks:

```powershell
php -l purchase.php
php -l member_detail.php
```

Then execute the manual matrix: create or use a member, record 85 and 125 baht purchases, check totals, cancel one active row, Refresh, attempt the same cancellation again, and verify the total and level change only once. Test invalid, missing, non-numeric, and nonexistent IDs directly in the URL.

### Task 6: Documentation, full verification, and handoff

**Files:**
- Create: `README.md`
- Modify: any page or CSS file only when a verification case exposes a concrete defect

- [ ] **Step 1: Document local setup**

Create `README.md` with these exact setup steps:

```markdown
# Coffee Member Rewards

## Requirements

- PHP 8.1 or newer with PDO MySQL
- MySQL 8 or MariaDB
- Apache via XAMPP, or PHP's local server

## Setup

1. Import `coffee_db.sql` into MySQL/phpMyAdmin.
2. Check credentials in `config/database.php`.
3. Place the project under the web server document root.
4. Open `index.php` in the browser.

For PHP's built-in server, run `php -S localhost:8000` from this folder and open `http://localhost:8000/index.php`.

## Pages

- `index.php` - member list and search
- `member_create.php` - create member
- `member_edit.php?id=1` - edit name and phone
- `purchase.php` - search/select member and record purchase
- `member_detail.php?id=1` - member detail, history, and cancellation

## Rules

- 10 baht = 1 point; decimals are rounded down per purchase.
- Active purchases count toward the total.
- Cancelled purchases remain in history and do not count toward points.

## Demo checks

- 85 baht -> 8 points
- 125 baht -> 12 points
- 995 + 50 baht -> 1,000 points, Silver
- 1,000 points minus a 5-point cancellation -> 995 points, Member
```

- [ ] **Step 2: Run the complete syntax check**

Run:

```powershell
Get-ChildItem -File -Filter *.php | ForEach-Object { php -l $_.FullName }
php tests/functions_test.php
php tests/repository_smoke.php
```

Expected: every PHP file reports no syntax errors, function checks pass, and repository smoke passes.

- [ ] **Step 3: Run the browser acceptance matrix**

Verify all of the following in the browser, recording the result in the handoff:

1. List all members and search by full/partial Thai name.
2. Search by member number and verify the query remains in the input.
3. Search for an unknown term and verify the empty-state message.
4. Create a member with a new number, then Refresh and verify one row only.
5. Attempt the same member number and verify a validation error.
6. Edit name and phone and verify member number and points are unchanged.
7. Select a duplicate-name member and verify both number and phone are visible.
8. Record 85 and 125 baht and verify 8/12 points.
9. Submit 0, a negative number, text, and an invalid member ID; verify none is inserted.
10. Cancel an active purchase, verify status changes and total/level recalculates.
11. Submit the same cancellation twice and verify points are not deducted twice.
12. Check desktop and narrow mobile widths for clipped or overflowing content.

- [ ] **Step 4: Review the final diff and workspace state**

Run:

```powershell
Get-ChildItem -Recurse -File | Sort-Object FullName | Select-Object FullName
```

Confirm the deliverable contains the SQL, five required pages, shared includes, CSS, tests, README, and the approved design/plan docs. The current folder is not a Git repository, so do not initialize or alter `.git`; report that no commit was created.

## Plan self-review

- Spec coverage: Tasks 1-3 implement PDO, schema, prepared queries, UTF-8, helpers, CSRF, and responsive shell; Task 4 covers member create/list/search/edit; Task 5 covers purchase, active-only totals, level recalculation, history, and one-time cancellation; Task 6 covers setup and every PDF acceptance case.
- Placeholder scan: ไม่พบคำค้างหรือขั้นตอนที่ปล่อยให้ผู้พัฒนาต้องเดาเอง; ฟังก์ชันหลัก, SQL, คำสั่งตรวจสอบ และผลลัพธ์ที่คาดหวังถูกระบุครบ
- Type consistency: repository functions accept `PDO` and typed IDs/amounts consistently; pages use `parse_id()`/`parse_positive_amount()` before repository writes; `member_level()` consumes the integer aggregate returned by the member queries.
- Scope check: the plan produces one independently runnable Plain PHP application and does not add menu, inventory, payment, or customer login subsystems.
