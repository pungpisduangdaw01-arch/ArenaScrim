# สรุปผล Audit โปรเจกต์ ArenaScrim

> **หมายเหตุ (10 ต.ค. 2026):** รายงานนี้เป็นผลการ audit รอบ 9 ต.ค. แก้ตัวเลขให้ตรงตาม `docs/DECISIONS.md` X-02..X-05 แล้ว · เอกสารหลักของโครงการปัจจุบันอยู่ใน `docs/` และผลทดสอบล่าสุดอยู่ใน `docs/TEST_PLAN.md`

- **ฉบับที่:** 2 (ปรับปรุงหลังแก้ไข) — 9 ต.ค. 2026
- **ผลประเมินปัจจุบัน:** **READY WITH MINOR FIXES** — แก้ช่องโหว่ระดับ High และ Medium ในโค้ดครบแล้ว ที่เหลือคือ responsive, บทความวิจัย และเรื่องเล็ก
- **ผลประเมินฉบับที่ 1 (ก่อนแก้):** NEEDS FIXES
- **ขอบเขต:** อ่านโค้ดครบทุกไฟล์ (PHP 45, JS 2, SQL 2, CSS 10) และบทความใน `docs/`
- **สิ่งที่รันจริง:** `php -l`, HTTP request (ไม่ล็อกอิน และทดสอบ CSRF), query DB แบบอ่านอย่างเดียว, PHP harness ออฟไลน์, render หน้าเว็บด้วยบัญชีจริงแบบอ่านอย่างเดียว
- **ยังไม่ได้ทำ:** ทดสอบ UI บนเบราว์เซอร์, รัน flow ที่เขียนลง DB (สร้างทีม บันทึกผล ลบ), ลองโจมตีช่องโหว่จริง

---

## 1. ภาพรวมระบบ

| รายการ | สิ่งที่พบ | สถานะ |
|---|---|---|
| เทคโนโลยี | PHP 8.2.4 (procedural), MariaDB 10.4.28, Chart.js, Font Awesome | ผ่าน |
| โครงสร้าง | หน้าเว็บ (`user/`, `admin/`, `scrim/`) แยกจากตัวประมวลผล (`actions/`) และมีฟังก์ชันกลางใน `config/functions.php` | ผ่าน |
| ฐานข้อมูล | 7 ตาราง เชื่อมกันด้วย FK แบบ `ON DELETE CASCADE` และ schema ตรงกับ DB จริงทุกคอลัมน์ | ผ่าน |
| การติดตั้ง | มี `README.md` อธิบายการติดตั้ง การตั้ง admin และการใช้งานแล้ว | ผ่าน |
| Dependency | ติดตั้ง `dompdf` ไว้ (12MB) แต่ไม่มีโค้ดไหนเรียกใช้ | ข้อเสนอแนะ |

**จุดแข็ง**
- ใช้ PDO prepared statement ทุก query จึงไม่พบ SQL Injection
- เก็บรหัสผ่านด้วย `password_hash` และเรียก `session_regenerate_id` ตอนล็อกอิน
- ตรวจความเป็นเจ้าของข้อมูลด้วย JOIN กับ `leader_id` ทุกครั้งก่อนลบ และตรวจสิทธิ์จาก DB ทุกหน้า
- การบันทึก แก้ไข และลบทุกอย่างต้องเป็น POST ที่มี CSRF token
- ห่อการบันทึกแมตช์ไว้ใน transaction
- escape ข้อมูลก่อนแสดงผลด้วย `e()` ครบทุกจุด

---

## 2. Issue Register

