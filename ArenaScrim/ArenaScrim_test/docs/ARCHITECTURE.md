# ARCHITECTURE — ArenaScrim

> **ตรวจล่าสุด:** 10 ต.ค. 2026 (อ่านโค้ด + query ฐานข้อมูลแบบอ่านอย่างเดียว)
> **ป้ายกำกับ:** **FACT** = มีหลักฐานจากโค้ดหรือ DB · **INFERENCE** = อนุมานจากหลักฐาน · **PROPOSAL** = ข้อเสนอที่ยังไม่อนุมัติ · **TBD** = รอการตัดสินใจ
> ส่วนที่ 1–8 อธิบาย**ระบบปัจจุบัน**เท่านั้น ส่วนที่ 9 เป็นข้อเสนอสำหรับอนาคต และแยกออกจากกันโดยเจตนา

---

## 1. Technology Stack (FACT)

| ส่วน | รายละเอียด | หลักฐาน |
|---|---|---|
| ภาษา | PHP 8.2.4 แบบ procedural ไม่มี framework, router หรือ autoload ของโค้ดตัวเอง | ทุกหน้าเป็นไฟล์ `.php` ที่เรียกตรง |
| ฐานข้อมูล | MariaDB 10.4.28, InnoDB ทุกตาราง, `utf8mb4_unicode_ci` | `information_schema.TABLES` |
| การเชื่อมต่อ | PDO: `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `EMULATE_PREPARES=false`, `charset=utf8mb4` | `config/db.php:7-12` |
| เว็บเซิร์ฟเวอร์ | Apache 2.4 + `mod_php` (XAMPP), `AllowOverride All` | `httpd.conf`, `httpd-xampp.conf` |
| ฝั่งหน้าเว็บ | HTML ที่ render จากเซิร์ฟเวอร์, CSS ส่วนใหญ่เป็น inline, Chart.js (CDN ไม่ระบุเวอร์ชัน), Font Awesome 6.5.1 (CDN), Google Fonts (Prompt) | `includes/header.php:11-20` |
| JavaScript | `assets/js/match-calc.js` (คำนวณ % ในฟอร์มบันทึกผล) และ `assets/js/main.js` (ไฟล์ว่าง) | — |
| Dependency (Composer) | `dompdf/dompdf ^3.1` ติดตั้งใน `vendor/` แต่ไม่มีโค้ดเรียกใช้ และไม่มีการ `require vendor/autoload.php` | grep |
| Timezone | PHP: `Asia/Bangkok` (`functions.php:4`) · MariaDB: `SYSTEM` (+07 บนเครื่องนี้) | `SELECT @@time_zone` |
| Automated tests | ไม่มี | ไม่พบ `tests/` หรือ `phpunit.xml` |

**INFERENCE (timezone):** การตรวจวันที่ฝั่ง PHP (`match-process.php`, `scrim-process.php`) ใช้เวลา Asia/Bangkok แต่การหมดอายุของประกาศใช้ `NOW()` ของ MariaDB (`scrim/index.php:9`) บนเครื่องนี้ทั้งสองตรงกัน (+07) แต่ถ้าย้ายไปเซิร์ฟเวอร์ที่ timezone ต่างกันจะเพี้ยน

---

## 2. โครงสร้างโปรเจกต์และหน้าที่ (FACT — อัปเดตหลังระยะที่ 2, 10 ต.ค. 2026: ลบ redirect stub 10 ไฟล์, `config/database.php`, CSS/JS ที่ไม่ใช้ · เพิ่ม `paginate_rows()`/`pagination_links()` ใน functions.php · `export-pdf.php` ใช้ dompdf)

```
/                      index.php (redirect ตาม role), login.php, register.php, logout.php
config/db.php          สร้าง $pdo แล้ว require functions.php
config/functions.php   session_start, BASE_URL, e(), require_login(), require_admin(), CSRF helpers,
                       post_button(), redirect_with(), show_flash(), get_led_team(), get_user_team(),
                       clean_url(), ค่าคงที่ HERO_ROLES / MEMBER_ROLES / RANKS
