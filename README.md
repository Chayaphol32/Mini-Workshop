# Coffee Member Rewards

Mini Workshop จาก Lab 7 สำหรับจัดการสมาชิกและแต้มสะสมของร้านกาแฟด้วย PHP + MySQL + PDO

## Requirements

- PHP 8.1 หรือใหม่กว่า พร้อม PDO MySQL
- MySQL 5.7+ หรือ MariaDB
- Apache และ MySQL จาก MAMP/XAMPP หรือ PHP local server

## Setup ด้วย MAMP

1. เปิด MAMP ให้ Apache และ MySQL ทำงาน
2. Import ไฟล์ `coffee_db.sql` ผ่าน phpMyAdmin หรือใช้ MySQL client
3. ตรวจค่าการเชื่อมต่อใน `config/database.php`
4. เปิดโปรเจกต์ผ่าน Apache หรือรัน PHP local server จากโฟลเดอร์นี้

ค่าเริ่มต้นสำหรับ MAMP ในโปรเจกต์นี้คือ host `127.0.0.1`, port `3306`, database `coffee_rewards`, user `root`, password `root`

ถ้าต้องการใช้ค่าของตัวเอง ให้ตั้ง environment variables `COFFEE_DB_HOST`, `COFFEE_DB_PORT`, `COFFEE_DB_NAME`, `COFFEE_DB_USER` และ `COFFEE_DB_PASSWORD`

## Login และสิทธิ์การใช้งาน

หลัง import ฐานข้อมูลเดิม ให้เพิ่มตารางบัญชีและ seed บัญชีตัวอย่าง:

```powershell
$mysql = 'C:\MAMP\bin\mysql\bin\mysql.exe'
$sql = Get-Content -Raw -LiteralPath 'database_upgrade_auth.sql'
& $mysql -h 127.0.0.1 -P 3306 -u root -proot coffee_rewards -e $sql

$php = 'C:\MAMP\bin\php\php8.3.1\php.exe'
$ext = 'C:\MAMP\bin\php\php8.3.1\ext'
$session = 'C:\Users\Chaya\verse\y4.1\wap\code\LAP7\tmp\sessions'
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll -d session.save_path=$session scripts/seed_demo_accounts.php

$orderSql = Get-Content -Raw -LiteralPath 'database_upgrade_orders.sql'
& $mysql -h 127.0.0.1 -P 3306 -u root -proot coffee_rewards -e $orderSql
```

บัญชีสำหรับทดสอบบนเครื่อง local เท่านั้น:

- Admin: `admin` / `admin123`
- User: `user1` / `user123` (ผูกกับสมาชิก `M0001`)

ก่อนใช้งานจริงควรเปลี่ยนรหัสผ่านและไม่เผยแพร่บัญชีตัวอย่าง

## วิธีรัน local server

ตัวอย่างเมื่อใช้ PHP ของ MAMP:

```powershell
$php = 'C:\MAMP\bin\php\php8.3.1\php.exe'
$ext = 'C:\MAMP\bin\php\php8.3.1\ext'
$session = 'C:\Users\Chaya\verse\y4.1\wap\code\LAP7\tmp\sessions'
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll -d session.save_path=$session -S 127.0.0.1:8000 -t .
```

จากนั้นเปิด `http://127.0.0.1:8000/index.php`

ถ้าเปิดผ่าน Apache ใต้โฟลเดอร์ย่อย เช่น `http://localhost/LAP7/` ให้ตั้งค่า `COFFEE_APP_BASE=LAP7` เพื่อให้ลิงก์ของหน้า User/Admin ชี้กลับมายังโปรเจกต์ถูกต้อง

## Pages

- `index.php` - ส่งต่อไปหน้า login หรือ dashboard ตาม role
- `login.php` - เข้าสู่ระบบ User/Admin
- `register.php` - สมัครบัญชี User และสร้างสมาชิกใหม่
- `logout.php` - ออกจากระบบด้วย POST + CSRF
- `user/index.php` - dashboard ส่วนตัว แต้ม และออเดอร์ล่าสุด
- `user/menu.php` - เลือกเมนูและส่งออเดอร์
- `user/orders.php` - ประวัติออเดอร์ของตัวเอง
- `user/order_detail.php?id=1` - รายละเอียด/ยกเลิกออเดอร์ pending
- `user/profile.php` - แก้ไขชื่อและเบอร์โทรของตัวเอง
- `admin/index.php` - dashboard และตัวเลขสรุปของร้าน
- `admin/members.php` - ค้นหา/จัดการสมาชิกทั้งหมด
- `admin/member_create.php`, `admin/member_edit.php`, `admin/member_detail.php` - CRUD สมาชิกและประวัติแต้ม
- `admin/purchase.php` - บันทึกยอดซื้อแบบ Manual
- `admin/orders.php`, `admin/order_detail.php?id=1` - จัดการออเดอร์ทุกคนและเปลี่ยนสถานะ
- `admin/menu.php`, `admin/menu_create.php`, `admin/menu_edit.php` - CRUD เมนูอาหาร
- `admin/users.php` - สร้าง/เปิด/ปิดบัญชี User

URL เดิมของ Workshop เช่น `member_create.php`, `member_edit.php`, `member_detail.php` และ `purchase.php` จะ redirect ไปหน้า Admin ที่ตรงกัน และไม่เปิดให้ User ใช้งาน

## Rules

- 10 บาท = 1 แต้ม และปัดเศษลงแยกแต่ละรายการ
- รายการ `active` เท่านั้นที่นับแต้ม
- รายการ `cancelled` ยังอยู่ในประวัติแต่ไม่นับแต้ม
- 0–999 แต้ม = Member
- 1,000–4,999 แต้ม = Silver
- 5,000–9,999 แต้ม = Gold
- ตั้งแต่ 10,000 แต้ม = Platinum

## Verification

ใช้ PHP ของ MAMP ตรวจ syntax และรัน test:

```powershell
$php = 'C:\MAMP\bin\php\php8.3.1\php.exe'
$ext = 'C:\MAMP\bin\php\php8.3.1\ext'
$session = 'C:\Users\Chaya\verse\y4.1\wap\code\LAP7\tmp\sessions'
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll tests/functions_test.php
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll -d session.save_path=$session tests/repository_smoke.php
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll tests/auth_unit_test.php
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll -d session.save_path=$session tests/auth_repository_smoke.php
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll tests/order_unit_test.php
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll -d session.save_path=$session tests/order_repository_smoke.php
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll tests/admin_order_unit_test.php
& $php -n -d extension_dir=$ext -d extension=php_pdo_mysql.dll -d session.save_path=$session tests/admin_repository_smoke.php
```

กรณีทดสอบสำคัญ: 85 บาทได้ 8 แต้ม, 125 บาทได้ 12 แต้ม, User เห็นเฉพาะข้อมูลตัวเอง, Admin เห็นข้อมูลทั้งหมด, ราคาเมนูคำนวณจาก server, ออเดอร์ที่ยกเลิกไม่แต้ม และออเดอร์สำเร็จสร้าง purchase ได้ครั้งเดียว