| ID | Severity | ปัญหา | สถานะ |
|---|---|---|---|
| AUD-001 | 🔴 High | Stored XSS: ชื่อทีมคู่แข่งถูกพิมพ์ลง `<title>` โดยไม่ escape | ✅ แก้แล้ว |
| AUD-002 | 🔴 High | ไม่มี CSRF protection และการลบ/เปลี่ยนสิทธิ์ทำผ่านลิงก์ GET | ✅ แก้แล้ว |
| AUD-003 | 🟠 Medium | role ใน session ไม่อัปเดตเมื่อ admin เปลี่ยนสิทธิ์หรือลบผู้ใช้ | ✅ แก้แล้ว |
| AUD-004 | 🟠 Medium | แก้ไขแมตช์เก่าที่ใช้ฮีโร่ซึ่งถูกลบไปแล้ว ทำให้สถิติผู้เล่นหาย | ✅ แก้แล้ว |
| AUD-005 | 🟠 Medium | ขาด validation ฝั่ง server ทำให้ได้ HTTP 500 หรือ error ดิบ | ✅ แก้แล้ว |
| AUD-006 | 🟠 Medium | ไม่รองรับมือถือ: ไม่มี `@media` และใช้ grid ที่จำนวนคอลัมน์ตายตัว | ⏳ ยังไม่แก้ (ต้องดูบนเบราว์เซอร์) |
| AUD-007 | 🟠 Medium | บทความวิจัยยังมี placeholder และมีข้อความที่ไม่ตรงกับระบบ | ⏳ ยังไม่แก้ (ผู้วิจัยต้องแก้เอง) |
| AUD-008 | 🟡 Low | cookie ไม่มี HttpOnly/SameSite, เปิด directory listing และไม่มี security header | ✅ แก้แล้ว |
| AUD-009 | 🟡 Low | แสดงข้อความ error ของ DB ให้ผู้ใช้เห็น | ✅ แก้แล้ว (10 ต.ค. 2026 — ดู `docs/TEST_PLAN.md` T-A4) |
| AUD-010 | 🟡 Low | ไม่จำกัดจำนวนครั้งที่ล็อกอินผิด และรหัสผ่านขั้นต่ำแค่ 6 ตัว | ⏳ ยังไม่แก้ (ระบุเป็นข้อจำกัดใน README แล้ว) |
| AUD-011 | 🟡 Low | ไม่มี README และ migration ทำให้สับสน | ✅ แก้แล้ว |
| AUD-012 | ⚪ Info | มีไฟล์และโค้ดที่ไม่ได้ใช้ และข้อมูลบางส่วนไม่สม่ำเสมอ | 🟡 แก้บางส่วน (รวมตำแหน่ง/แรงก์เป็นค่าคงที่แล้ว) |

---

## 3. รายละเอียดการแก้ไข

### ✅ AUD-001 — Stored XSS (High)

- **ปัญหาเดิม:** `includes/header.php:9` พิมพ์ `$page_title` โดยไม่ escape และ `user/match-detail.php:49` นำชื่อทีมคู่แข่งที่ผู้ใช้กรอกเองใส่ลงไป หัวหน้าทีมจึงฝัง script ไว้เพื่อขโมย session ของ admin ได้
- **การแก้ไข:** `<title><?= e($page_title ?? '...') ?></title>`
- **ยืนยัน:** harness ใช้ payload `</title><script>alert(document.cookie)</script>` แล้วได้ผลเป็น `&lt;/title&gt;&lt;script&gt;...` ซึ่งแสดงเป็นข้อความธรรมดา

### ✅ AUD-002 — CSRF (High)

