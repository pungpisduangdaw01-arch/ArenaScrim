> ⚠️ **หมายเหตุ (10 ต.ค. 2026, D-29):** เอกสารชุด `docs/project-planning/` เป็นฉบับอ้างอิงก่อนอนุมัติขอบเขต และ**ถูกแทนที่ด้วย** `docs/PROJECT_SCOPE.md`, `docs/DECISIONS.md`, `docs/ARCHITECTURE.md`, `docs/IMPLEMENTATION_PLAN.md`, `docs/TEST_PLAN.md` ถ้าข้อมูลขัดกัน ให้ยึดเอกสารชุดใหม่

# 00 — สถานะปัจจุบันของระบบ ArenaScrim

> **สถานะเอกสาร:** ร่างเพื่อใช้อ้างอิงก่อนการพัฒนารอบถัดไป · ตรวจสอบเมื่อ 9 ต.ค. 2026
> **ป้ายกำกับ:** **FACT** = มีหลักฐานจากโค้ด/DB/ผลทดสอบ · **INFERENCE** = อนุมานจากหลักฐาน · **UNKNOWN** = ข้อมูลไม่พอ · **PROPOSAL** = ข้อเสนอที่ยังไม่อนุมัติ

## 1. แหล่งข้อมูลที่ใช้

| แหล่ง | วิธีใช้ |
|---|---|
| Source code (ไม่รวม `vendor/`) | อ่านไฟล์และ grep ตรวจตำแหน่งโค้ด |
| `database/arenascrim_db.sql` | อ่าน schema |
| ฐานข้อมูลจริง `arenascrim_db` | **อ่านอย่างเดียว** ด้วย `SELECT`/`mysqldump --no-data` ไม่มีการเขียนข้อมูล |
| `AUDIT_REPORT.md` (ฉบับที่ 2) | ข้อมูลอ้างอิงเบื้องต้น ตรวจซ้ำกับโค้ดแล้ว (ดูหัวข้อ 6) |
| `docs/ArenaScrim_บทความวิจัย.docx` | ดูวัตถุประสงค์และข้อจำกัดที่ผู้วิจัยระบุไว้ |
| `README.md` | ขั้นตอนติดตั้ง |

## 2. สถาปัตยกรรม (FACT)

| ส่วน | รายละเอียด | หลักฐาน |
|---|---|---|
| ภาษา/รูปแบบ | PHP 8.2.4 แบบ procedural ไม่มี framework, MVC หรือ router | ทุกหน้าเป็นไฟล์ `.php` แยกกัน |
| ฐานข้อมูล | MariaDB 10.4.28 ผ่าน PDO (`ERRMODE_EXCEPTION`, `EMULATE_PREPARES=false`) | `config/db.php` |
| เซิร์ฟเวอร์ | Apache บน XAMPP (`mod_php`) | `httpd-xampp.conf` |
| หน้าเว็บ | HTML + CSS แบบ inline เป็นส่วนใหญ่, Chart.js และ Font Awesome จาก CDN | `includes/header.php:14,20` |
| ฟังก์ชันกลาง | `e()`, `require_login()`, `require_admin()`, CSRF helpers, `post_button()`, `get_led_team()`, `get_user_team()`, `clean_url()`, ค่าคงที่ `HERO_ROLES`/`MEMBER_ROLES`/`RANKS` | `config/functions.php:20-160` |
| รูปแบบการทำงาน | หน้าแสดงผล (`user/`, `admin/`, `scrim/`) → ฟอร์ม POST → `actions/*.php` (ตรวจสิทธิ์ + CSRF + validation) → `redirect_with()` + flash message | `config/functions.php:97` |
| Dependency | `dompdf/dompdf ^3.1` ติดตั้งแล้วแต่**ไม่มีโค้ดเรียกใช้** | `composer.json`; grep ไม่พบการเรียกใช้นอก `vendor/` |
| การทดสอบอัตโนมัติ | **ไม่มี** (ไม่มีโฟลเดอร์ tests และไม่มี PHPUnit config) | `ls tests phpunit.xml*` ไม่พบ |

### โครงสร้างโฟลเดอร์

```
├── index.php, login.php, register.php, logout.php   ทางเข้า / ยืนยันตัวตน
├── config/      db.php, functions.php, database.php (alias ของ db.php สำหรับระบบเก่า)
├── includes/    header.php (เมนูแยกตาม role), footer.php
├── user/        หน้าใช้งานของผู้ใช้ 21 ไฟล์ (เป็น redirect stub 8 ไฟล์)
├── scrim/       index.php (lobby), create.php
├── admin/       7 ไฟล์ (เป็น redirect stub 2 ไฟล์)
├── actions/     8 ไฟล์ประมวลผล
├── assets/      css (10), js (2: match-calc.js, main.js ที่เป็นไฟล์ว่าง), images
├── database/    arenascrim_db.sql, migration_member_info.sql, .htaccess (Deny)
└── docs/        บทความวิจัย .docx และเอกสารชุดนี้
```

