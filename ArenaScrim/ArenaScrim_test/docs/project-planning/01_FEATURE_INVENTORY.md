> ⚠️ **หมายเหตุ (10 ต.ค. 2026, D-29):** เอกสารชุด `docs/project-planning/` เป็นฉบับอ้างอิงก่อนอนุมัติขอบเขต และ**ถูกแทนที่ด้วย** `docs/PROJECT_SCOPE.md`, `docs/DECISIONS.md`, `docs/ARCHITECTURE.md`, `docs/IMPLEMENTATION_PLAN.md`, `docs/TEST_PLAN.md` ถ้าข้อมูลขัดกัน ให้ยึดเอกสารชุดใหม่

# 01 — Feature Inventory

> **สถานะเอกสาร:** ร่าง · ตรวจจาก source code ณ 10 ต.ค. 2026
> **ความหมายของสถานะ:**
> - **ยืนยันแล้ว** = มีผลทดสอบจริงรองรับ (ระบุพฤติกรรมที่ยืนยันไว้ในคอลัมน์หลักฐาน)
> - **มีโค้ดแต่ยังไม่ยืนยัน** = อ่านโค้ดแล้วพบ logic ครบ แต่ยังไม่มีผลทดสอบการทำงาน
> - **ทำงานบางส่วน** = มีบางส่วนของความสามารถ แต่ขาดบางส่วนตามหลักฐาน
> - **พบปัญหา** = มีหลักฐานว่าทำงานผิดหรือไม่ครบ
> - **ยังไม่พบ** = ไม่พบโค้ดของความสามารถนี้
>
> เอกสารนี้ไม่ได้ระบุว่าโมดูลใด "สมบูรณ์" หรือ "ผ่านการทดสอบ" เพราะยังไม่มีการทดสอบ flow ที่เขียนข้อมูลลง DB

## 1. ตาราง Inventory