- **ปัญหาเดิม:** การลบและเปลี่ยนสิทธิ์ทำผ่านลิงก์ GET และไม่มี token มีช่องทางโจมตีจากในระบบเองด้วย: ใส่ URL คำสั่งของ admin เป็น "ลิงก์รูปสกรีนช็อต" แล้วรอให้ admin เปิดดูแมตช์นั้น ผู้โจมตีก็จะถูกเลื่อนขึ้นเป็น admin
- **การแก้ไข:**
  - เพิ่ม `csrf_token()`, `csrf_field()`, `verify_csrf($back)` และ `post_button()` ใน `config/functions.php`
  - ใส่ `csrf_field()` ในฟอร์ม POST ทั้ง 10 ฟอร์ม (login, register, สร้าง/แก้ทีม, เพิ่มสมาชิก, ข้อมูลผู้เล่น, โปรไฟล์, บันทึกผล, โพสต์ scrim, จัดการฮีโร่)
  - เปลี่ยนลิงก์ลบ/เปลี่ยนสถานะ 11 จุดเป็นฟอร์ม POST ผ่าน `post_button()` โดยหน้าตาและข้อความยืนยันยังเหมือนเดิม
  - ทุกไฟล์ใน `actions/` ที่แก้ข้อมูลเรียก `verify_csrf()` และอ่าน `id` จาก `$_POST`
  - ลิงก์ที่เหลือมีแค่ logout และ export PDF ซึ่งไม่แก้ข้อมูล
- **ยืนยัน (HTTP จริง):** ไม่มี token ถูกปฏิเสธ, token ผิดถูกปฏิเสธ, token ถูกผ่านไปถึงขั้นตรวจรหัสผ่าน และไม่มีหน้าใดเหลือลิงก์ GET ที่แก้ข้อมูล

### ✅ AUD-003 — สิทธิ์ใน session ค้าง (Medium)

- **ปัญหาเดิม:** `require_admin()` เช็กแค่ `$_SESSION['role']` ซึ่งตั้งค่าครั้งเดียวตอนล็อกอิน
- **การแก้ไข:** `require_login()` อ่าน role จาก DB ทุก request และล้าง session แล้วส่งกลับหน้าล็อกอินถ้าบัญชีถูกลบ
- **ยืนยัน:** render `admin/users.php` ด้วยบัญชี user ทั่วไปแล้วถูก redirect

### ✅ AUD-004 — สถิติหายเมื่อแก้แมตช์ที่ใช้ฮีโร่ซึ่งถูกลบ (Medium)

- **การแก้ไข:** `hero_select()` ใน `user/match-add.php` เพิ่มฮีโร่ที่ถูกลบไปแล้วเป็นตัวเลือกที่ถูกเลือก พร้อมข้อความ "(ถูกลบจากรายการ)"
- **ยืนยัน:** ทดสอบฟังก์ชันแยกได้ผลตามที่คาด: ฮีโร่ปกติถูกเลือก 1 ตัว, ฮีโร่ที่ถูกลบยังคงอยู่, ค่าว่างไม่มีตัวเลือกใดถูกเลือก

### ✅ AUD-005 — Validation ฝั่ง server (Medium)

| จุด | การแก้ไข |
|---|---|
| `user/profile-edit.php` | ตรวจความยาว nickname (100), fullname/open_id/full_id (255) และเพิ่ม `maxlength` ในฟอร์ม |
| `actions/team-process.php` | ตรวจ role สมาชิกกับ `MEMBER_ROLES`, ตรวจความยาว Line ID (100) และลิงก์ (255) |
| `actions/scrim-process.php` | ตรวจแรงก์กับ `RANKS` และ note ≤ 1,000 ตัวอักษร |
| `actions/match-process.php` | จำกัด K/D/A ≤ 999, gold ≤ 9,999,999, damage ≤ 99,999,999, objects ≤ 99, ชื่อฮีโร่ ≤ 50, note ≤ 5,000, URL ≤ 255 และแสดงข้อความ error ทั่วไปแทน error ของ DB (บันทึกรายละเอียดลง `error_log`) |
| `user/member-info.php` | ตรวจ member_note ≤ 2,000 ตัวอักษร |
| `config/functions.php` | เพิ่มค่าคงที่ `MEMBER_ROLES` และ `RANKS` ใช้ร่วมกันทั้งฟอร์มและฝั่ง server |

### ✅ AUD-008 — ค่าตั้ง session และ server (Low)