includes/              header.php (เมนูตาม role), footer.php
user/                  หน้าผู้ใช้ 14 หน้า (รวม opponents.php — วิเคราะห์คู่แข่ง)
scrim/                 index.php (lobby, ไม่ต้องล็อกอิน), create.php
admin/                 6 หน้า (รวม team-detail.php — ดูทีมแบบอ่านอย่างเดียว)
actions/               ไฟล์ประมวลผลฟอร์ม 8 ไฟล์
database/              arenascrim_db.sql (schema + ฮีโร่ 92 ตัว), migration_member_info.sql (สำหรับ DB รุ่นเก่า), .htaccess
assets/fonts/          ฟอนต์ Sarabun (OFL) + metrics/cache ของ dompdf ที่สร้างไว้ล่วงหน้า (Apache เขียนโฟลเดอร์นี้ไม่ได้)
.htaccess              ปิด directory listing, บล็อก .md/.json/.lock/.docx, ตั้งค่า session cookie, security headers
```

---

## 3. Request Flow (FACT)

```
GET หน้าเว็บ
  └─ require_once config/db.php → $pdo + functions.php (session_start)
  └─ require_login() / require_admin()            ← อ่าน role จาก DB ทุก request
  └─ get_led_team() / get_user_team()             ← ตรวจความเป็นเจ้าของ
  └─ SELECT ...  → include header.php → HTML (escape ด้วย e()) → footer.php

