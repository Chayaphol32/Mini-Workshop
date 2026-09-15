# Design Spec: Coffee Ordering & Member Rewards

วันที่: 2026-09-13

## เป้าหมาย

ต่อยอด Mini Workshop เดิมให้เป็นระบบสั่งอาหาร/เครื่องดื่มที่มีการเข้าสู่ระบบและแบ่งสิทธิ์ชัดเจน โดยสมาชิกทั่วไปเห็นเฉพาะข้อมูลของตนเอง ส่วนผู้ดูแลระบบเห็นและจัดการข้อมูลทั้งหมดได้ ระบบแต้มเดิมยังต้องทำงานต่อเนื่องและรองรับรายการซื้อจากออเดอร์ที่สำเร็จ

## ขอบเขตบทบาท

### User/สมาชิก

- สมัครบัญชีและเข้าสู่ระบบด้วย username และ password
- ดูข้อมูลสมาชิกของตนเอง ได้แก่ ชื่อ เบอร์โทร เลขสมาชิก แต้ม และระดับสมาชิก
- ดูเมนูที่เปิดขายและราคาปัจจุบัน
- สร้างออเดอร์โดยเลือกเมนูและจำนวน
- ดูเลขออเดอร์ รายการอาหาร ยอดรวม และสถานะของออเดอร์ตัวเอง
- ยกเลิกออเดอร์ได้เฉพาะสถานะ `pending`
- ไม่สามารถค้นหา ดู แก้ไข หรือยกเลิกข้อมูลของสมาชิก/ออเดอร์คนอื่น

### Admin/ผู้ดูแลระบบ

- เข้าสู่ระบบจากหน้า login เดียวกัน แล้วถูกส่งไปหน้า Admin Dashboard
- ดูสมาชิก ออเดอร์ เมนู และประวัติแต้มของทุกคน
- เพิ่ม แก้ไข และค้นหาสมาชิก
- สร้างหรือปิดใช้งานบัญชี User ที่ผูกกับสมาชิก
- เพิ่ม แก้ไข และเปิด/ปิดการขายเมนู
- ดูรายละเอียดออเดอร์ทั้งหมดและเปลี่ยนสถานะออเดอร์
- บันทึกยอดซื้อแบบเดิมให้สมาชิก และยกเลิกรายการซื้อได้ตามกติกาเดิม
- Admin จะไม่เห็น password เดิมหรือข้อมูลลับของบัญชีในรูปแบบอ่านได้

## ประสบการณ์การใช้งาน

### ผู้ใช้ที่ยังไม่เข้าสู่ระบบ

- `login.php` เป็นหน้าเริ่มต้น มีฟอร์ม username/password และลิงก์ไป `register.php`
- `register.php` รับ username ชื่อ เบอร์โทร และ password ระบบสร้างสมาชิกกับบัญชี User ใน transaction เดียวกัน
- หลังสมัครสำเร็จให้เข้าสู่ระบบได้ และส่งไป `user/index.php`
- มีข้อความแจ้งข้อผิดพลาดที่เข้าใจง่าย โดยไม่เปิดเผยว่า username ใดมีอยู่แล้วเกินความจำเป็น

### User Portal

- `user/index.php` แสดงแต้มปัจจุบัน ระดับสมาชิก ออเดอร์ล่าสุด และปุ่มสั่งอาหาร
- `user/menu.php` แสดงเฉพาะเมนูที่ active พร้อมราคาและจำนวนที่เลือก
- `user/order_create.php` รับรายการจากเมนู แต่ต้องอ่านราคาและสถานะเมนูจากฐานข้อมูลซ้ำบน server ก่อนบันทึกเสมอ
- `user/orders.php` แสดงเฉพาะออเดอร์ของ user ที่ login อยู่ พร้อมกรองสถานะได้
- `user/order_detail.php?id=...` แสดงรายละเอียดเฉพาะออเดอร์ของ user คนนั้น และมีปุ่มยกเลิกเฉพาะเมื่อสถานะเป็น `pending`
- `user/profile.php` แสดงข้อมูลตนเองและเปิดให้แก้ไขชื่อ/เบอร์โทร ส่วน username และเลขสมาชิกเป็นข้อมูลอ่านอย่างเดียว

### Admin Portal