PHP ที่ไม่รวม `vendor/` มี 45 ไฟล์ ในจำนวนนี้ 10 ไฟล์เป็น redirect stub ของระบบเก่า (FACT)

## 3. ผู้ใช้และสิทธิ์ (FACT)

| บทบาท | วิธีได้มา | สิทธิ์ | หลักฐาน |
|---|---|---|---|
| Guest | ไม่ได้ล็อกอิน | ดู Scrim lobby ได้ (รวมช่องทางติดต่อของทีม) | `scrim/index.php` ไม่เรียก `require_login()` |
| User | สมัครสมาชิก | สร้างได้ 1 ทีม, เห็นเมนูผู้ใช้ | `actions/team-process.php:55` |
| หัวหน้าทีม | user ที่สร้างทีม (`teams.leader_id`) | จัดการทีม สมาชิก ผลการซ้อม และประกาศ scrim | `get_led_team()` ใน actions ทุกตัว |
| Admin | `users.role='admin'` (ตั้งด้วย SQL หรือให้ admin คนอื่นตั้ง) | จัดการผู้ใช้ ทีม ประกาศ ฮีโร่ และดูแมตช์ของทุกทีมผ่าน URL | `config/functions.php:87`, `user/match-detail.php:15-16` |

- **FACT:** `users.role` มีแค่ 2 ค่า: `enum('user','admin')` (`database/arenascrim_db.sql:147`)
- **FACT:** สมาชิกทีมเป็น "รายชื่อ" (roster) ไม่ใช่บัญชีผู้ใช้ เพราะ `team_members.user_id` ถูกตั้งเป็น `user_id` ของหัวหน้าเสมอ (`actions/team-process.php:95`) และใน DB จริงสมาชิก 5/5 แถวมี `user_id` = หัวหน้าทีม
- **INFERENCE:** บทบาท "สมาชิกทีม" ที่ล็อกอินได้จึงไม่มีอยู่จริง แม้ `get_user_team()` (`config/functions.php:125`) จะมีโค้ดค้นหาจาก `team_members.user_id` แต่ผู้ใช้ที่ไม่ใช่หัวหน้าจะไม่เจอทีมเลย ซึ่งตรงกับข้อจำกัดข้อ 2 ในบทความวิจัย
- **FACT:** สิทธิ์อ่านจาก DB ทุก request (`config/functions.php:27-47`) ไม่ได้อ่านจาก session อย่างเดียว

## 4. ฐานข้อมูล (FACT)

| ตาราง | หน้าที่ | ความสัมพันธ์ |
|---|---|---|
| `users` | บัญชี, role, ข้อมูลส่วนตัว (nickname/fullname/open_id/full_id) | — |
| `teams` | ทีม, tag, ช่องทางติดต่อ | `leader_id` → users (CASCADE) |
| `team_members` | รายชื่อสมาชิก, ตำแหน่ง, ข้อมูลส่วนตัวของผู้เล่น | `team_id` → teams, `user_id` → users (CASCADE) |
| `heroes` | ฮีโร่ 92 ตัว พร้อมตำแหน่ง | ไม่มี FK มายังตารางนี้ |
| `scrim_matches` | ผลแต่ละเกม, objectives, ฮีโร่ฝั่งตรงข้าม 5 ช่อง, โน้ต, ลิงก์ | `team_id` → teams (CASCADE) |
| `match_player_stats` | สถิติผู้เล่นรายเกม | `match_id` → scrim_matches, `member_id` → team_members (CASCADE) |
| `scrim_posts` | ประกาศหาทีมซ้อม | `team_id` → teams (CASCADE) |

- **FACT:** schema ใน `arenascrim_db.sql` ตรงกับ DB จริงทุกคอลัมน์ (ตรวจด้วย `mysqldump --no-data` + diff)
- **FACT:** ชื่อฮีโร่และชื่อทีมคู่แข่งเก็บเป็นข้อความ ไม่ได้อ้างอิง FK ไปยัง `heroes`/`teams`
- **FACT:** ไม่มี UNIQUE บน `teams.leader_id` และ `(team_members.team_id, in_game_name)` กฎ "1 คน 1 ทีม" และ "ชื่อสมาชิกห้ามซ้ำ" บังคับเฉพาะในโค้ด (`team-process.php:55`, `:88`)
- **FACT:** ไม่มีคอลัมน์หรือตารางสำหรับจัดกลุ่มเกมเป็นซีรีส์ (Bo3/Bo5) มีแค่ `match_type` และ `game_number` ในแต่ละแถว (`arenascrim_db.sql:59-60`)
- **FACT:** `scrim_posts` กับ `scrim_matches` ไม่มีความสัมพันธ์กัน
- **FACT:** คอลัมน์ที่ไม่มีโค้ดใช้: `team_members.position`, `match_player_stats.hero` (ใช้แค่เป็นค่า fallback ใน `export-pdf.php:106`)
- **FACT (ข้อมูลจริงขณะตรวจ):** users 3 (admin 1), teams 1, team_members 5, scrim_matches 12 (Bo1 4 / Bo3 8), match_player_stats 60, scrim_posts 0, heroes 92