| Module ID | ชื่อโมดูล | ผู้ใช้ | สิ่งที่ทำได้ (จากโค้ด) | Input → Output สำคัญ | ตาราง | ไฟล์ | สถานะ | หลักฐาน | ข้อจำกัด / คำถาม |
|---|---|---|---|---|---|---|---|---|---|
| M01 | สมัคร / เข้าสู่ระบบ / ออกจากระบบ | Guest, User | สมัครด้วย username/display/email/password; ล็อกอินด้วย username หรือ email; logout ล้าง session | ฟอร์ม → แถวใน `users` (password hash) → session | users | `register.php`, `login.php`, `logout.php`, `actions/auth-process.php` | มีโค้ดแต่ยังไม่ยืนยัน (ยืนยันแล้วเฉพาะ: ปฏิเสธเมื่อ CSRF ผิด และแจ้งเมื่อรหัสผ่านผิด) | `auth-process.php:8-95`; HTTP test TC-RUN-07..09 | ไม่มีการเปลี่ยนหรือรีเซ็ตรหัสผ่าน และไม่จำกัดจำนวนครั้งที่ล็อกอินผิด |
| M02 | ข้อมูลบัญชีส่วนตัว | User | แก้ display name, nickname, fullname, OpenID, Full ID | ฟอร์ม → `UPDATE users` | users | `user/profile-edit.php` | มีโค้ดแต่ยังไม่ยืนยัน | `profile-edit.php:17-43` | ฟิลด์ nickname/fullname/open_id/full_id ไม่มีหน้าอื่นใช้ (grep) และซ้ำกับฟิลด์ใน `team_members` |
| M03 | จัดการทีม | User (หัวหน้า) | สร้างทีม (1 คน 1 ทีม), แก้ชื่อ/tag/ช่องทางติดต่อ, ยุบทีม (ลบข้อมูลทีมทั้งหมดผ่าน cascade) | ฟอร์ม → `teams` | teams (+cascade) | `user/team-manage.php`, `user/team-edit.php`, `actions/team-process.php` | มีโค้ดแต่ยังไม่ยืนยัน | `team-process.php:54-145` | กฎ 1 คน 1 ทีมไม่มี UNIQUE ใน DB และไม่มีการโอนตำแหน่งหัวหน้า |
| M04 | จัดการรายชื่อสมาชิก | หัวหน้า | เพิ่ม/ลบสมาชิก (IGN + ตำแหน่ง), แก้ข้อมูลส่วนตัวผู้เล่น (ชื่อจริง, OpenID, แรงก์, ช่องทางติดต่อ, หมายเหตุ) | ฟอร์ม → `team_members` | team_members | `user/member-add.php`, `user/member-info.php`, `actions/team-process.php` | มีโค้ดแต่ยังไม่ยืนยัน | `team-process.php:74-115`, `member-info.php:36-73` | สมาชิกเป็น roster ที่ล็อกอินไม่ได้ (`team-process.php:95`) และลบสมาชิกแล้วสถิติย้อนหลังหายตาม (cascade) |
| M05 | บันทึกผลการซ้อม | หัวหน้า | บันทึก/แก้ไขเกม: คู่แข่ง, วันเวลา (ห้ามอนาคต), Bo, เกมที่, ผล, เวลาเกม, objectives, ฮีโร่ฝั่งตรงข้าม 5 ตัว, สถิติผู้เล่นรายคน, ลิงก์รูป/รีเพลย์, โน้ต; คำนวณ % อัตโนมัติฝั่ง JS | ฟอร์ม → `scrim_matches` + `match_player_stats` (transaction) | scrim_matches, match_player_stats, heroes, team_members | `user/match-add.php`, `actions/match-process.php`, `assets/js/match-calc.js` | มีโค้ดแต่ยังไม่ยืนยัน (ยืนยันแล้วเฉพาะ: `hero_select()` คงฮีโร่ที่ถูกลบไว้) | `match-process.php:1-175`; `match-calc.js:1-51` | แต่ละเกมเป็นแถวแยก ไม่มีการจัดกลุ่มซีรีส์ และชื่อคู่แข่งเป็นข้อความอิสระ |
| M06 | ประวัติและรายละเอียดแมตช์ | หัวหน้า, Admin (ผ่าน URL) | สรุป (W/L, win rate, ฟอร์ม 5 เกม, streak, เวลาเฉลี่ย, จำนวนคู่ซ้อม), ค้นหาคู่แข่ง, กรองผล/Bo/ช่วงวันที่, หน้ารายละเอียด (KDA ทีม, MVP, objectives, ฮีโร่ฝั่งตรงข้าม, ตารางผู้เล่น, โน้ต, รูป) | GET filter → ตาราง | scrim_matches, match_player_stats | `user/match-history.php`, `user/match-detail.php` | มีโค้ดแต่ยังไม่ยืนยัน (ยืนยันแล้วเฉพาะ: render ด้วยบัญชีจริงไม่มี error) | `match-history.php:13-57`; TC-RUN-11 | ไม่มี pagination (ดึงทุกแถวพร้อมกัน) และลบแมตช์ผ่านปุ่ม POST |
| M07 | รายงานผลการแข่ง | หัวหน้า, Admin | เปิดหน้า HTML รายงานแล้วเรียก `window.print()` | match_id → หน้าพิมพ์ | scrim_matches, match_player_stats | `actions/export-pdf.php`, `assets/css/pdf-style.css` | ทำงานบางส่วน | `export-pdf.php:40` | **ไม่ได้สร้างไฟล์ PDF** (ต้องให้ผู้ใช้ "บันทึกเป็น PDF" จากเบราว์เซอร์เอง) และ dompdf ไม่ได้ใช้ |
| M08 | สถิติภาพรวมทีม | หัวหน้า | แมตช์, win rate, KDA เฉลี่ยต่อผู้เล่น, W/L, ฮีโร่ที่ใช้บ่อย 5 ตัว, 10 แมตช์ล่าสุด, กราฟ doughnut/bar | → การ์ด + Chart.js | scrim_matches, match_player_stats | `user/profile.php` | มีโค้ดแต่ยังไม่ยืนยัน | `profile.php:22-69` | — |
| M09 | สถิติรายผู้เล่น | หัวหน้า | เลือกผู้เล่น → แมตช์, win rate, MVP เฉลี่ย, KDA เฉลี่ย, gold เฉลี่ย, % damage/taken, ฮีโร่ที่ใช้บ่อย, ประวัติรายเกม, กราฟ | member_id → การ์ด + ตาราง | match_player_stats, scrim_matches | `user/member-stats.php` | มีโค้ดแต่ยังไม่ยืนยัน | `member-stats.php:46-127` | แสดง `$db_error` ดิบ (`:152`) |
| M10 | เปรียบเทียบตามช่วงเวลา | หัวหน้า | สรุปสัปดาห์นี้/เดือนนี้, เลือก 2 ช่วงมาเทียบ 13 ตัวชี้วัด + W/L พร้อมลูกศรดีขึ้น/แย่ลง, ตารางรายผู้เล่น, กราฟแนวโน้ม 8 สัปดาห์/6 เดือน | mode, a, b → ตาราง + กราฟ | scrim_matches, match_player_stats | `user/period-stats.php` | มีโค้ดแต่ยังไม่ยืนยัน | `period-stats.php:16-109,183-195` | ไม่รวม God Slayer และ `GROUP_CONCAT` มีขีดจำกัดตาม `group_concat_max_len` (บนเครื่องนี้ 1,048,576 — แก้ไขตาม DECISIONS X-01) |
| M11 | Scrim lobby | Guest (ดู), หัวหน้า (โพสต์/ปิด/ลบ) | ดูประกาศ (เปิดรับ/ทั้งหมด), โพสต์ (แรงก์, Bo, เวลาอนาคต, หมายเหตุ), ปิดประกาศ ("ได้ทีมแล้ว"), ลบ; หมดเวลาอัตโนมัติทุกครั้งที่เปิดหน้า; ติดต่อผ่านลิงก์ Discord/Facebook/Line | ฟอร์ม → `scrim_posts` | scrim_posts, teams | `scrim/index.php`, `scrim/create.php`, `actions/scrim-process.php`, `actions/scrim-delete.php` | ทำงานบางส่วน | `scrim/index.php:9` (expire), `scrim-process.php:13-63` | **ไม่มีการตอบรับหรือจับคู่ในระบบ** ทีมอื่นต้องติดต่อผ่านช่องทางภายนอก และประกาศไม่ได้เชื่อมกับผลแมตช์ที่บันทึก ใน DB จริงไม่มีประกาศเลย (0 แถว) |
| M12 | Admin dashboard | Admin | การ์ดนับ users/teams/scrims/matches/heroes, สมาชิกใหม่ 5 คน, แมตช์ล่าสุด 5 รายการ | → การ์ด | ทุกตาราง | `admin/index.php` | มีโค้ดแต่ยังไม่ยืนยัน (render ไม่มี error) | `admin/index.php:11-29` | แมตช์ล่าสุดไม่มีลิงก์ไปยังรายละเอียด |
| M13 | Admin: ผู้ใช้ | Admin | ค้นหา/กรอง role, สลับ user↔admin, ลบผู้ใช้ (ห้ามลบหรือสลับตัวเอง) | POST → `users` | users (+cascade) | `admin/users.php`, `actions/admin-process.php:17-37` | มีโค้ดแต่ยังไม่ยืนยัน (ยืนยันแล้วเฉพาะ: user ทั่วไปถูก redirect) | TC-RUN-12 | แก้ไขข้อมูลผู้ใช้หรือรีเซ็ตรหัสผ่านไม่ได้ และไม่มี audit log |
| M14 | Admin: ทีม | Admin | ค้นหา, ดูจำนวนสมาชิก/แมตช์/ชนะ/ซ้อมล่าสุด, ลบทีม | POST → `teams` | teams (+cascade) | `admin/teams.php`, `admin-process.php:40-45` | มีโค้ดแต่ยังไม่ยืนยัน | `admin/teams.php:20-30` | ไม่มีลิงก์ไปดูสมาชิกหรือแมตช์ของทีม (grep href แล้วไม่พบ) |
| M15 | Admin: ประกาศ Scrim | Admin | กรองสถานะ, ลบ | POST → `scrim_posts` | scrim_posts | `admin/scrims.php`, `admin-process.php:48-53` | มีโค้ดแต่ยังไม่ยืนยัน | — | — |
| M16 | Admin: Hero pool | Admin | ค้นหา/กรองตำแหน่ง, เพิ่ม, แก้ชื่อ (อัปเดตประวัติเดิมให้ตรงกัน), ลบ (ประวัติเดิมยังอยู่), แสดงจำนวนครั้งที่ถูกใช้ | POST → `heroes` (+ update ประวัติ) | heroes, match_player_stats, scrim_matches | `admin/heroes.php`, `admin-process.php:55-120` | มีโค้ดแต่ยังไม่ยืนยัน | `admin-process.php:99-108` | แก้ชื่อฮีโร่ใช้ transaction แต่ไม่มี try/rollback |
| M17 | โครงสร้างความปลอดภัย | ทุกคน | ตรวจการล็อกอินและ role จาก DB, CSRF token, `post_button()`, escape, `clean_url`, `.htaccess` | — | users | `config/functions.php`, `.htaccess` | ยืนยันแล้วบางส่วน | TC-RUN-02, 05, 07–10, 12 | ยังไม่ได้ทดสอบ CSRF ทีละ action |
| M18 | Redirect stub ระบบเก่า | — | 10 ไฟล์ทำหน้าที่ redirect ไปหน้าใหม่ | — | — | `user/dashboard.php`, `apply-team.php`, `find-scrim.php`, `manage-team-requests.php`, `my-team.php`, `scrim-history.php`, `setup-profile.php`, `team-stats.php`, `admin/dashboard.php`, `admin/manage-scrim.php` | มีโค้ดแต่ยังไม่ยืนยัน | อ่านไฟล์ทุกไฟล์แล้ว (3–4 บรรทัด) | **INFERENCE:** ชื่อไฟล์อย่าง `apply-team`, `manage-team-requests` บ่งบอกว่าระบบรุ่นก่อนเคยมี flow สมัครเข้าทีม ซึ่งไม่มีในรุ่นนี้แล้ว (ดู Q-02) |