- **การแก้ไข:** เพิ่ม `.htaccess` ที่ root เพื่อปิด directory listing, บล็อกไฟล์ `.md .json .lock .docx`, ตั้ง cookie เป็น `HttpOnly` / `SameSite=Lax`, เปิด `use_strict_mode` และเพิ่ม `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`
- **ยืนยัน (curl):** `Set-Cookie: ...; HttpOnly; SameSite=Lax`, header ครบ, `docs/` `vendor/` `composer.json` `*.md` ได้ 403 และหน้าเว็บกับ CSS ยังได้ 200

### ✅ AUD-011 — README (Low)

- **การแก้ไข:** เพิ่ม `README.md` ที่มีขั้นตอนติดตั้ง วิธีตั้ง admin วิธีใช้งาน โครงสร้างโฟลเดอร์ แนวทางด้านความปลอดภัย และข้อจำกัด รวมถึงระบุว่า `migration_member_info.sql` ใช้กับ DB รุ่นเก่าเท่านั้น

---

## 4. ปัญหาที่ยังเหลือ และวิธีแก้

### ⏳ AUD-006 — Responsive (Medium, Needs Verification)

- **ปัญหา:** ไม่มี `@media` และใช้ grid แบบ inline ที่จำนวนคอลัมน์ตายตัว เช่น `repeat(6, 1fr)` ใน `match-history.php:106` และ `1fr 1fr` ใน `admin/index.php:52`
- **วิธีแก้:**
  - ใช้ class กลาง `grid-template-columns: repeat(auto-fit, minmax(150px, 1fr))` แทน
  - ครอบตาราง admin ด้วย `overflow-x: auto`
  - เพิ่ม `@media (max-width: 768px)` ให้ navbar
- **ตรวจ:** เปิด DevTools ที่ความกว้าง 375px

### ⏳ AUD-007 — บทความวิจัย (Medium)

- ยังมี placeholder ค้างอยู่: "ชื่อ นามสกุล ผู้วิจัย1", "xx คน", "x.xx", "ด้าน........"
- บทความเขียนว่า "ออกรายงาน PDF" แต่ระบบใช้ `window.print()` ควรเขียนว่า "พิมพ์หรือบันทึกเป็น PDF ผ่านเบราว์เซอร์"
- ตารางที่ 3 (20 กรณีทดสอบ) ควรรันจริงและเก็บภาพหน้าจอไว้เป็นหลักฐาน
- ถ้าต้องการ เพิ่มเรื่อง CSRF token ในหัวข้อการพัฒนาระบบได้แล้ว เพราะระบบมีแล้ว
- หัวข้อ 1.8 ยังไม่ได้กล่าวถึงฟีเจอร์ "จัดการฮีโร่"

### ✅ AUD-009 — Error disclosure (Low) — แก้แล้ว 10 ต.ค. 2026

- จุดที่เหลือเดิม: `config/db.php:14` (`die(... $e->getMessage())`) และ `user/member-stats.php` (`$db_error`)
- **การแก้ไข:** บันทึกด้วย `error_log()` และแสดงข้อความทั่วไปให้ผู้ใช้ (db.php ตอบ HTTP 503)
- **ยืนยัน:** T-A4-01, T-A4-02 PASS บน DB ทดสอบ (ก่อนแก้ FAIL)

### ⏳ AUD-010 — Login rate limit (Low)

- ระบุไว้เป็นข้อจำกัดใน README แล้ว ถ้าต้องการแก้ ให้เพิ่มตัวนับจำนวนครั้งที่ล็อกอินผิดใน session หรือใน DB

### 🟡 AUD-012 — Cleanup (Info)

- ไฟล์ redirect ของระบบเก่า 10 ไฟล์
- `style.css` และ `dashboard-layout.css` ไม่ถูกเรียกใช้, `main.js` เป็นไฟล์ว่าง, dompdf ไม่ได้ใช้
- คอลัมน์ `team_members.position` และ `match_player_stats.hero` ไม่ได้ใช้
- โหลด Chart.js โดยไม่ระบุเวอร์ชัน (ควรระบุเป็น `chart.js@4.4.1`)
- ✅ ตำแหน่งสมาชิกและแรงก์รวมเป็นค่าคงที่ใน `functions.php` แล้ว