## 5. สถานะด้านคุณภาพ (สรุป)

| ด้าน | สถานะ | ประเภท |
|---|---|---|
| SQL Injection | ใช้ prepared statement ทุก query | FACT (อ่านโค้ด) |
| XSS | escape ด้วย `e()` และแก้ `<title>` แล้ว | FACT (harness ออฟไลน์) |
| CSRF | ทุกการเขียนข้อมูลเป็น POST + token | FACT (HTTP test ที่ login) และ INFERENCE สำหรับ action อื่น ๆ (ใช้ฟังก์ชันเดียวกัน แต่ยังไม่ได้ทดสอบทีละตัว) |
| Session/headers | `HttpOnly`, `SameSite=Lax`, security headers | FACT (curl) |
| Error handling | ยังแสดงข้อความ error ดิบ 2 จุด: `config/db.php:14`, `user/member-stats.php:126,152` | FACT |
| Responsive | ไม่มี `@media` สำหรับหน้าจอ และพบ grid แบบจำนวนคอลัมน์ตายตัว 20 จุด | FACT (โค้ด); การแสดงผลจริงเป็น UNKNOWN |
| Flow ที่เขียนลง DB | ยังไม่เคยทดสอบหลังแก้ไข | UNKNOWN |

## 6. ความขัดแย้งระหว่าง `AUDIT_REPORT.md` กับโค้ดปัจจุบัน

| ประเด็น | AUDIT_REPORT ระบุ | โค้ดปัจจุบัน | สรุป |
|---|---|---|---|
| จำนวนไฟล์ PHP | "PHP 44" | `find` นับได้ 45 ไฟล์ (ไม่รวม vendor) | รายงานนับคลาดไป 1 ไฟล์ ไม่กระทบข้อค้นพบอื่น ใช้ 45 เป็นค่าอ้างอิง |
| Redirect stub | "9 ไฟล์" (AUD-012) | 10 ไฟล์ (`user/` 8 + `admin/` 2) | รายงานนับคลาด ใช้ 10 เป็นค่าอ้างอิง |
| ตำแหน่ง `die()` ใน db.php | `config/db.php:15` | อยู่ที่บรรทัด 14 | เลขบรรทัดคลาด แต่ปัญหายังอยู่จริง |
| "render 17 หน้า" (TC-RUN-11) | 17 หน้า | ผลจริงคือ render 17 ครั้ง ครอบคลุม 15 หน้าไม่ซ้ำกัน (2 ครั้งเป็นโหมดแก้ไข) และใน 17 ครั้งนั้นมี `scrim/index.php` ที่ผล harness ไม่สมบูรณ์ (มี warning เรื่อง role เพราะหน้านี้ไม่ต้องล็อกอิน) | ควรแก้ข้อความในรายงานให้ตรง |
| สถานะ AUD-001 ถึง 005 และ 008 | แก้แล้ว | ตรวจโค้ดแล้วพบการแก้ตามที่ระบุ (`header.php:9`, `functions.php:27-84`, `match-add.php` hero_select, `.htaccess`) | สอดคล้อง |
| ข้อจำกัดที่รายงานระบุเอง | ไม่ได้ทดสอบ flow ที่เขียนลง DB | ยังไม่มีหลักฐานการทดสอบเพิ่ม | สอดคล้อง และยังเป็น UNKNOWN |

## 7. ข้อจำกัดของหลักฐาน

- ไม่ได้เปิด UI บนเบราว์เซอร์จริง
- ไม่ได้รันการสร้าง แก้ไข หรือลบข้อมูล
- การยืนยันว่า "ทำงาน" ส่วนใหญ่มาจากการอ่านโค้ด เว้นแต่จะระบุไว้ใน `06_EVIDENCE_AND_TEST_GAPS.md`
- ข้อมูลใน DB จริงมีน้อย (1 ทีม, 12 แมตช์) จึงไม่สะท้อนพฤติกรรมเมื่อข้อมูลมาก เช่น การไม่มี pagination
