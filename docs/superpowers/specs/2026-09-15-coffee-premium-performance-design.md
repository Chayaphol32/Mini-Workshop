# Design Spec: Premium Cinematic Coffee Rewards & Performance

วันที่: 2026-09-15

## เป้าหมาย

ยกระดับ Coffee Member Rewards จากหน้าระบบ Workshop ให้เป็นประสบการณ์ร้านกาแฟแบบ premium ที่มี motion ชัดเจน ใช้งานได้ดีบนมือถือ และตอบสนองไวขึ้น โดยคง PHP + TiDB + Vercel และพฤติกรรมสิทธิ์ User/Admin ที่มีอยู่ทั้งหมด

จากการวัด production ปัจจุบัน `login.php` ใช้เวลาเริ่มตอบสนองประมาณ 4 วินาที ขณะที่ไฟล์ CSS ใช้เวลาประมาณ 0.18 วินาที สาเหตุหลักคือหน้า public เปิดการเชื่อมต่อ TiDB ทุกครั้งแม้เป็นการแสดงฟอร์มอย่างเดียว รอบนี้จึงแก้ที่จุดคอขวดนี้ก่อน และเพิ่มการ cache asset แบบ versioned

## ทิศทางที่เลือก

เลือกแนวทาง **Reserve Coffee Club** จาก mockup รอบแรก และใช้ระดับ **Cinematic Motion** เป็นแกน โดยหยิบเอฟเฟกต์บางส่วนของ Immersive Motion มาใช้เฉพาะ hero, reward status และ CTA ที่สำคัญ

แนวทางนี้เหมาะกับโค้ดปัจจุบันที่สุด เพราะยังใช้ server-rendered PHP ที่ deploy อยู่แล้ว ไม่ต้องย้ายระบบเป็น SPA และไม่เพิ่ม dependency ที่ทำให้ cold start หรือขนาด asset ใหญ่ขึ้น

## ทางเลือกที่พิจารณา

### 1. CSS-first progressive enhancement — เลือกใช้

- ปรับ shared header, page shell และ component styles ให้เป็น premium
- ใช้ CSS animation ด้วย `transform` และ `opacity` เป็นหลัก
- ปรับ bootstrap ให้หน้า public แบบ GET ไม่เปิด DB จนกว่าจะมี POST ที่ต้องใช้ข้อมูล
- เพิ่ม cache headers และ asset versioning
- คง server-side authorization, CSRF และราคาที่คำนวณจาก server

ข้อดีคือความเสี่ยงต่ำ, deploy ได้กับ PHP runtime เดิม, ทดสอบได้ง่าย และควบคุม performance ได้ดีที่สุด

### 2. ย้ายเป็น React/Next.js เต็มรูปแบบ

ให้ interaction และ animation ได้มากกว่า แต่ต้องย้าย routing, session, auth และการเชื่อมต่อ TiDB ใหม่ทั้งหมด จึงไม่เหมาะกับรอบที่ต้องการแก้ปัญหาความเร็วและส่งมอบระบบเดิมต่อเนื่อง

### 3. เพิ่มภาพและ animation library จากภายนอก

สร้างภาพจำที่โดดเด่นได้เร็ว แต่เพิ่ม request, bundle size, dependency และความเสี่ยงด้าน mobile performance โดยไม่แก้ต้นเหตุ 4 วินาทีจาก DB connection

## ภาษาภาพและ motion

- โทนหลัก: espresso black, warm cream, roasted brown และ copper highlight
- ใช้สี accent แยกสถานะ User/Admin แต่ยังอยู่ในระบบสีเดียวกัน
- ใช้ system font stack สำหรับ body เพื่อไม่เพิ่ม request จาก web font และใช้ serif display เฉพาะ headline ที่ต้องการบรรยากาศ
- ใช้ eyebrow แบบ uppercase, headline ใหญ่, panel แบบ layered และ status badge ที่อ่านได้ทันที
- ใช้ `page-enter`, `reveal-up`, `float`, `glow`, `shine` และ hover lift แบบสั้นและมีจังหวะ
- animation ทุกตัวต้องใช้เฉพาะ `transform`, `opacity`, `box-shadow` หรือสีที่ไม่ทำให้ layout reflow
- มี `@media (prefers-reduced-motion: reduce)` เพื่อปิด animation และ transition เมื่อผู้ใช้ร้องขอ
- ไม่มี video, external image, external font หรือ animation package ในรอบนี้

## ประสบการณ์ที่ปรับ

### Guest / Login / Register

- หน้า Login และ Register แบบ GET แสดงผลได้โดยไม่ต้องรอ TiDB
- หน้า Root redirect ไป Login ได้เร็วขึ้นเมื่อยังไม่มี session
- ฟอร์มใช้ hierarchy ชัด, CTA เด่น, error state อ่านง่าย และมี motion ตอนเปิดหน้าแบบเบา
- เมื่อเป็น POST จึงค่อยโหลด database connection เพื่อ login หรือสร้างบัญชี

### Shared shell