---

## 5. Security Summary

| หัวข้อ | ผล |
|---|---|
| SQL Injection | ✅ ไม่พบ |
| XSS | ✅ แก้แล้ว (AUD-001) |
| CSRF | ✅ แก้แล้ว (AUD-002) |
| Authentication | ✅ hash รหัสผ่านและ regenerate session แล้ว ⚠️ แต่ไม่มี rate limit (AUD-010) |
| Authorization / IDOR | ✅ ตรวจความเป็นเจ้าของครบ และอ่านสิทธิ์จาก DB ทุก request |
| Session cookie | ✅ `HttpOnly`, `SameSite=Lax`, `use_strict_mode` |
| Security headers | ✅ `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` |
| Directory listing / ไฟล์ภายใน | ✅ ได้ 403 |
| Input validation | ✅ ตรวจความยาว, whitelist และเพดานตัวเลขครบ (AUD-005) |
| Error disclosure | ✅ แก้แล้ว (AUD-009) |
| File upload | ไม่มีฟีเจอร์นี้ (รับเป็น URL แทน และบังคับ http/https) |

---

## 6. ผลทดสอบที่รันจริง

| Test ID | รายการ | Expected | Actual | Status |
|---|---|---|---|---|
| TC-RUN-01 | `php -l` ทุกไฟล์ (หลังแก้) | ไม่มี error | ไม่มี error | PASS |
| TC-RUN-02 | เข้า 11 URL ที่ต้องล็อกอินโดยไม่มี session | 302 ไป login | 302 | PASS |
| TC-RUN-03 | GET `/database/arenascrim_db.sql` | 403 | 403 | PASS |
| TC-RUN-04 | GET `/docs/`, `/vendor/`, `composer.json`, `*.md` | 403 | 403 | PASS (ก่อนแก้ FAIL) |
| TC-RUN-05 | XSS harness ที่ `<title>` | ถูก escape | ถูก escape | PASS (ก่อนแก้ FAIL) |
| TC-RUN-06 | diff schema กับ DB จริง | ตรงกัน | ตรงกัน | PASS |
| TC-RUN-07 | POST login โดยไม่มี CSRF token | ถูกปฏิเสธ | ถูกปฏิเสธ ("คำขอไม่ถูกต้องหรือหมดอายุ") | PASS |
| TC-RUN-08 | POST login ด้วย token ผิด | ถูกปฏิเสธ | ถูกปฏิเสธ | PASS |
| TC-RUN-09 | POST login ด้วย token ถูก + รหัสผ่านผิด | ผ่าน CSRF แล้วแจ้งรหัสผ่านผิด | แจ้ง "Username/Email หรือ รหัสผ่านไม่ถูกต้อง" | PASS |
| TC-RUN-10 | Session cookie และ security headers | มี `HttpOnly`, `SameSite`, header ครบ | ครบ | PASS |
| TC-RUN-11 | render 17 ครั้ง ครอบคลุม 15 หน้าที่แก้ ด้วยบัญชีจริง (อ่านอย่างเดียว) | ไม่มี error และไม่มีลิงก์ GET ที่แก้ข้อมูล | ผ่านทุกหน้า | PASS |
| TC-RUN-12 | user ทั่วไปเปิด `admin/users.php` | ถูก redirect | ถูก redirect | PASS |
| TC-RUN-13 | `hero_select()` กับฮีโร่ที่ถูกลบ | ยังถูกเลือกอยู่ | ยังถูกเลือกอยู่ | PASS |

## 7. Test Cases ที่ต้องรันบนเบราว์เซอร์ (ให้เก็บภาพหน้าจอเป็นหลักฐาน)

