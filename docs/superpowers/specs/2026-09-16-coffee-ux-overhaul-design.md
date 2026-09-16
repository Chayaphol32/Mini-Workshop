# Coffee Member Rewards UX Overhaul Design

**วันที่:** 2026-09-16  
**สถานะ:** รอผู้ใช้ตรวจสอบก่อนจัดทำ implementation plan  
**ขอบเขต:** เปลี่ยน UX/UI ของระบบสมาชิกและระบบผู้ดูแลทั้งชุด โดยคง backend, database, authentication, RBAC และ URL เดิมไว้

## เป้าหมาย

ทำให้ Coffee Member Rewards รู้สึกเป็น product สำหรับร้านกาแฟจริง ไม่ใช่หน้าฟอร์ม CRUD ที่ถูกตกแต่งเพิ่ม โดยออกแบบให้เส้นทางหลักของแต่ละ role ชัดเจนตั้งแต่เปิดหน้า:

- User เข้าเมนูสั่งอาหารทันทีหลัง login
- User เห็นตะกร้า ออเดอร์ของตัวเอง และแต้มได้โดยไม่ต้องเดาว่าต้องกดตรงไหน
- Admin เห็นคิวงานและสถานะที่ต้องจัดการก่อนข้อมูลรอง
- Layout ใช้งานได้จริงบนมือถือโดยไม่เกิด page-level horizontal overflow
- ทุกหน้ามี shell, spacing, typography, state และ interaction language ชุดเดียวกัน
- animation ทำให้การเปลี่ยนสถานะรู้สึกมีชีวิต แต่ไม่ทำให้การสั่งอาหารหรือการทำงานของ Admin ช้าลง

## ปัญหาที่พบจากระบบปัจจุบัน

การตรวจหน้าใน viewport ประมาณ 696×912 พบประเด็นที่ต้องแก้ในระดับโครงสร้าง ไม่ใช่แค่สีหรือเงา:

1. Navigation หลักถูกรวมไว้ในปุ่ม “เมนู” ทำให้ผู้ใช้ต้องเปิดเมนูเพื่อรู้ว่าระบบมีหน้าอะไรบ้าง
2. Admin members ใช้พื้นที่ประมาณ 357px กับ stat card ที่เรียงแนวตั้ง ก่อนถึง directory และ filter
3. ตารางสมาชิกมีความกว้างมากกว่าพื้นที่ที่มองเห็น ทำให้เกิด horizontal overflow บนมือถือ
4. หน้า User เริ่มจาก dashboard แม้เจตนาหลักของระบบคือสั่งอาหาร
5. CTA, ปุ่มย้อนกลับ, filter และ action ของแต่ละหน้ามีลำดับความสำคัญไม่คงที่
6. หน้า form และหน้า data management ยังไม่ได้สื่อสถานะ empty, loading, error และ success เป็นระบบเดียวกัน

## หลักฐานและแนวทางจาก design system สากล

แนวทางนี้อิงเอกสารสาธารณะปัจจุบันของหลายทีมที่สร้างระบบใช้งานจริง ไม่ได้อิงเพียงสไตล์ภาพ:

- Apple แนะนำให้ tab bar ใช้สำหรับ section ระดับบน, แสดงอยู่ขณะผู้ใช้เดินทางในระบบ และใช้ label ที่ชัดเจน; sidebar เหมาะกับ collection ระดับบนบนจอที่มีพื้นที่มาก และควรปรับเป็น navigation ที่กะทัดรัดเมื่อพื้นที่จำกัด ([Tab bars](https://developer.apple.com/design/human-interface-guidelines/tab-bars?changes=la_11), [Sidebars](https://developer.apple.com/design/human-interface-guidelines/sidebars?changes=_11))
- IBM Carbon แนะนำให้ global navigation เปลี่ยนตำแหน่งตามขนาดหน้าจอ และเน้น active state, skip link, heading hierarchy และ keyboard navigation ([Global header](https://carbondesignsystem.com/patterns/global-header/), [Tabs](https://carbondesignsystem.com/components/tabs/usage/))
- Shopify รวม surface ของ Admin, Checkout, customer account และ POS ไว้ภายใต้ Polaris เพื่อให้ pattern ระหว่างงานของผู้ใช้คงที่ ([Polaris references](https://shopify.dev/docs/api/polaris))
- GOV.UK แนะนำให้ table ใช้เพื่อเปรียบเทียบข้อมูลที่เป็นแถว/คอลัมน์, มี caption และลดหรือแบ่งข้อมูลเมื่อมีมาก แทนการยัดข้อมูลทั้งหมดลงหน้าจอเดียว ([Table](https://design-system.service.gov.uk/components/table/), [Production](https://design-system.service.gov.uk/get-started/production/))

ข้อสรุปที่นำมาใช้กับโปรเจกต์นี้คือ “navigation ต้องบอกทาง, data screen ต้องช่วยตัดสินใจ, mobile ต้องเปลี่ยน representation เมื่อจำเป็น และ component ต้องคงที่ทั้งระบบ”

## แนวทางที่เลือก

เลือก **Order-first Coffee Club App** โดยใช้ server-rendered PHP เป็นหลักและเพิ่ม progressive enhancement ด้วย CSS/vanilla JavaScript เฉพาะ interaction ที่ช่วยลดขั้นตอน:

- ไม่เปลี่ยนเป็น SPA เพราะระบบมี PHP route และ form POST ที่ทำงานอยู่แล้ว การครอบด้วย client-side state ทั้งหมดจะเพิ่มจุดเสียและทำให้การเข้าถึง/การตรวจสอบยากขึ้น
- ไม่ทำเป็น visual makeover อย่างเดียว เพราะปัญหาหลักอยู่ที่ navigation, information hierarchy, responsive representation และ order flow
- ไม่เพิ่ม UI framework หรือ dependency ใหม่ เพื่อคงความเหมาะสมกับ local PHP/MAMP และ free hosting

## โครงสร้าง shell ใหม่

### Shared app shell

`includes/header.php` และ `includes/footer.php` จะเป็นจุดรวมของ shell เท่านั้น:

- skip link ไป `#main-content`
- brand ที่กดกลับไปยัง role home ได้
- desktop sidebar สำหรับ authenticated user
- mobile bottom navigation ที่มี icon และ label
- top utility bar สำหรับชื่อผู้ใช้, role, แต้ม/สถานะที่เกี่ยวข้อง และ logout
- flash message ที่ประกาศด้วย `role="status"` และไม่บัง content หลัก
- `<main id="main-content">` ที่มี page title หนึ่งระดับอย่างชัดเจน

Navigation ต้องคำนวณ active state จาก route ปัจจุบันและใช้ native `<a>` เป็นหลัก ไม่ใช้ div ที่ทำตัวเป็นปุ่ม

### User navigation

ใช้ 4 destinations ที่มีความหมายชัดเจน:

1. `สั่งอาหาร` → `/user/menu.php` เป็น default route หลัง login
2. `ออเดอร์` → `/user/orders.php`
3. `แต้มของฉัน` → `/user/index.php`
4. `บัญชี` → `/user/profile.php`

บน desktop แสดงเป็น sidebar พร้อมคำอธิบายสั้น ๆ; บน mobile แสดงเป็น bottom navigation แบบ fixed พร้อม safe-area padding และไม่ทับ floating cart/action bar

### Admin navigation

ใช้ 5 destinations ที่สอดคล้องกับงานประจำวัน:

1. `ภาพรวม` → `/admin/index.php`
2. `คิวออเดอร์` → `/admin/orders.php`
3. `สมาชิก` → `/admin/members.php`
4. `เมนู` → `/admin/menu.php`
5. `บัญชีผู้ใช้` → `/admin/users.php`

`/admin/purchase.php` เป็น action เฉพาะทางที่เข้าจากสมาชิกและ quick action ไม่เพิ่มเป็น destination หลัก เพื่อไม่ให้ navigation ยาวเกินจำเป็น

## User experience

### 1. Default order screen: `/user/menu.php`

ลำดับจากบนลงล่าง:

1. compact greeting bar: ชื่อสมาชิก, แต้มปัจจุบัน และลิงก์ดูสิทธิ์
2. page title “สั่งอาหาร” พร้อม subtitle ที่บอกกติกาแต้มแบบสั้น
3. search field ที่ค้นหาชื่อและคำอธิบายเมนูได้ทันที
4. horizontal category scroller: ทั้งหมด, กาแฟ, ชา/เครื่องดื่ม, เบเกอรี่
5. menu grid ที่ใช้ card ขนาดพอดี ไม่ทำให้ emoji เป็น primary visual เพียงอย่างเดียว
6. sticky cart summary: จำนวนแก้ว, ยอดรวม, แต้มที่จะได้รับ และ CTA “ตรวจสอบออเดอร์”

Interaction:

- กด `+` หรือเพิ่มจำนวนแล้ว cart summary update ทันทีโดยไม่ reload
- เมื่อเพิ่มรายการครั้งแรก ให้ cart bar enter ด้วย transform/opacity และประกาศข้อความสั้นผ่าน live region
- CTA เปิด cart panel/bottom sheet ที่สรุปเมนูและมีปุ่ม submit เดิมของระบบ
- submit ยังคง POST ไป `/user/order_create.php` ด้วย `csrf_token` และ `quantities[...]` เดิม ไม่เปลี่ยน contract ของ backend
- ถ้าไม่มีเมนู ให้แสดง empty state พร้อมคำอธิบายและทางกลับไปดูออเดอร์ ไม่แสดงพื้นที่ว่างเปล่า

### 2. User order list: `/user/orders.php`

- ใช้ status filter เป็น segmented control ที่ label ชัด
- แสดงออเดอร์เป็น stacked order cards บนทุกขนาดจอ โดยไม่ใช้ตารางสำหรับข้อมูลที่อ่านเป็น timeline
- card ต้องมี order number, วันที่, จำนวนรายการ, ยอดรวม, status chip และ CTA ดูรายละเอียด
- ออเดอร์ล่าสุดที่ยังดำเนินการอยู่แสดงก่อน พร้อม accent ที่สื่อว่าเป็น “กำลังติดตาม”
- empty state แยกตาม filter และมี CTA กลับไปสั่งอาหาร

### 3. User order detail: `/user/order_detail.php`

- header แสดงเลขออเดอร์, วันที่ และ status ให้อยู่ใน visual group เดียว
- status stepper แสดงเส้นทาง `รอดำเนินการ → กำลังเตรียม → เสร็จแล้ว`; cancelled ใช้ state แยกที่ชัดเจน
- รายการสินค้าเป็น item list ที่อ่านบนมือถือได้ง่าย ไม่ใช้ table กว้าง
- summary แยก subtotal/แต้ม/ยอดรวม และวาง action zone ท้ายเนื้อหา
- ปุ่มยกเลิกใช้ danger style และต้องมี native confirm ก่อน POST
- หลังเปลี่ยนสถานะหรือยกเลิก ให้ flash message และ focus กลับไปยัง heading/status area ที่เหมาะสม

### 4. Member hub: `/user/index.php`

คงหน้านี้ไว้สำหรับข้อมูลของตัวเอง แต่เปลี่ยนบทบาทจาก landing page เป็น member hub:

- แต้มและระดับสมาชิกเป็น hero metric เดียวที่เด่น
- recent orders แสดงสั้น ๆ พร้อมลิงก์ “ดูทั้งหมด”
- reward rule และข้อมูลสมาชิกอยู่ใน section รอง
- CTA หลักเป็น “สั่งอีกครั้ง” ไป `/user/menu.php`

### 5. Profile: `/user/profile.php`

- แยก identity card กับ edit form
- แสดงข้อมูลที่ระบบใช้ติดต่อให้ชัดก่อนฟอร์ม
- input มี label, helper/error text และ focus state ที่มองเห็นได้
- action bar บนมือถือเป็น full-width stack; desktop จัดชิดขวา

## Admin experience

### 1. Operations dashboard: `/admin/index.php`

ลำดับความสำคัญ:

1. header “ภาพรวมร้าน” พร้อมวันที่และ quick action
2. compact KPI row: ออเดอร์เปิด, ยอดขาย, สมาชิก — อยู่แถวเดียวบนจอใหญ่ และ 2-column compact grid บนมือถือ
3. live queue เป็น content หลัก แสดงออเดอร์ที่ต้องลงมือก่อน
4. quick actions ไปคิวออเดอร์, เพิ่มเมนู, เพิ่มสมาชิก และบันทึกยอดซื้อ
5. recent activity/empty state เมื่อไม่มีออเดอร์

KPI จะไม่ใช้การ์ดสูงเต็มแถว และตัวเลขต้องมี label ที่อ่านได้โดยไม่พึ่งสี

### 2. Order operations: `/admin/orders.php`

- page header มีจำนวนผลลัพธ์และ action ที่เกี่ยวข้อง
- filter bar รวม search, status และ clear action ไว้ในพื้นที่เดียว
- desktop ใช้ table เพื่อเปรียบเทียบหลายออเดอร์
- mobile เปลี่ยนเป็น order list card โดยแต่ละ card แสดง customer, total, status และ CTA หลัก
- status filter ต้องรักษา query string ที่ใช้อยู่ (`status`, `q`)
- empty state บอกว่าตัวกรองปัจจุบันไม่พบอะไร และมี “ล้างตัวกรอง”

### 3. Order detail: `/admin/order_detail.php`

- top summary แสดง customer, order number, created time, total และ status
- status action อยู่ใกล้ status ปัจจุบัน ไม่ซ่อนท้ายหน้า
- ใช้ item list ที่อ่านได้บนมือถือ
- ข้อมูลสมาชิกเป็น contextual link ไป `member_detail.php`
- การเปลี่ยน status ยังคง POST และ CSRF flow เดิม
- action ที่ย้อนกลับไม่ได้ต้องใช้ danger styling และ confirm dialog/confirm ที่เข้าถึงได้

### 4. Member directory: `/admin/members.php`

- search เป็น primary control และมี result count บรรทัดเดียวกัน
- summary metrics เหลือ compact strip ไม่เกิน 3 ค่าและไม่เรียงเป็นการ์ดสูงบน mobile
- desktop table มี caption/headers/scope และ action column ที่คงที่
- mobile เปลี่ยนแต่ละสมาชิกเป็น card/list row: avatar, member no, name, phone, points, tier และ overflow action menu หรือ stacked actions
- member name/member no เป็น link ไป detail; actions แยกชัดจาก link เพื่อไม่ให้ click target ซ้อนกัน

### 5. Menu catalog: `/admin/menu.php`

- catalog header มี count และ CTA เพิ่มเมนู
- active/inactive เป็น status chip ที่สื่อทั้งข้อความและสถานะ ไม่พึ่งสีอย่างเดียว
- desktop table แสดง name, description, price, status, updated และ edit
- mobile เปลี่ยนเป็น menu card/list row ที่แสดงราคาและสถานะเหนือรายละเอียดรอง
- create/edit form ใช้ form shell เดียวกันและแสดงผล preview ของ status/price ในระดับที่ไม่ต้องใช้ JavaScript

### 6. Accounts and manual purchase

`/admin/users.php`, `/admin/purchase.php`, `member_create.php`, `member_edit.php`, `menu_create.php` และ `menu_edit.php` ใช้ form shell, error summary, field hint, action footer และ back link ชุดเดียวกัน:

- title บอกงานที่กำลังทำและ entity ที่เกี่ยวข้อง
- field errors อยู่ใกล้ field และเชื่อมด้วย `aria-describedby`
- submit button ระบุผลลัพธ์ เช่น “สร้างบัญชี User”, “บันทึกเมนู”, “ยืนยันบันทึกยอดซื้อ”
- เมื่อไม่มีสมาชิกที่เลือกหรือไม่มีสมาชิกให้สร้างบัญชี ให้แสดงเหตุผลและ action ถัดไป

## Guest experience

`login.php` และ `register.php` ใช้ auth shell แบบเดียวกัน:

- desktop แบ่งเป็น brand/value proposition กับ form
- mobile ให้ form อยู่ก่อนข้อความรอง เพื่อให้เข้าสู่ระบบ/สมัครสมาชิกเร็ว
- primary action เด่นเพียงปุ่มเดียว
- error summary อยู่ก่อน form และ focus ไปที่ error summary เมื่อมี error
- autocomplete, required, input type และ label ต้องคงครบ
- ไม่เปิด animation หนักใน auth screen ที่ผู้ใช้ต้องการทำงานเร็ว

## Visual design system

### Tone

“Roasted espresso meets modern product UI”: warm off-white canvas, deep espresso shell, copper/amber accent, muted green เฉพาะ Admin context และ typography ที่อ่านภาษาไทยได้ดี

ความพรีเมียมจะมาจาก proportion, hierarchy, whitespace, imagery/texture ที่พอดี และ motion ที่มีเหตุผล ไม่ใช่การเพิ่ม gradient/shadow/card ซ้อนกันทุกส่วน

### Tokens

กำหนด token กลางใน `assets/style.css` และใช้ component class แทน inline style ที่ซ้ำ:

- canvas, surface, elevated surface, ink, muted ink, accent, success, warning, danger
- spacing scale 4/8/12/16/24/32/48
- radius scale 10/14/20/28 และ pill เฉพาะ chip/control
- shadow 3 ระดับ: subtle, raised, overlay
- type scale สำหรับ display/page/section/body/meta
- motion duration 140ms/220ms/360ms และ easing ชุดเดียว

ไม่ใช้สีเป็นตัวบอกสถานะเพียงอย่างเดียว; status chip ต้องมี text และ focus/hover/disabled states ครบ

### Component vocabulary

สร้างหรือจัดระเบียบ class ให้มีชื่อที่สื่อหน้าที่:

- `app-shell`, `app-sidebar`, `mobile-nav`, `topbar`
- `page-header`, `page-actions`, `section-header`
- `metric-strip`, `metric`, `status-chip`
- `filter-bar`, `search-field`, `segmented-control`
- `menu-grid`, `menu-item`, `cart-bar`, `cart-sheet`
- `order-list`, `order-card`, `order-status`
- `data-table`, `data-list-mobile`, `empty-state`, `error-summary`
- `form-shell`, `field`, `form-actions`

ชื่อ class เดิมที่ใช้โดย tests หรือหน้าอื่นต้องไม่ถูกลบทิ้งโดยไม่มี compatibility mapping; หาก component เปลี่ยนชื่อ ให้คง class เดิมเป็น alias ในช่วง migration

## Responsive rules

- mobile-first CSS; breakpoint หลักที่ 640px, 900px และ 1200px โดยใช้ `min-width`
- ที่ความกว้างต่ำกว่า 900px ห้ามมี layout ใดทำให้ `body.scrollWidth > body.clientWidth`
- table ที่มีข้อมูลเชิงเปรียบเทียบยังคง scroll ได้ภายใน wrapper เฉพาะเมื่อจำเป็นจริง; รายการ operations ที่อ่านทีละ record ต้องเปลี่ยนเป็น card/list
- bottom navigation และ sticky cart ต้องเว้น `padding-bottom` ผ่าน CSS custom property เพื่อไม่บัง content
- touch target interactive control อย่างน้อย 44×44px
- long Thai text ต้อง wrap ได้ ไม่ใช้ fixed width กับ title, button หรือ cell
- `prefers-reduced-motion: reduce` ปิด transform/stagger และลด duration เหลือ 1ms โดยยังคงสถานะที่สื่อความหมาย
- รองรับ safe-area inset บนอุปกรณ์ที่มี home indicator

## Accessibility requirements

ใช้ native semantics ก่อน ARIA:

- ทุก interactive control มี accessible name
- ทุก input/select/textarea มี label ที่เชื่อมกับ id
- icon-only control มี `aria-label`; icon ตกแต่งใช้ `aria-hidden="true"`
- navigation มี landmark label และ active link ที่สื่อ current page
- expandable cart/menu ใช้ native button/details หรือมี `aria-expanded`/`aria-controls`
- dialog/bottom sheet ต้องมี focus management, Escape close และคืน focus ไป trigger
- error field ใช้ `aria-invalid` และ `aria-describedby`; summary ใช้ `role="alert"` เมื่อจำเป็น
- order/status update ที่เกิดจาก JavaScript ประกาศใน live region แต่ไม่ใช้ toast เป็นช่องทางเดียวของ critical feedback
- heading hierarchy ต่อเนื่องจาก h1 ไป h2/h3
- table ใช้ caption, `th`, `scope` และไม่ใช้ table เป็น layout
- focus-visible ต้องเห็นชัดบน dark และ light surface

## Motion and perceived performance

animation ที่อนุญาต:

- page shell fade/slide สั้น ๆ ตอน route render
- nav active indicator แบบ transform ที่ไม่ทำให้ layout reflow
- menu card hover/press ที่ใช้ transform และ opacity
- cart bar enter/exit และจำนวนสินค้าเปลี่ยนแบบ scale เล็กน้อย
- admin status transition ที่เน้น chip/queue item
- stagger เฉพาะรายการแรกบน viewport และไม่เกิน 5 รายการ

ข้อจำกัด:

- ห้ามใช้ animation ที่ทำให้ปุ่ม submit ดีเลย์
- ห้าม animate ความกว้าง/ความสูงของทั้งตารางหรือ layout หนัก ๆ
- หลีกเลี่ยง `box-shadow` ที่เปลี่ยนทุก frame
- ใช้ CSS compositor properties เป็นหลัก (`transform`, `opacity`)
- ลด/ปิด motion เมื่อผู้ใช้ตั้งค่า reduced motion

## Data and security invariants

การ redesign ไม่เปลี่ยน business contract ต่อไปนี้:

- PHP route และ query parameter เดิมยังรองรับทั้งหมด
- user เห็นเฉพาะ member/order ของตนเอง; admin เห็นข้อมูลที่ role อนุญาต
- `require_role`, CSRF validation, prepared statements และ password hashing ต้องอยู่ครบ
- `/user/order_create.php` ยังคงรับ `quantities[...]` และตรวจ active menu/quantity ฝั่ง server
- status transition ของ order ยังคงผ่าน repository/helper เดิม ไม่ย้าย logic ไป JavaScript
- ราคาย้อนหลังและแต้มสะสมต้องคำนวณจาก backend เดิม
- ไม่มีการใส่ข้อมูลส่วนตัวหรือ token ลงใน localStorage, URL ใหม่ หรือ client-side log

## Testing and acceptance criteria

### Automated

หลัง implementation ต้องผ่าน:

- PHP lint ทุกไฟล์ที่แก้
- test suite เดิมทั้งหมด รวม auth, repository, order, render shell และ deploy tests
- HTTP smoke ของ guest, user และ admin routes
- role isolation: user เปิด admin route ได้ 403 และ admin เปิด user-only route ได้ 403 ตาม policy เดิม
- order flow: user สร้าง/ดู/ยกเลิก order ได้ และ admin เห็นการเปลี่ยนแปลง
- helper compatibility เมื่อรันด้วย PHP ที่ไม่มี mbstring

### Browser verification

ตรวจด้วย browser ที่ viewport อย่างน้อย:

- 390×844 (mobile)
- 768×1024 (tablet)
- 1440×900 (desktop)

สำหรับแต่ละ role ตรวจ:

- login → role home ถูกต้อง โดย user ไปเมนูทันที
- navigation active state และ back links
- menu search/filter/quantity/cart/submit
- order list/detail/status/cancel
- admin queue, search/filter, member detail, menu edit และ account actions
- empty, error, flash success และ disabled/loading states
- ไม่มี page-level horizontal overflow และไม่มี console error ที่เกิดจากแอป
- keyboard tab order, visible focus, Escape ของ overlay และ reduced-motion mode

### Definition of done

งาน UX overhaul ถือว่าเสร็จเมื่อทุกหน้าใน route map ใช้ shell/component language เดียวกัน, primary task ของ User และ Admin ทำได้โดยไม่ต้องค้นหา navigation, mobile representation ไม่บังคับเลื่อนตารางทั้งหน้า, automated tests เดิมผ่าน และ browser verification ผ่านทั้งสาม viewport โดยไม่ลดสิทธิ์ความปลอดภัยหรือเปลี่ยนผลลัพธ์ธุรกิจ

## Rollout order

implementation plan ควรแบ่งเป็นลำดับที่ตรวจได้:

1. shared tokens, shell, role-aware navigation และ responsive primitives
2. User order-first flow: menu/cart, orders, order detail, member hub/profile
3. Admin operations: dashboard, orders, members, menu, forms/accounts
4. guest auth shell และ cross-page states
5. accessibility, motion/performance polish และ browser verification

แต่ละช่วงต้องรักษา route/backend contract และรัน test gate ก่อนเริ่มช่วงถัดไป