- `admin/index.php` แสดงจำนวนสมาชิก ออเดอร์รอดำเนินการ ยอดขาย และแต้มที่ใช้งาน
- `admin/members.php`, `admin/member_create.php`, `admin/member_edit.php`, `admin/member_detail.php` เป็น CRUD สมาชิกเดิมที่ย้ายมาอยู่ใต้สิทธิ์ Admin
- `admin/orders.php` แสดงออเดอร์ทุกคน ค้นหาด้วยเลขออเดอร์/ชื่อ/เลขสมาชิก และกรองสถานะ
- `admin/order_detail.php?id=...` แสดงข้อมูลลูกค้า รายการอาหาร ยอดรวม และประวัติสถานะ พร้อมฟอร์มเปลี่ยนสถานะ
- `admin/menu.php`, `admin/menu_create.php`, `admin/menu_edit.php` จัดการชื่อ รายละเอียด ราคา และสถานะ active/inactive ของเมนู
- `admin/users.php` แสดงบัญชี User/Admin และเปิด/ปิดใช้งานบัญชี User โดยไม่แก้ password เดิมโดยตรง
- `admin/purchase.php` ใช้บันทึกยอดซื้อแบบ manual จาก Workshop เดิม และ `admin/member_detail.php` ใช้ดู/ยกเลิกรายการซื้อ

### การนำทางและการป้องกันหน้า

- `index.php` เป็น dispatcher: ยังไม่ login ไป `login.php`, User ไป `user/index.php`, Admin ไป `admin/index.php`
- ทุกหน้า User เรียก `require_login()` และตรวจ role เป็น `user`
- ทุกหน้า Admin เรียก `require_login()` และตรวจ role เป็น `admin`
- URL เดิมของ Workshop ที่ root เช่น `member_create.php` และ `purchase.php` จะ redirect ไปหน้า Admin ที่เกี่ยวข้องเมื่อ login แล้ว หรือ redirect ไป login เมื่อยังไม่ login เพื่อไม่ให้เกิดหน้าซ้ำที่ไม่มีการป้องกัน
- `logout.php` ใช้ POST พร้อม CSRF แล้วทำลาย session และส่งกลับ login

## โมเดลข้อมูล

ตาราง `members` และ `purchases` เดิมจะไม่ถูกลบ เพื่อรักษาข้อมูล Mini Workshop

### `users`

- `id` primary key
- `member_id` nullable foreign key ไป `members.id`; User ต้องมีค่า ส่วน Admin เป็น null
- `username` unique, lowercase/trim ก่อนบันทึก
- `password_hash` เก็บผลจาก `password_hash()` เท่านั้น
- `role` เป็น `user` หรือ `admin`
- `status` เป็น `active` หรือ `disabled`
- `last_login_at`, `created_at`, `updated_at`
- unique `member_id` เพื่อให้สมาชิกหนึ่งคนมีบัญชี User ได้หนึ่งบัญชี

### `menu_items`

- `id`, `name`, `description`
- `price` เป็น decimal 2 ตำแหน่งและต้องมากกว่า 0
- `status` เป็น `active` หรือ `inactive`
- `created_at`, `updated_at`

### `orders`

- `id` และเลขแสดงผลรูปแบบ `ORD-000001` จาก id
- `user_id` foreign key ไป `users.id`
- `member_id` foreign key ไป `members.id` เพื่อค้นหารายงานและแต้มได้เร็ว
- `status`: `pending`, `preparing`, `ready`, `completed`, `cancelled`
- `total_amount` คำนวณจากรายการบน server
- `purchase_id` nullable unique foreign key ไป `purchases.id` เมื่อออเดอร์ได้รับแต้มแล้ว
- `created_at`, `updated_at`, `completed_at`, `cancelled_at`

### `order_items`

- `id`, `order_id`, `menu_item_id`
- `item_name` และ `unit_price` เป็น snapshot ตอนสั่ง เพื่อให้ประวัติไม่เปลี่ยนตามการแก้เมนูภายหลัง
- `quantity` เป็นจำนวนเต็ม 1–99
- `line_total` คำนวณจาก unit price x quantity บน server

### ความสัมพันธ์กับระบบแต้มเดิม

- ออเดอร์ที่สถานะ `completed` เท่านั้นจึงสร้างรายการใน `purchases` และคำนวณแต้มด้วย `floor(total_amount / 10)`
- การเปลี่ยนเป็น `completed` และการสร้าง purchase ต้องอยู่ใน transaction เดียวกัน และต้องไม่สร้างซ้ำเมื่อกดซ้ำ
- การยกเลิกออเดอร์ก่อน completed ไม่สร้างแต้ม
- ออเดอร์ที่ completed แล้วไม่เปิดให้ User ยกเลิกจากหน้า User; หากต้องแก้ไขให้ Admin จัดการผ่านรายการซื้อเดิมตามกติกา cancellation
- รายการ `purchases` manual จาก Workshop ยังคงนับแต้มและแสดงในรายละเอียดสมาชิกเหมือนเดิม

## กติกาออเดอร์และสถานะ

