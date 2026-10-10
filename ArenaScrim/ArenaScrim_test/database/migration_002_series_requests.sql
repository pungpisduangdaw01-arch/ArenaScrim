-- =====================================================================
-- Migration 002 — ซีรีส์ (C3), คำขอซ้อม (C4), Bo1–Bo7 (D-30),
--                 ลบโค้ชแล้วเก็บทีม (D-27/D-32), ตัวนับล็อกอินผิด (E2/D-37),
--                 ลบคอลัมน์ที่ไม่ใช้ (D-38)
-- ใช้กับฐานข้อมูลที่ติดตั้งจาก arenascrim_db.sql รุ่นก่อน 10 ต.ค. 2026
-- ต้องสำรองฐานข้อมูลก่อนรันทุกครั้ง (D-21) · ย้อนกลับด้วย rollback_002_series_requests.sql
-- =====================================================================

-- 1. ซีรีส์: กลุ่มของเกมที่ซ้อมกับคู่แข่งเดียวกันในนัดเดียว
CREATE TABLE scrim_series (
  series_id     INT(11)      NOT NULL AUTO_INCREMENT,
  team_id       INT(11)      NOT NULL,
  opponent_name VARCHAR(100) NOT NULL,
  best_of       TINYINT(1)   NOT NULL DEFAULT 3 COMMENT 'Bo1–Bo7 (D-30)',
  series_date   DATETIME     DEFAULT NULL COMMENT 'เวลานัด/เวลาเกมแรก',
  request_id    INT(11)      DEFAULT NULL COMMENT 'มาจากคำขอซ้อมที่ถูกรับ (C4)',
  created_at    TIMESTAMP    NOT NULL DEFAULT current_timestamp(),
  migrate_key   VARCHAR(255) DEFAULT NULL COMMENT 'ใช้ระหว่าง migration เท่านั้น',
  PRIMARY KEY (series_id),
  KEY team_id (team_id),
  KEY request_id (request_id),
  CONSTRAINT scrim_series_ibfk_1 FOREIGN KEY (team_id) REFERENCES teams (team_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE scrim_matches
  ADD COLUMN series_id INT(11) DEFAULT NULL AFTER team_id,
  ADD KEY series_id (series_id),
  ADD CONSTRAINT scrim_matches_ibfk_2 FOREIGN KEY (series_id) REFERENCES scrim_series (series_id) ON DELETE SET NULL;

-- 2. คำขอซ้อมจากประกาศ (D-18): หลายทีมขอได้ เจ้าของรับ 1 คำขอ
CREATE TABLE scrim_requests (
  request_id   INT(11)      NOT NULL AUTO_INCREMENT,
  post_id      INT(11)      NOT NULL,
  team_id      INT(11)      NOT NULL COMMENT 'ทีมที่ขอซ้อม',
  status       ENUM('pending','accepted','rejected','cancelled') NOT NULL DEFAULT 'pending',
  message      VARCHAR(255) DEFAULT NULL,
  created_at   TIMESTAMP    NOT NULL DEFAULT current_timestamp(),
  responded_at DATETIME     DEFAULT NULL,
  PRIMARY KEY (request_id),
  UNIQUE KEY post_team (post_id, team_id),
  KEY team_id (team_id),
  CONSTRAINT scrim_requests_ibfk_1 FOREIGN KEY (post_id) REFERENCES scrim_posts (post_id) ON DELETE CASCADE,
  CONSTRAINT scrim_requests_ibfk_2 FOREIGN KEY (team_id) REFERENCES teams (team_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE scrim_series
  ADD CONSTRAINT scrim_series_ibfk_2 FOREIGN KEY (request_id) REFERENCES scrim_requests (request_id) ON DELETE SET NULL;

-- 3. ประกาศหาทีมซ้อมรองรับ BO1–BO7 (D-30)
ALTER TABLE scrim_posts
  MODIFY match_format ENUM('BO1','BO2','BO3','BO4','BO5','BO6','BO7') DEFAULT 'BO3';

-- 4. ลบบัญชีโค้ชแล้วทีม ผู้เล่น และผลซ้อมยังอยู่ (D-27, D-32)
ALTER TABLE teams DROP FOREIGN KEY teams_ibfk_1;
ALTER TABLE teams
  MODIFY leader_id INT(11) DEFAULT NULL COMMENT 'โค้ชของทีม (NULL = ไม่มีโค้ช)',
  ADD CONSTRAINT teams_ibfk_1 FOREIGN KEY (leader_id) REFERENCES users (user_id) ON DELETE SET NULL;

ALTER TABLE team_members DROP FOREIGN KEY team_members_ibfk_2;
ALTER TABLE team_members
  MODIFY user_id INT(11) DEFAULT NULL COMMENT 'บัญชีที่เพิ่มรายชื่อ (ไม่ใช่บัญชีของผู้เล่น — D-11)',
  ADD CONSTRAINT team_members_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE SET NULL;

-- 5. ตัวนับล็อกอินผิด (E2 / D-37: 5 ครั้งต่อ 15 นาที ต่อ username/email)
CREATE TABLE login_attempts (
  attempt_id   INT(11)      NOT NULL AUTO_INCREMENT,
  login_key    VARCHAR(100) NOT NULL COMMENT 'username/email ที่กรอก (ตัวพิมพ์เล็ก)',
  attempted_at DATETIME     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (attempt_id),
  KEY login_key (login_key, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. ลบคอลัมน์ที่ไม่มีโค้ดใช้ (D-38) — ตรวจแล้วไม่มีข้อมูลในคอลัมน์ทั้งสอง
ALTER TABLE team_members DROP COLUMN position;
ALTER TABLE match_player_stats DROP COLUMN hero;

-- 7. จัดเกมเดิมเข้าซีรีส์อัตโนมัติ (D-15, D-35)
--    กลุ่มเดียวกัน = ทีม + คู่แข่ง (ไม่สนตัวพิมพ์/ช่องว่างหัวท้าย) + วันเดียวกัน + Bo เดียวกัน
--    เรียงตามเวลา และเริ่มซีรีส์ใหม่เมื่อ "เกมที่" ไม่มากกว่าเกมก่อนหน้า (เช่น กลับไปเป็น 1)
CREATE TEMPORARY TABLE tmp_series_map AS
SELECT match_id, team_id, opponent_name, match_type, match_date,
       CONCAT_WS('|', team_id, opp_key, DATE(match_date), match_type,
                 SUM(new_series) OVER (PARTITION BY team_id, opp_key, DATE(match_date), match_type
                                       ORDER BY match_date, match_id)) AS grp
FROM (
    SELECT m.*, LOWER(TRIM(m.opponent_name)) AS opp_key,
           CASE WHEN LAG(m.game_number) OVER w IS NULL OR m.game_number <= LAG(m.game_number) OVER w THEN 1 ELSE 0 END AS new_series
    FROM scrim_matches m
    WINDOW w AS (PARTITION BY m.team_id, LOWER(TRIM(m.opponent_name)), DATE(m.match_date), m.match_type ORDER BY m.match_date, m.match_id)
) x;

INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date, migrate_key)
SELECT team_id, MIN(opponent_name),
       LEAST(7, GREATEST(1, CAST(SUBSTRING(MIN(match_type), 3) AS UNSIGNED))),
       MIN(match_date), grp
FROM tmp_series_map
GROUP BY grp;

UPDATE scrim_matches m
JOIN tmp_series_map t ON t.match_id = m.match_id
JOIN scrim_series s ON s.migrate_key = t.grp
SET m.series_id = s.series_id;

DROP TEMPORARY TABLE tmp_series_map;
ALTER TABLE scrim_series DROP COLUMN migrate_key;
