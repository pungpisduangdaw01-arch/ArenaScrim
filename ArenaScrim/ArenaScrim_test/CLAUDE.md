# CLAUDE.md — กฎการทำงานสำหรับ Claude Code ในโปรเจกต์ ArenaScrim

ArenaScrim เป็นเว็บแอป PHP สำหรับบริหารทีม RoV และบันทึก/วิเคราะห์ผลการซ้อม (โครงงานนักศึกษา)

## 1. อ่านก่อนลงมือทุกครั้ง

| เอกสาร | ใช้ทำอะไร |
|---|---|
| `docs/PROJECT_SCOPE.md` | ขอบเขตที่อนุมัติ (A/B/C) และสิ่งที่อยู่นอกขอบเขต |
| `docs/DECISIONS.md` | การตัดสินใจที่ยืนยันแล้ว และ **TBD ที่ยังห้ามเดา** |
| `docs/ARCHITECTURE.md` | โครงสร้าง ตาราง สิทธิ์ และสูตรสถิติของระบบ**ปัจจุบัน** |
| `docs/IMPLEMENTATION_PLAN.md` | ระยะงานและเงื่อนไขก่อนเริ่ม |
| `docs/TEST_PLAN.md` | test case และสถานะ |

`docs/project-planning/`, `AUDIT_REPORT.md` และ `README.md` เป็นเอกสารอ้างอิงรุ่นก่อน ถ้าขัดกับเอกสารข้างบน ให้รายงาน อย่าเลือกเชื่อเอง

## 2. กฎหลัก

1. **ห้ามเดา** ถ้ารายละเอียดเป็น TBD ใน `DECISIONS.md` หรือไม่มีหลักฐานในโค้ด ให้ถามเจ้าของโครงการ ห้ามตัดสินใจแทน
2. **ห้ามทำเกินขอบเขต** ทำเฉพาะงานที่ได้รับมอบหมายและอยู่ใน `PROJECT_SCOPE.md` ถ้าพบปัญหาอื่น ให้บันทึกและรายงาน ห้ามแก้ทันที
3. **ห้ามเริ่มระยะงานใหม่เอง** ทำตาม `IMPLEMENTATION_PLAN.md` และหยุดรอการอนุมัติเมื่อจบงานที่ได้รับมอบหมาย
4. **ฐานข้อมูลจริง (`arenascrim_db`) ใช้ได้แค่อ่าน** ห้าม INSERT/UPDATE/DELETE/ALTER/DROP และห้ามทดสอบการเขียนข้อมูลกับ DB จริง
5. **DB ทดสอบ:** อนุญาตให้ใช้ `arenascrim_test` ที่สร้างจากสำเนาข้อมูลจริง และสำเนาโปรเจกต์ที่ชี้ไปยัง DB นั้น (D-08) **ห้ามชี้โค้ดหลักไปที่ DB ทดสอบ** และห้ามเขียนข้อมูลลง `arenascrim_db`
6. **ห้ามเพิ่ม framework หรือ library และห้ามเปลี่ยนสถาปัตยกรรม** (D-03)
7. **ห้ามเขียนทับหรือลบเอกสารหรือไฟล์เดิม**โดยไม่ได้รับอนุญาต
8. **ห้ามอ้างว่าทดสอบผ่าน**โดยไม่มีหลักฐาน การทดสอบที่ยังไม่รันต้องเป็น `NOT RUN` หรือ `BLOCKED`
9. แยกข้อความในเอกสารเป็น **FACT / INFERENCE / PROPOSAL / TBD** เสมอ
10. **หลังเปลี่ยนระบบทุกครั้ง ต้องอัปเดตเอกสารที่เกี่ยวข้อง** (ARCHITECTURE, TEST_PLAN, DECISIONS, README) ในงานเดียวกัน

## 3. สภาพแวดล้อม