> ก่อนทดสอบให้**รีเฟรชทุกหน้า**ที่เปิดค้างไว้ เพราะหน้าที่เปิดก่อนแก้ยังไม่มี CSRF token

| Test ID | ประเภท | ขั้นตอน | Expected | Status |
|---|---|---|---|---|
| TC-AUTH-01 | Positive | สมัครด้วยข้อมูลถูกต้อง | สร้างบัญชี และรหัสผ่านใน DB เป็น hash | NOT RUN |
| TC-AUTH-02 | Negative | ส่งฟอร์มสมัครว่าง (ปิด `required`) | แจ้ง "กรอกให้ครบ" | NOT RUN |
| TC-AUTH-03 | Boundary | รหัสผ่าน 5 ตัว และ 6 ตัว | 5 ถูกปฏิเสธ, 6 ผ่าน | NOT RUN |
| TC-AUTH-04 | Negative | สมัครด้วย username/email ที่มีอยู่แล้ว | ระบบปฏิเสธ | NOT RUN |
| TC-TEAM-01 | Negative | สร้างทีมที่ 2 ด้วยบัญชีเดิม | ระบบปฏิเสธ | NOT RUN |
| TC-TEAM-02 | Validation | ใส่ Discord = `javascript:alert(1)` | ระบบปฏิเสธ | NOT RUN |
| TC-TEAM-03 | Regression | เพิ่มสมาชิก แล้วลบสมาชิก (ปุ่ม POST ใหม่) | ทำงานและมีข้อความยืนยัน | NOT RUN |
| TC-TEAM-04 | Regression | ยุบทีม (ปุ่ม POST ใหม่) | ทีมถูกลบ | NOT RUN |
| TC-MATCH-01 | Integration | บันทึกผลพร้อม gold แล้วดู % อัตโนมัติ | ผลรวม % ≈ 100 และสถิติถูกบันทึกครบ | NOT RUN |
| TC-MATCH-02 | Validation | ใส่วันที่ในอนาคต | ระบบปฏิเสธ | NOT RUN |
| TC-MATCH-03 | Boundary | % = 150, MVP = 20, kills = 5000 | บันทึกเป็น 100, 16 และ 999 | NOT RUN |
| TC-MATCH-04 | Authorization | แก้ `member_id` เป็นสมาชิกทีมอื่น | ไม่บันทึกแถวนั้น | NOT RUN |
| TC-MATCH-05 | Regression | ลบแมตช์จากหน้าประวัติ และจากหน้ารายละเอียด | ลบได้ทั้งสองหน้า | NOT RUN |
| TC-MATCH-06 | Regression | ลบฮีโร่แล้วแก้ไขแมตช์เก่าที่ใช้ฮีโร่นั้น | สถิติยังอยู่ | NOT RUN |
| TC-SCRIM-01 | Validation | โพสต์ด้วยเวลาที่ผ่านมาแล้ว | ระบบปฏิเสธ | NOT RUN |
| TC-SCRIM-02 | Regression | ปิดประกาศ และลบประกาศ | สถานะเปลี่ยน และประกาศถูกลบ | NOT RUN |
| TC-ADM-01 | Authorization | user ทั่วไปเปิดหน้า admin | redirect | NOT RUN |
| TC-ADM-02 | Regression | ลดสิทธิ์ admin ในขณะที่เขายังล็อกอินอยู่ แล้วให้เขารีเฟรช | ถูก redirect ออกจากหน้า admin | NOT RUN |
| TC-ADM-03 | Regression | admin ลบผู้ใช้ ลบทีม ลบประกาศ ลบฮีโร่ | ทำงานทุกปุ่ม | NOT RUN |
| TC-SEC-01 | Security | ใช้ opponent_name = `</title><script>alert(1)</script>` | แสดงเป็นข้อความ ไม่มี alert | NOT RUN |
| TC-SEC-02 | Security | เปิด URL ลบแบบ GET เดิม เช่น `match-delete.php?id=1` | ไม่ลบ และแจ้ง "คำขอไม่ถูกต้อง" | NOT RUN |
| TC-VAL-01 | Validation | nickname ยาว 150 ตัวอักษร (ปิด maxlength) | แจ้ง error ที่อ่านเข้าใจได้ | NOT RUN |
| TC-UI-01 | Usability | เปิดหน้าต่าง ๆ ที่ความกว้าง 375px | อ่านได้และไม่ล้นจอ | NOT RUN (คาดว่า FAIL จนกว่าจะแก้ AUD-006) |