- User เลือกเฉพาะเมนู active; เมนู inactive ที่ค้างอยู่ใน browser ต้องถูกปฏิเสธเมื่อ submit
- จำนวนต้องเป็นจำนวนเต็ม 1–99 ต่อเมนู และต้องมีอย่างน้อยหนึ่งรายการ
- ราคารวมและแต้มคำนวณใหม่บน server ห้ามเชื่อค่าจาก hidden input หรือ JavaScript
- ลำดับสถานะหลักคือ `pending` → `preparing` → `ready` → `completed`
- `pending` เปลี่ยนเป็น `cancelled` ได้โดย User หรือ Admin
- Admin ยกเลิกออเดอร์ที่ยังไม่ completed ได้; สถานะ terminal (`completed`, `cancelled`) ไม่ย้อนกลับ
- การเปลี่ยนสถานะทุกครั้งต้องมี CSRF และ redirect หลัง POST

## ความปลอดภัย

- ใช้ `password_hash()`/`password_verify()` ไม่เก็บ password แบบ plain text
- หลัง login สำเร็จใช้ `session_regenerate_id(true)` และเก็บเฉพาะ user id, role, member id ใน session
- ตั้งค่า session cookie เป็น HttpOnly, SameSite=Lax และ Secure เมื่อใช้งาน HTTPS
- ใช้ prepared statements ทุก query ที่รับค่าจากผู้ใช้
- ใช้ CSRF token กับ register, login state-changing flows ที่เกี่ยวข้อง, profile, order, status update, member/menu/user mutations และ logout
- escape output ด้วย `htmlspecialchars` และตรวจชนิด/ช่วงค่าบน server
- Query ของ User ต้องมีเงื่อนไข `user_id` หรือ `member_id` จาก session เสมอ เพื่อป้องกันการเดา id แล้วเห็นข้อมูลคนอื่น
- login ปฏิเสธบัญชี disabled และแสดงข้อความรวมที่ไม่บอกว่าผิดที่ username หรือ password

## การย้ายข้อมูลและ seed สำหรับเครื่อง local

- เพิ่มตารางใหม่แบบไม่ลบ `members`/`purchases` และเก็บข้อมูล seed เดิมไว้
- เพิ่มบัญชีตัวอย่างสำหรับทดสอบ local: `admin / admin123` และ `user1 / user123` โดย `user1` ผูกกับสมาชิก `M0001`; ใน README ต้องระบุว่าเป็นรหัสสำหรับ workshop เท่านั้นและควรเปลี่ยนก่อนใช้งานจริง
- เพิ่มเมนูตัวอย่างอย่างน้อย 5 รายการ เพื่อให้ User ทดสอบการสร้างออเดอร์ได้ทันที
- การสร้าง hash ของบัญชี seed ทำด้วย PHP ไม่ใช้การเก็บ password ตรง ๆ ในฐานข้อมูล
- หากต้องรองรับฐานข้อมูลเดิม ให้ใช้ migration/upgrade script ที่ตรวจว่าตารางหรือข้อมูลมีอยู่แล้วก่อนเพิ่ม ไม่ใช้ `DROP TABLE`

## นอกขอบเขตของรอบนี้

- การชำระเงินจริงหรือเชื่อม payment gateway
- การจัดส่งและคำนวณค่าส่ง
- การอัปโหลดรูปเมนู
- ระบบลืม password ผ่าน email/SMS
- หลายสาขาและคลังวัตถุดิบ

## เกณฑ์ยอมรับงาน

1. ผู้ที่ยังไม่ login เข้า protected URL ไม่ได้และถูกส่งไป login
2. User login แล้วเห็น dashboard/เมนู/ออเดอร์ของตนเอง และไม่สามารถเปิดข้อมูล User คนอื่นด้วยการเปลี่ยน id ใน URL
3. User สั่งเมนู active สำเร็จ ยอดรวมถูกต้อง และออเดอร์เริ่มที่ `pending`
4. User ยกเลิกได้เฉพาะ pending; ออเดอร์ cancelled ไม่สร้างแต้ม
5. Admin login แล้วเห็นข้อมูลสมาชิกและออเดอร์ทั้งหมด พร้อมจัดการเมนู/สมาชิก/สถานะออเดอร์ได้
6. เมื่อ Admin เปลี่ยนออเดอร์เป็น completed ระบบสร้าง purchase ครั้งเดียวและคำนวณแต้มตามกติกาเดิม เช่น 85 บาทได้ 8 แต้ม และ 125 บาทได้ 12 แต้ม
7. เมนู inactive ราคา/จำนวนผิด และคำขอที่ไม่มีหรือมี CSRF ผิดถูกปฏิเสธทั้งฝั่ง server
8. ข้อมูล Mini Workshop เดิมยังอยู่ และหน้ารายงานแต้มเดิมคำนวณเฉพาะ purchase ที่ active
9. มีการตรวจ syntax, ฟังก์ชัน/รีโพซิทอรี, auth/RBAC, order lifecycle และทดสอบผ่าน browser ทั้ง User และ Admin