- Header มี brand lockup, role identity และ nav ที่สื่อชัดว่าอยู่ฝั่ง User หรือ Admin
- บนมือถือใช้ native `<details>` เป็นเมนูแบบไม่ต้องโหลด JavaScript เพิ่ม
- เพิ่ม skip link, `main` landmark และ focus state ที่มองเห็นได้
- Footer และ page shell ใช้พื้นผิว/เส้นแบ่งเดียวกันทั้งระบบ

### User portal

- Dashboard แสดงแต้ม, ระดับสมาชิก และ shortcut สั่งซ้ำในลำดับสายตาที่ชัด
- เพิ่ม visual weight ให้ reward status และออเดอร์ล่าสุด โดยไม่เปลี่ยน data scope
- หน้าเมนูใช้ card ที่เลือกจำนวนได้ง่าย, hover/press feedback และ action panel ที่ติดอยู่ในตำแหน่งใช้งานสะดวกบน desktop
- สถานะออเดอร์ใช้สีและ label ที่แยกได้ชัดบนมือถือ

### Admin portal

- Dashboard ใช้ KPI card, quick actions และ operational hierarchy ที่อ่านได้ในครั้งเดียว
- ตาราง, filter, badge และ action button ใช้ spacing และ state เดียวกัน
- เพิ่ม motion เฉพาะการเข้าสู่หน้าและการ hover เพื่อไม่รบกวนงานที่ต้องจัดการข้อมูลจำนวนมาก
- คงการเห็นข้อมูลทั้งหมดและความสามารถ CRUD เดิมไว้ครบ

## Performance architecture

1. `includes/bootstrap.php` รองรับโหมด `COFFEE_SKIP_DATABASE` สำหรับ request ที่ยังไม่ต้องใช้ DB
2. `login.php`, `register.php`, `index.php` และ `logout.php` ใช้โหมดนี้ในเส้นทางที่ไม่ต้อง query และเรียก `config/database.php` เฉพาะ POST หรือเส้นทางที่ต้องใช้ข้อมูล
3. `assets/style.css` ถูกเสิร์ฟเป็น static asset พร้อม `Cache-Control: public, max-age=31536000, immutable`
4. `asset_url()` เติม version จาก file modification time ให้ browser cache ได้เต็มที่โดยยัง invalidation เมื่อ asset เปลี่ยน
5. TiDB PDO รองรับ persistent connection แบบเปิดใช้ได้ผ่าน `TIDB_PERSISTENT` เพื่อช่วยลด handshake ใน warm Vercel instance โดยยังปิดได้หาก provider/runtime ไม่เหมาะสม
6. ไม่เพิ่ม JavaScript framework และไม่ย้าย logic ราคา, auth, role หรือ CSRF ไปฝั่ง client

## ความถูกต้องและการถอยกลับ

- ไม่แก้ schema และไม่ลบข้อมูลใน TiDB
- ไม่เปลี่ยน rule แต้ม, order lifecycle, role scope หรือ authorization
- หาก browser ไม่รองรับ feature motion จะยังเห็น layout และ content ครบ
- หาก `TIDB_PERSISTENT` ไม่เหมาะสม ให้ตั้งค่าเป็น `0` ได้โดยไม่กระทบ connection ปกติ
- หาก asset cache มีปัญหา URL version ใหม่จาก `asset_url()` จะทำให้ browser ขอไฟล์ใหม่โดยไม่ต้องล้าง cache เอง

## การทดสอบและเกณฑ์ยอมรับ

### Automated

- เพิ่ม test ยืนยันว่าโหมด skip database ไม่สร้าง `$pdo` และยังโหลด session/auth helpers ได้
- เพิ่ม test ยืนยันว่า `asset_url()` สร้าง URL ที่มี version และรักษา application base path
- ขยาย Vercel deployment test ให้ตรวจ lazy bootstrap และ static cache configuration
- รัน unit, repository smoke, auth, order, admin และ PHP lint เดิมทั้งหมด

### Production checks

- Root ที่ไม่มี session ตอบ `302` ไป `/login.php`
- `/login.php` ตอบ `200` ไม่มี PHP `Deprecated`, `Warning` หรือ header ที่ส่งออกก่อนเวลา
- CSS ตอบ `200` พร้อม cache policy ที่เหมาะสม
- Login ด้วยบัญชี User และ Admin ยังส่งไปคนละ dashboard และยังเห็นข้อมูลตาม role
- หน้า User/Admin ที่สำคัญแสดงผลบน desktop และ mobile โดยไม่มี horizontal overflow ที่ไม่จำเป็น
- วัด response time ใหม่หลัง deploy และรายงานตามจริง ไม่รับประกัน latency คงที่ใน cold start ของ free/serverless plan

## นอกขอบเขตของรอบนี้

- payment gateway, notification, email/SMS และ delivery
- เปลี่ยน provider ฐานข้อมูลหรือย้ายเป็น API architecture ใหม่
- อัปโหลดรูปเมนูหรือสร้างระบบจัดการ media
- ย้าย PHP application ไปเป็น React/Next.js
