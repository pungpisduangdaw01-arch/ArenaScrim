# MIGRATION 002 — แบบ Schema สำหรับ C3, C4, E2, E4 และ E3 (ส่วนคอลัมน์)

> **สถานะ:** ออกแบบและทดสอบบน DB ทดสอบแล้ว (10 ต.ค. 2026) · **รอเจ้าของโครงการทบทวน (P3-3)** และอนุมัติการรันกับ DB จริง (D-21)
> **ไฟล์:** `database/migration_002_series_requests.sql`, `database/rollback_002_series_requests.sql`
> **ยังไม่ได้รันกับ DB จริง** · `database/arenascrim_db.sql` (ใช้ติดตั้งใหม่) จะอัปเดตพร้อมการรันกับ DB จริง

## 1. การเปลี่ยนแปลง

| # | การเปลี่ยนแปลง | ที่มา | ผลต่อข้อมูลเดิม |
|---|---|---|---|
| 1 | ตารางใหม่ `scrim_series` (team_id, opponent_name, best_of 1–7, series_date, request_id) + คอลัมน์ `scrim_matches.series_id` (FK, `ON DELETE SET NULL`) | C3, D-15, D-16, D-30 | เพิ่มคอลัมน์ · เกมเดิมถูกจัดเข้าซีรีส์ (ข้อ 7) |
| 2 | ตารางใหม่ `scrim_requests` (post_id, team_id ผู้ขอ, status pending/accepted/rejected/cancelled, message) · UNIQUE (post_id, team_id) · `scrim_series.request_id` FK `ON DELETE SET NULL` | C4, D-18, D-19 | ไม่มี |
| 3 | `scrim_posts.match_format` ENUM BO1–BO7 | D-30 | ค่าเดิมยังใช้ได้ |
| 4 | `teams.leader_id` และ `team_members.user_id` เป็น NULL ได้ + FK `ON DELETE SET NULL` (เดิม CASCADE) | D-27, D-32 | ค่าเดิมไม่เปลี่ยน |
| 5 | ตารางใหม่ `login_attempts` (login_key, attempted_at) | E2, D-37 | ไม่มี |
| 6 | ลบคอลัมน์ `team_members.position` และ `match_player_stats.hero` | D-38 | ตรวจแล้วไม่มีข้อมูล (0 แถว) |
| 7 | จัดเกมเดิมเข้าซีรีส์: ทีม + คู่แข่ง (ไม่สนตัวพิมพ์/ช่องว่างหัวท้าย) + วันเดียวกัน + Bo เดียวกัน เรียงตามเวลา และเริ่มซีรีส์ใหม่เมื่อ "เกมที่" ไม่มากกว่าเกมก่อนหน้า | D-15, D-35 | ดูข้อ 3 |

### แผนภาพความสัมพันธ์ (ส่วนที่เปลี่ยน)

```
users 1───* teams.leader_id                (SET NULL — เดิม CASCADE)
users 1───* team_members.user_id           (SET NULL — เดิม CASCADE)
teams 1───* scrim_series                   (CASCADE)
scrim_series 1───* scrim_matches.series_id (SET NULL)
scrim_posts 1───* scrim_requests           (CASCADE)
teams 1───* scrim_requests.team_id         (CASCADE)
scrim_requests 1───0..2 scrim_series.request_id (SET NULL)  ← รับคำขอแล้วสร้างซีรีส์ให้ทั้งสองทีม (D-19)
login_attempts (ไม่มี FK)
```

## 2. ผลการทดสอบบน DB ทดสอบ (`tests_p3.sh`)

| ID | ตรวจ | ผล |
|---|---|---|
| T-C5-01 | migration รันได้ไม่มี error | **PASS** |
| T-C5-02 | จำนวนแถวทุกตารางเท่าเดิม (3, 1, 5, 12, 60, 0, 92) | **PASS** |
| T-C5-03 | hash ของข้อมูลแมตช์และสถิติเดิมไม่เปลี่ยน | **PASS** |
| T-C5-04 | เกมทั้ง 12 เกมมี `series_id` | **PASS** |
| T-C5-05 | สร้างซีรีส์ 12 ชุด | **PASS** |
| T-C5-06 | best_of / ทีม / คู่แข่งของซีรีส์ตรงกับเกมทุกเกม | **PASS** |
| T-C5-07 | คอลัมน์ที่ลบหายไปจริง (รวมคอลัมน์ชั่วคราว `migrate_key`) | **PASS** |
| T-C5-08 | `match_format` = BO1–BO7 | **PASS** |
| T-C5-09 | ลบบัญชีโค้ชแล้ว ทีมยังอยู่ (`leader_id` = NULL) และผู้เล่น 5, แมตช์ 12, สถิติ 60 ยังอยู่ | **PASS** |
| T-C5-10 | rollback รันได้ | **PASS** |
| T-C5-11 | schema หลัง rollback ตรงกับของเดิมทุกตัวอักษร (diff `mysqldump --no-data`) | **PASS** |
| T-C5-12 | ข้อมูลสังเคราะห์: Alpha Bo3 เกม 1–3, Bravo, Alpha Bo3 เกม 1–2, Alpha Bo5, Alpha วันถัดไป → ซีรีส์ขนาด 3, 1, 2, 1, 1 ตามลำดับเวลา | **PASS** |
| T-C5-13 | **ความเข้ากันได้:** รันชุดทดสอบ A+B ทั้งหมด (66 กรณี) ด้วยโค้ดปัจจุบันบน schema ใหม่ | **PASS 66/66** |