## 2. ความสามารถที่ "ยังไม่พบ" ในโค้ด (FACT จาก grep)

| ความสามารถ | วิธีตรวจ |
|---|---|
| เปลี่ยนหรือรีเซ็ตรหัสผ่าน | grep `UPDATE users` พบแค่ `profile-edit.php:32` (ไม่แก้ password) และ `admin-process.php:34` (role) |
| สมาชิกทีมมีบัญชีเข้าสู่ระบบของตัวเอง / เชิญเข้าทีม | `team-process.php:95` ใช้ `user_id` ของหัวหน้าเสมอ |
| ผู้ช่วยหัวหน้าทีม / โค้ช / หลายผู้จัดการ | role enum มีแค่ `user`, `admin` |
| ตอบรับหรือจับคู่ Scrim ในระบบ | ไม่มีตารางหรือ action สำหรับการตอบรับ |
| เชื่อมประกาศ Scrim กับผลการซ้อม | ไม่มี `post_id` ใน `scrim_matches` |
| จัดกลุ่มเกมเป็นซีรีส์ | ไม่มีคอลัมน์ series |
| สถิติตามคู่แข่ง (head-to-head) | ไม่มี `GROUP BY opponent_name` |
| วิเคราะห์ฮีโร่ฝั่งตรงข้ามสำหรับผู้ใช้ | `enemy_hero_*` ใช้ใน match-detail, export และนับใน admin heroes เท่านั้น |
| สถิติแยกตามตำแหน่ง | ไม่มี `GROUP BY main_role/hero_role` |
| Pagination | ไม่มี `LIMIT`/`OFFSET` ในหน้ารายการหลัก |
| อัปโหลดไฟล์ | ไม่มี `$_FILES`/`move_uploaded_file` |
| อีเมลหรือการแจ้งเตือน | ไม่มี `mail()`/library ส่งเมล |
| Export ข้อมูล (CSV/Excel) | ไม่พบ |
| Audit log | ไม่พบ |
| ข้อมูลตัวอย่าง (seed) | SQL มีแค่ฮีโร่ |
| Automated tests | ไม่พบ |

## 3. เส้นทางการใช้งานหลัก (FACT จากเมนูใน `includes/header.php:38-55`)

- **User/หัวหน้า:** โปรไฟล์ & สถิติ → จัดการทีม → ประวัติการซ้อม → สถิติรายสัปดาห์/เดือน → หาทีมซ้อม
  หน้า `member-stats`, `member-info`, `match-add`, `match-detail` เข้าได้จากปุ่มภายในหน้าเท่านั้น ไม่อยู่ในเมนูหลัก
- **Admin:** ภาพรวม → จัดการสมาชิก → จัดการทีม → จัดการประกาศ Scrim → จัดการฮีโร่
- **Guest:** login / register / scrim lobby (ผ่าน URL ตรง เพราะเมนู guest ว่าง)