POST ฟอร์ม (มี csrf_field())
  └─ actions/*.php → require_login/admin → verify_csrf($back)
  └─ ตรวจความเป็นเจ้าของ (get_led_team หรือ JOIN teams.leader_id) → validate
  └─ INSERT/UPDATE/DELETE (prepared) → redirect_with($url, 'success'|'error', ข้อความ)
  └─ หน้าปลายทาง show_flash()
```

**ข้อยกเว้นที่พบ (FACT):**
- `user/profile-edit.php` และ `user/member-info.php` ส่งฟอร์มกลับมาที่หน้าตัวเอง (ไม่ผ่าน `actions/`) แต่เรียก `verify_csrf()` เช่นเดียวกัน
- มีการเขียนข้อมูลจาก request แบบ GET 2 จุด: `UPDATE scrim_posts SET status='expired'` ใน `scrim/index.php:9` และ `admin/scrims.php:11` เป็นการอัปเดตอัตโนมัติที่ผู้ใช้ควบคุมไม่ได้ จึงไม่มี CSRF
- Logout ใช้ GET (`includes/header.php:69` → `auth-process.php?action=logout` → `logout.php`)

---

## 4. การเชื่อมโยงไฟล์กับตาราง (FACT — grep `FROM/JOIN/INSERT/UPDATE/DELETE`)

| ไฟล์ | อ่าน | เขียน |
|---|---|---|
| `actions/auth-process.php` | users | users |
| `actions/team-process.php` | teams, team_members | teams, team_members |
| `actions/match-process.php` | team_members, scrim_matches, match_player_stats | scrim_matches, match_player_stats |
| `actions/match-delete.php` | teams, scrim_matches | scrim_matches (+cascade stats) |
| `actions/scrim-process.php` | teams | scrim_posts |
| `actions/scrim-delete.php` | teams, scrim_posts | scrim_posts |
| `actions/admin-process.php` | users, teams, heroes, scrim_posts | users, teams, heroes, scrim_posts, scrim_matches, match_player_stats (กรณีแก้ชื่อฮีโร่) |
| `actions/export-pdf.php` | teams, team_members, scrim_matches, match_player_stats | — |
| `user/profile-edit.php` | users | users |
| `user/member-info.php` | team_members, scrim_matches, match_player_stats | team_members |
| `user/team-manage.php`, `team-edit.php`, `member-add.php` | teams (ผ่าน `get_led_team`), team_members | — |
| `user/match-add.php` | team_members, heroes, scrim_matches, match_player_stats | — |
| `user/match-history.php`, `match-detail.php`, `profile.php`, `member-stats.php`, `period-stats.php` | scrim_matches, match_player_stats (+team_members, teams) | — |
| `scrim/index.php` | users, teams, scrim_posts | scrim_posts (expire) |
| `admin/index.php`, `users.php`, `teams.php`, `heroes.php` | ตามชื่อหน้า | — |
| `admin/scrims.php` | users, teams, scrim_posts | scrim_posts (expire) |

---

## 5. Data Model (FACT — `information_schema`, ตรงกับ `database/arenascrim_db.sql`)

```
users 1───* teams (leader_id)            CASCADE
users 1───* team_members (user_id)       CASCADE   ← ปัจจุบันเป็น user_id ของหัวหน้าเสมอ
teams 1───* team_members (team_id)       CASCADE
teams 1───* scrim_matches (team_id)      CASCADE
teams 1───* scrim_posts (team_id)        CASCADE
scrim_matches 1───* match_player_stats (match_id)   CASCADE
team_members  1───* match_player_stats (member_id)  CASCADE
heroes   (ไม่มี FK — ชื่อฮีโร่ในตารางอื่นเก็บเป็นข้อความ)
```

| ตาราง | PK | UNIQUE | Index อื่น | หมายเหตุ |
|---|---|---|---|---|
| users | user_id | username, email | — | `role enum('user','admin')` |
| teams | team_id | team_name | leader_id | **ไม่มี** UNIQUE บน leader_id |
| team_members | member_id | — | team_id, user_id | **ไม่มี** UNIQUE (team_id, in_game_name) · `position` ไม่ได้ใช้ |
| heroes | hero_id | hero_name | — | 92 แถว |
| scrim_matches | match_id | — | team_id | `opponent_name` เป็นข้อความอิสระ · `enemy_hero_1..5` เป็นคอลัมน์แยก · ไม่มี series |
| match_player_stats | stat_id | — | match_id, member_id | **ไม่มี** UNIQUE (match_id, member_id) (ป้องกันซ้ำในโค้ด `match-process.php:147`) · `hero` ไม่ได้ใช้ |
| scrim_posts | post_id | — | team_id | `status enum('open','matched','expired')` · ไม่มีความสัมพันธ์กับ scrim_matches |

- ทุก FK: `ON DELETE CASCADE`, `ON UPDATE RESTRICT`
- **INFERENCE:** ถ้าบัญชีของผู้ใช้ถูกลบ `team_members` ที่อ้างถึง user นั้นจะถูกลบตาม และสถิติใน `match_player_stats` ก็จะหายไปด้วย ตอนนี้ยังไม่เกิดผล เพราะทุกแถวอ้างถึงหัวหน้า แต่จะสำคัญมากเมื่อทำบัญชีผู้เล่น (C1)

**ข้อมูลจริงขณะตรวจ:** users 3 · teams 1 · team_members 5 · scrim_matches 12 · match_player_stats 60 · scrim_posts 0 · heroes 92

---

## 6. Authentication, Authorization และความปลอดภัย (FACT)

| กลไก | การทำงาน | ตำแหน่ง |
|---|---|---|
| รหัสผ่าน | `password_hash(PASSWORD_DEFAULT)` / `password_verify` และขั้นต่ำ 6 ตัว | `auth-process.php:29,46,75` |
| Session | `session_regenerate_id(true)` เมื่อล็อกอินและสมัคร; cookie `HttpOnly`, `SameSite=Lax`, `use_strict_mode` | `auth-process.php`, `.htaccess` |
| ตรวจการล็อกอิน | `require_login()` อ่าน `users.role` จาก DB ทุก request และล้าง session ถ้าบัญชีถูกลบ | `functions.php:27-47` |
| ตรวจ Admin | `require_admin()` = `require_login()` + role = admin | `functions.php:87-95` |
| ความเป็นเจ้าของ | หัวหน้า: `get_led_team()` (10 จุด) · ดูข้อมูลทีม: `get_user_team()` (7 จุด) · ลบ: `JOIN teams ... AND t.leader_id = ?` · ผู้เล่นในฟอร์มบันทึกผล: `$valid_members` | `match-process.php:90,147` ฯลฯ |
| Admin ดูข้ามทีม | `match-detail.php:15-16`, `export-pdf.php:18-19` อนุญาต role admin | — |
| CSRF | token 64 hex ต่อ session; `verify_csrf()` ตรวจ POST + `hash_equals`; ปุ่มที่เขียนข้อมูลทุกปุ่มใช้ `post_button()` | `functions.php:52-84` |
| XSS | `e()` = `htmlspecialchars(ENT_QUOTES, UTF-8)`; `json_encode` ใน `<script>` | `functions.php:20` |
| URL ที่ผู้ใช้กรอก | `clean_url()` รับเฉพาะ http/https | `functions.php:137` |
| Headers | `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` | `.htaccess` |
| Rate limit ล็อกอิน | **ไม่มี** | — |

### การจัดการข้อผิดพลาด (FACT)

| จุด | พฤติกรรม |
|---|---|
| เชื่อมต่อ DB ไม่ได้ | `error_log()` + HTTP 503 + ข้อความทั่วไป (`config/db.php`) — **แก้ 10 ต.ค. 2026** (เดิมแสดง SQLSTATE) |
| `member-stats.php` | `error_log()` + ข้อความทั่วไป — **แก้ 10 ต.ค. 2026** |
| `match-process.php` | `rollBack()` + `error_log()` + ข้อความทั่วไป (`:171-174`) |
| Exception อื่นที่ไม่ได้ catch | PHP `display_errors=Off` (php.ini) จึงได้หน้า HTTP 500 ว่าง |
| ข้อผิดพลาดจาก validation | `redirect_with(..., 'error', ...)` + `show_flash()` |

---

## 7. สูตรคำนวณสถิติ (FACT — จากโค้ด)

| ตัวชี้วัด | สูตร | ที่ใช้ | ตำแหน่ง |
|---|---|---|---|
| Win rate ของทีม | WIN ÷ (WIN+LOSS) × 100 นับ**ต่อเกม** (1 แถว `scrim_matches` = 1 ครั้ง) | profile, match-history, period | `profile.php:24-33`, `match-history.php:60-63`, `period-stats.php:49,77` |
| Win rate ผู้เล่น | นับเฉพาะเกมที่ผู้เล่นมีแถวสถิติ | member-stats, member-info, period (รายคน) | `member-stats.php:57-58,95`, `member-info.php:78` |
| KDA ratio | (K+A) ÷ max(1, D) (ถ้า D=0 ให้ใช้ K+A) | match-detail, period, member-info | `match-detail.php:41`, `period-stats.php:78,105`, `member-info.php:249` |
| K/D/A เฉลี่ยต่อผู้เล่น | AVG(kills) ฯลฯ จากทุกแถวสถิติ | profile, member-stats | `profile.php:36`, `member-stats.php:59-61` |
| Kill/Death ทีมต่อเกม (period) | SUM ÷ จำนวนแมตช์ที่**มี**สถิติ | period | `period-stats.php:57,79-80` |
| Gold เฉลี่ย | AVG(NULLIF(gold,0)) **ไม่นับแถวที่ gold = 0** | member-stats, period | `member-stats.php:65`, `period-stats.php:59,97` |
| MVP / % damage / % taken เฉลี่ย | AVG ตรง ๆ **นับค่า 0 ด้วย** | member-stats, period | `member-stats.php:66-68`, `period-stats.php:59` |
| % gold/damage/taken (ตอนกรอก) | ค่าผู้เล่น ÷ ผลรวมของผู้เล่นที่เลือกฮีโร่ × 100 ปัดทศนิยม 1 ตำแหน่ง (คำนวณฝั่ง JS ผู้ใช้แก้ทับได้; server จำกัดค่า 0–100) | match-add | `match-calc.js:13-22`, `match-process.php` `$pct` |
| % ร่วมไฟต์ | min(100, (K+A) ÷ Kill รวมทีม × 100) | match-add | `match-calc.js:24-31` |
| เวลาเกมเฉลี่ย | แปลง `mm` หรือ `mm:ss` เป็นวินาทีแล้วเฉลี่ย | match-history, period | `match-history.php:76-82`, `period-stats.php:66-69` |
| Streak | นับผลเดียวกันต่อเนื่องจากแมตช์ล่าสุด**ในชุดที่ถูกกรองแล้ว** | match-history | `match-history.php:68-73` |
| ช่วงสัปดาห์ | ISO week (จันทร์ 00:00 ถึงจันทร์ถัดไป แบบ exclusive) | period | `period-stats.php:19-24` |
| ช่วงเดือน | วันที่ 1 00:00 ถึงวันที่ 1 ของเดือนถัดไป (exclusive) | period | `period-stats.php:26-29` |
| Objectives (period) | AVG ต่อเกม; first dragon/tower = AVG(0/1) × 100 · **ไม่รวม God Slayer** | period | `period-stats.php:50-51` |
| ลูกศรดีขึ้น/แย่ลง | คำนวณผลต่างจากค่าที่ปัดทศนิยมเท่ากับที่แสดงบนหน้าจอ (pct/dec 1, dec2 2, int/time 0) ถ้าเท่ากันแสดง "เท่าเดิม" มิฉะนั้นใช้ทิศทางที่กำหนด (**D-40**, แก้ 10 ต.ค. 2026) | period | `period-stats.php:125-136,183-195` |

**ข้อสังเกต (INFERENCE — ต้องยืนยันใน TEST_PLAN ไม่ใช่บั๊กที่ยืนยันแล้ว):**
1. ~~Gold ไม่นับค่า 0 แต่ MVP และ % ต่าง ๆ นับค่า 0~~ → **D-24 (10 ต.ค. 2026):** ผู้เล่นที่ลงเล่นต้องมี Gold, MVP, ดาเมจทำ และดาเมจรับ > 0 (ตรวจทั้ง `match-process.php` และ `match-calc.js`) จึงไม่มีค่า 0 ใหม่เข้ามา และข้อมูลเดิมไม่มีค่า 0
2. Win rate ใน period ใช้ทุกแมตช์ แต่ KDA ใช้เฉพาะแมตช์ที่มีสถิติ ตัวหารจึงต่างกัน (TBD-N12)
3. ~~เกณฑ์ "เท่าเดิม" 0.05~~ → แก้ตาม D-40 (T-FIX-D40 PASS)
4. ผลรวม % ในข้อมูลจริงอยู่ระหว่าง 99.9–100.1 ต่อแมตช์ ซึ่งสอดคล้องกับสูตรใน `match-calc.js` (คลาดจากการปัดเศษ)
5. `group_concat_max_len` ของเซิร์ฟเวอร์นี้คือ 1,048,576 ดังนั้น `GROUP_CONCAT(match_duration)` (`period-stats.php:52`) **ไม่ติดข้อจำกัด 1024** ตามที่เอกสารรอบก่อนระบุ (ดู DECISIONS: ความไม่สอดคล้องของเอกสาร)

---

## 8. ข้อจำกัดเชิงโครงสร้างที่มีผลต่องานถัดไป (FACT)

| ข้อจำกัด | ผลต่อ |
|---|---|
| สิทธิ์ระดับทีมขึ้นกับ `teams.leader_id` เพียงคนเดียว และมีการเช็กกระจายอยู่ 21 จุด: `get_led_team` 10, `$is_leader` 4, เงื่อนไข `leader_id` ใน SQL หรือการเทียบค่า 7 (`match-delete.php:11`, `scrim-delete.php:10`, `scrim-process.php:51`, `team-process.php:105,118,139`, `scrim/index.php:122`) | C1, C2 |
| `team_members.user_id` เป็น NOT NULL และตอนนี้ใส่ id ของหัวหน้า | C1 |
| ไม่มีหน่วย "ซีรีส์" | C3, และ B4 (ผลต่อคู่แข่งนับเป็นเกม) |
| `scrim_posts` ไม่สัมพันธ์กับ `scrim_matches` | C4 |
| หน้าประวัติคำนวณการ์ดสรุป, streak และเวลาเฉลี่ยจาก**ผลลัพธ์ทั้งหมด**ที่ดึงมาในหน่วยความจำ (`match-history.php:57-82`) | B2: ถ้าใส่ LIMIT ตรง ๆ การ์ดสรุปจะผิด |
| `member-info.php`, `member-stats.php` ใช้ทีมของผู้ใช้ปัจจุบัน (`get_user_team`) | B3: นำมาใช้ให้ admin ดูทีมอื่นตรง ๆ ไม่ได้ |
| `opponent_name` เป็นข้อความอิสระ | B4: ชื่อทีมเดียวกันที่สะกดต่างกันจะถูกนับเป็นคนละทีม |
| ฮีโร่ฝั่งตรงข้ามอยู่ใน 5 คอลัมน์ | B4: ต้อง `UNION ALL` (มีตัวอย่างใน `admin/heroes.php:27-31`) |
| dompdf มีเฉพาะฟอนต์ตะวันตก (Courier, DejaVu ฯลฯ) | B5: **INFERENCE** ถ้าใช้ PDF จริงต้องเพิ่มฟอนต์ภาษาไทย (ต้องทดสอบยืนยัน) |
| `arenascrim_db.sql` เป็นไฟล์ dump และมีรูปแบบไฟล์ migration อยู่แล้ว 1 ไฟล์ | C5: แนวทางการเปลี่ยน schema |

---

## 9. ข้อเสนอการเปลี่ยนแปลงในอนาคต (PROPOSAL — ยังไม่อนุมัติรายละเอียด)

> ส่วนนี้ไม่ได้อธิบายระบบปัจจุบัน ทุกข้อขึ้นกับรายการ TBD ใน `docs/DECISIONS.md`
>
> **อัปเดต 10 ต.ค. 2026:**
> - แถว C1 และ C2 ในตารางนี้**ยกเลิก** (D-11, D-12)
> - เพิ่มการเปลี่ยน FK `teams.leader_id` และ `team_members.user_id` เพื่อให้ลบโค้ชแล้วทีมและสถิติยังอยู่ (D-27, ดู §5 INFERENCE เรื่อง CASCADE)
> - B5 = dompdf (D-10)

| งาน | จุดที่ต้องเปลี่ยน (จากหลักฐาน) | ทางเลือกที่เป็นไปได้ | TBD ที่เกี่ยวข้อง |
|---|---|---|---|
| A4 error ดิบ | `db.php:14`, `member-stats.php:126,152` | `error_log()` + ข้อความทั่วไป (ตามแบบใน `match-process.php`) | — |
| B1 เปลี่ยนรหัสผ่าน | `profile-edit.php` หรือหน้าใหม่ + `UPDATE users SET password` | ต้องกรอกรหัสผ่านเดิม + `session_regenerate_id` | TBD-B1 |
| B2 Pagination | `match-history.php`, `admin/users.php`, `teams.php`, `scrims.php`, `heroes.php` | แยก query สรุป (ทั้งหมด) ออกจาก query รายการ (LIMIT/OFFSET) | TBD-B2 |
| B3 Admin ดูทีม | หน้าใหม่ใน `admin/` + ลิงก์จาก `admin/teams.php` | หน้าอ่านอย่างเดียวที่รับ `team_id` และใช้ `match-detail.php` เดิม (รองรับ admin แล้ว) | TBD-B3 |
| B4 สถิติคู่แข่ง/ฮีโร่ฝั่งตรงข้าม | หน้าใหม่ หรือส่วนเพิ่มใน `profile.php` / `match-history.php` | GROUP BY `LOWER(TRIM(opponent_name))` + UNION ALL enemy_hero | TBD-B4 |
| B5 รายงาน | `actions/export-pdf.php` | (ก) คงหน้าพิมพ์ + แก้เอกสาร หรือ (ข) ใช้ dompdf ที่มีอยู่ + ฟอนต์ไทย | **TBD-B5** |
| C1 บัญชีผู้เล่น | `team_members.user_id`, `get_user_team()`, ทุกหน้าที่ใช้ `$is_leader`, flow ใหม่สำหรับเชื่อมบัญชี | ทำ `user_id` ให้เป็น NULL ได้ หรือเพิ่มตารางเชื่อมใหม่, เชิญด้วยรหัส หรือค้นหา username | TBD-C1-* |
| C2 หลายผู้จัดการ | `get_led_team()` 10 จุด, `leader_id` ใน SQL หรือการเทียบค่า 7 จุด, `$is_leader` 4 จุด | คอลัมน์ `team_role` ใน team_members หรือตาราง `team_staff`, และฟังก์ชันกลาง `team_can($perm)` | TBD-C2-* |
| C3 ซีรีส์ | `scrim_matches`, `match-add.php`, `match-process.php`, หน้าสถิติ | ตาราง `scrim_series` + `series_id` ใน scrim_matches | TBD-C3-* |
| C4 ตอบรับ Scrim | `scrim_posts`, `scrim/index.php`, action ใหม่ | ตาราง `scrim_requests` + เชื่อมกับ series หรือ match | TBD-C4-* |
| C5 Schema | `database/` | ไฟล์ migration แยกตามลำดับ + อัปเดต `arenascrim_db.sql` + สำรองก่อนรัน | TBD-C5 |