DB จริงไม่เปลี่ยน (ตรวจจำนวนแถวและรายชื่อตารางก่อนและหลัง)

## 3. ข้อเท็จจริงที่ควรรู้ก่อนอนุมัติ

1. **เกมจริงทั้ง 12 เกมเล่นคนละวัน** กติกา D-15 จึงจัดเป็นซีรีส์ละ 1 เกม รวม 12 ซีรีส์ เช่น Talon Esports Bo3 เกมที่ 1 (14 ก.ย.) กับเกมที่ 2 (26 ก.ย.) จะเป็นคนละซีรีส์ ซึ่งถูกต้องตามกติกา โค้ชย้ายเกมมารวมกันได้ในระยะที่ 4 (D-15)
2. **Migration เข้ากันได้กับโค้ดปัจจุบัน** (T-C5-13) จึงรันกับ DB จริงก่อนพัฒนาระยะที่ 4 ได้ แต่ระหว่างนั้นเว็บยังใช้ข้อความเดิม เช่น คำยืนยันตอนลบผู้ใช้ยังบอกว่า "ข้อมูลทั้งหมดของทีมจะถูกลบด้วย" ทั้งที่ตอนนี้ทีมจะยังอยู่
3. **DDL ของ MariaDB ย้อนกลับในกลุ่ม transaction ไม่ได้** ถ้า migration ล้มกลางทางต้องใช้ rollback หรือกู้จากไฟล์สำรอง วิธีที่แนะนำคือกู้จากไฟล์สำรอง
4. **Rollback ต้องเตรียมก่อน** ทีมที่ไม่มีโค้ชต้องกำหนดโค้ชก่อน และประกาศที่ใช้ BO2/4/6/7 ต้องแก้ก่อน (เขียนไว้ในหัวไฟล์ rollback แล้ว)

## 4. ขั้นตอนเมื่อได้รับอนุมัติให้รันกับ DB จริง (D-21)

1. สำรอง `arenascrim_db` ใหม่ด้วย `mysqldump` ไว้นอก web root
2. บันทึกจำนวนแถวและ hash ของข้อมูลแมตช์และสถิติ
3. รัน `migration_002_series_requests.sql`
4. ตรวจซ้ำแบบ T-C5-02..08 บน DB จริง
5. อัปเดต `database/arenascrim_db.sql` ให้เป็น schema ใหม่ (สำหรับติดตั้งใหม่) และอัปเดต README
6. ถ้าผลไม่ตรง ให้กู้จากไฟล์สำรองทันที

## 5. สิ่งที่ต้องย้ายพร้อมกันตอน deploy (P4-6)

โค้ดระยะที่ 4 อยู่ใน `htdocs/ArenaScrim/ArenaScrim_test/` ซึ่งต้องใช้ schema ใหม่ จึงต้องย้ายเข้าโปรเจกต์หลัก**พร้อมกับ**การรัน migration

| ไฟล์ (ในสำเนาทดสอบ) | การเปลี่ยนแปลง |
|---|---|
| `config/functions.php` | `MATCH_TYPES` (Bo1–Bo7), `cleanup_empty_series()`, `series_scores()` |
| `actions/match-process.php`, `actions/match-delete.php` | บันทึกเกมเข้าซีรีส์ / สร้างซีรีส์ใหม่ / ลบซีรีส์ที่ว่าง |
| `user/match-add.php`, `assets/js/match-calc.js` | ช่องเลือกซีรีส์ + Bo1–Bo7 |
| `user/match-history.php`, `user/match-detail.php` | ผลรวมซีรีส์, ซีรีส์ที่รอบันทึกผล, รายการเกมในซีรีส์ |
| `actions/scrim-request.php` (ใหม่), `scrim/index.php`, `scrim/create.php`, `actions/scrim-process.php` | ขอซ้อม / ยกเลิก / รับ, lobby ต้องล็อกอิน, BO1–BO7 |
| `actions/admin-process.php`, `admin/team-detail.php`, `admin/teams.php`, `admin/users.php` | ลบโค้ชแล้วเก็บทีม + กำหนดโค้ชใหม่ |
| `actions/auth-process.php` | จำกัดล็อกอินผิด |
| `actions/export-pdf.php` | เลิกอ้างถึงคอลัมน์ `hero` |
| `database/arenascrim_db.sql` | schema ใหม่สำหรับติดตั้งใหม่ |
| `database/demo_data.sql` (ใหม่) | ข้อมูลสาธิต (สำหรับ DB ใหม่ หรือ DB สาธิตเท่านั้น) |
| `README.md` | อธิบายฟีเจอร์ใหม่และการติดตั้ง |

**ขั้นตอน (เมื่อได้รับอนุมัติ):**
1. สำรอง DB จริงและโฟลเดอร์โปรเจกต์หลัก
2. รัน migration กับ DB จริงและตรวจตาม §4
3. `rsync` จากสำเนาทดสอบเข้าโปรเจกต์หลัก โดย**ยกเว้น** `config/db.php`, `docs/`, `CLAUDE.md`, `AUDIT_REPORT.md` (เอกสารล่าสุดอยู่ในโปรเจกต์หลัก ส่วนในสำเนาเป็นรุ่นเก่า)
4. ตรวจเบื้องต้นบนเว็บหลักแบบอ่านอย่างเดียว
5. ถ้ามีปัญหา: กู้ทั้ง DB และโฟลเดอร์จากไฟล์สำรอง