---

## 8. ไฟล์ที่ถูกแก้ไขในรอบนี้

| ประเภท | ไฟล์ |
|---|---|
| ใหม่ | `.htaccess`, `README.md` |
| ฟังก์ชันกลาง | `config/functions.php`, `includes/header.php` |
| Actions | `auth-process.php`, `team-process.php`, `match-process.php`, `match-delete.php`, `scrim-process.php`, `scrim-delete.php`, `admin-process.php` |
| หน้าเว็บ | `login.php`, `register.php`, `scrim/index.php`, `scrim/create.php`, `admin/users.php`, `admin/teams.php`, `admin/scrims.php`, `admin/heroes.php`, `user/team-manage.php`, `user/team-edit.php`, `user/member-add.php`, `user/member-info.php`, `user/profile-edit.php`, `user/match-add.php`, `user/match-detail.php`, `user/match-history.php` |

ไม่ได้ลบไฟล์ใด และไม่ได้แก้ข้อมูลหรือโครงสร้างในฐานข้อมูล

---

## 9. Checklist ก่อนส่ง

- [x] README และวิธีติดตั้ง
- [x] มีไฟล์ที่ต้องใช้รันครบ
- [x] ขั้นตอนสร้างฐานข้อมูลชัดเจน (README อธิบายเรื่อง migration แล้ว)
- [x] ฟังก์ชันหลักครบตามวัตถุประสงค์
- [x] ไม่มีช่องโหว่ระดับ High ค้างอยู่
- [x] ตรวจข้อมูลนำเข้าครบ
- [x] สิทธิ์ผู้ใช้ถูกต้อง
- [x] ไม่มี secret จริงในโค้ด
- [ ] รองรับมือถือ (AUD-006)
- [ ] มีข้อมูลหรือบัญชีตัวอย่างสำหรับสาธิต
- [ ] บทความสมบูรณ์และตรงกับระบบ (AUD-007)
- [ ] มีหลักฐานการทดสอบ (รัน test case ในหัวข้อ 7 และเก็บภาพหน้าจอ)
- [ ] มี ERD / DFD / Use Case (ถ้าอาจารย์กำหนด)

## 10. Final Verdict

**READY WITH MINOR FIXES** — แก้ช่องโหว่ระดับ High ทั้งสองข้อแล้ว (XSS และ CSRF ซึ่งเมื่อใช้ร่วมกันทำให้ยกระดับเป็น admin ได้) รวมถึงปัญหาระดับ Medium ในโค้ดทั้งหมด และยืนยันด้วยการทดสอบที่รันได้จริงแล้ว

**สิ่งที่ต้องทำก่อนส่ง:**

1. รัน test case ในหัวข้อ 7 บนเบราว์เซอร์ โดยเฉพาะปุ่มลบ/ปิด/เปลี่ยนสิทธิ์ที่เปลี่ยนเป็นฟอร์ม POST
2. เติมบทความให้สมบูรณ์ (AUD-007)
3. ทำ responsive (AUD-006) ถ้าอาจารย์ประเมินด้าน UI บนมือถือ

**ข้อจำกัด:**

- ยังไม่ได้ทดสอบ UI บนเบราว์เซอร์
- ยังไม่ได้รัน flow ที่เขียนลง DB
- ผลด้าน responsive ได้จากการอ่านโค้ดเท่านั้น
