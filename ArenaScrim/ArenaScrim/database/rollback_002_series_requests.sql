-- =====================================================================
-- Rollback ของ Migration 002 — คืนโครงสร้างกลับเป็นรุ่นก่อน 10 ต.ค. 2026
-- หมายเหตุ:
--   * ข้อมูลซีรีส์ คำขอซ้อม และตัวนับล็อกอินจะหายไปทั้งหมด
--   * ทีมที่ไม่มีโค้ช (leader_id = NULL) ต้องลบหรือกำหนดโค้ชก่อน ไม่เช่นนั้นข้อ 4 จะล้มเหลว
--   * ประกาศที่ใช้ BO2/BO4/BO6/BO7 ต้องแก้ก่อน ไม่เช่นนั้นข้อ 3 จะล้มเหลว
--   * วิธีย้อนกลับที่แนะนำที่สุดคือกู้จากไฟล์สำรองที่ทำก่อนรัน migration
-- =====================================================================

-- 6. คอลัมน์ที่ถูกลบ (คืนเฉพาะโครงสร้าง ไม่มีข้อมูลเดิมให้คืน)
ALTER TABLE match_player_stats ADD COLUMN hero VARCHAR(50) DEFAULT NULL AFTER member_id;
ALTER TABLE team_members ADD COLUMN position VARCHAR(50) DEFAULT '' AFTER joined_at;

-- 5. ตัวนับล็อกอินผิด
DROP TABLE login_attempts;

-- 4. FK เดิม (ลบโค้ชแล้วลบทีม)
ALTER TABLE team_members DROP FOREIGN KEY team_members_ibfk_2;
ALTER TABLE team_members
  MODIFY user_id INT(11) NOT NULL,
  ADD CONSTRAINT team_members_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE;
ALTER TABLE teams DROP FOREIGN KEY teams_ibfk_1;
ALTER TABLE teams
  MODIFY leader_id INT(11) NOT NULL,
  ADD CONSTRAINT teams_ibfk_1 FOREIGN KEY (leader_id) REFERENCES users (user_id) ON DELETE CASCADE;

-- 3. BO1/BO3/BO5
ALTER TABLE scrim_posts MODIFY match_format ENUM('BO1','BO3','BO5') DEFAULT 'BO3';

-- 1–2. ซีรีส์และคำขอซ้อม
ALTER TABLE scrim_matches DROP FOREIGN KEY scrim_matches_ibfk_2;
ALTER TABLE scrim_matches DROP KEY series_id, DROP COLUMN series_id;
ALTER TABLE scrim_series DROP FOREIGN KEY scrim_series_ibfk_2;
DROP TABLE scrim_requests;
DROP TABLE scrim_series;