- XAMPP บน macOS · PHP 8.2.4 · MariaDB 10.4.28 · Apache + mod_php
- URL: `http://localhost/ArenaScrim/ArenaScrim/`
- PHP CLI: `/Applications/XAMPP/xamppfiles/bin/php`
- MySQL CLI: `/Applications/XAMPP/xamppfiles/bin/mysql -uroot arenascrim_db` (**ใช้แค่ SELECT**)
- ไม่มี automated test · ตรวจ syntax ด้วย:
  ```bash
  for f in $(find . -name '*.php' -not -path './vendor/*'); do /Applications/XAMPP/xamppfiles/bin/php -l $f | grep -v "No syntax errors"; done
  ```

## 4. รูปแบบโค้ดที่ต้องคงไว้ (FACT จากโค้ดปัจจุบัน)

- PHP แบบ procedural 1 ไฟล์ต่อ 1 หน้า เริ่มด้วย `require_once '../config/db.php';` (ได้ `$pdo` และฟังก์ชันใน `config/functions.php`)
- หน้าที่ต้องล็อกอิน: `$user_id = require_login();` · หน้า admin: `require_admin();` ต้องเรียก**ก่อน** `include '../includes/header.php'`
- สิทธิ์ระดับทีม: `get_led_team($pdo, $user_id)` (หัวหน้า) / `get_user_team()` (ดูข้อมูล) · การลบต้อง JOIN `teams.leader_id = ?`
- SQL: **prepared statement เท่านั้น** ห้ามต่อค่าจากผู้ใช้เข้าไปใน SQL · ส่วนที่ต้องสร้าง SQL แบบไดนามิกต้องมาจาก whitelist หรือค่าคงที่
- แสดงผล: ทุกค่าที่มาจากผู้ใช้หรือ DB ต้องผ่าน `e()` · ใช้ `json_encode()` สำหรับข้อมูลใน `<script>`
- ฟอร์ม POST ทุกฟอร์มต้องมี `<?= csrf_field() ?>` · action ต้องเรียก `verify_csrf($back)` · ปุ่มลบ/เปลี่ยนสถานะใช้ `post_button()` **ห้ามใช้ลิงก์ GET สำหรับการเขียนข้อมูล**
- หลังประมวลผล: `redirect_with($url, 'success'|'error', 'ข้อความภาษาไทย')` และหน้าปลายทางเรียก `show_flash()`
- ตรวจข้อมูลฝั่ง server เสมอ: ความยาวตามขนาดคอลัมน์, whitelist (`MEMBER_ROLES`, `RANKS`, `HERO_ROLES`), เพดานตัวเลข และ `clean_url()` สำหรับลิงก์
- Error จาก DB: `error_log()` + ข้อความทั่วไป **ห้ามแสดง `$e->getMessage()` ให้ผู้ใช้เห็น**
- คอมเมนต์เป็นภาษาไทย สั้น ๆ และมีความหนาแน่นเท่าโค้ดรอบข้าง · สไตล์ส่วนใหญ่เป็น inline ตามรูปแบบเดิม

## 5. การเปลี่ยน Schema (กลุ่ม C)

- ต้องผ่านระยะที่ 3 (ออกแบบ) และได้รับอนุมัติก่อน
- เขียนเป็นไฟล์ migration แยกใน `database/` และอัปเดต `arenascrim_db.sql` ให้ติดตั้งใหม่ได้
- ทดลองบน DB ทดสอบก่อนเสมอ และตรวจจำนวนแถวก่อนและหลัง
- การรันกับ DB จริงต้องได้รับอนุมัติเป็นรายครั้ง และต้องสำรองก่อน

## 6. เมื่อจบงาน

รายงาน:
- ไฟล์ที่เปลี่ยน
- test ที่รัน พร้อมผลจริง
- test ที่ยังไม่ได้รัน
- เอกสารที่อัปเดต
- TBD ใหม่ที่พบ

จากนั้น**หยุดรอการตรวจทาน**
