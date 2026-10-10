-- =====================================================================
-- ArenaScrim — ข้อมูลตัวอย่างสำหรับสาธิต (สังเคราะห์ทั้งหมด, D-36)
-- ใช้กับฐานข้อมูลที่ติดตั้งจาก arenascrim_db.sql (schema หลัง migration 002) เท่านั้น
-- บัญชีสาธิตทุกบัญชีใช้รหัสผ่าน demo1234 : demo_admin, demo_coach1, demo_coach2, demo_coach3
-- วันที่ทั้งหมดอ้างอิงจากวันที่ import (NOW() - n วัน) เพื่อให้กราฟและหน้าเทียบช่วงเวลามีข้อมูลเสมอ
-- =====================================================================
SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO users (username, email, password, display_name, role) VALUES ('demo_admin', 'demo_admin@example.com', '$2y$10$xNO3MhorHewxTNv2qdhCLu4RBBuW0UobBhQ93lVgQLyBpHhlZ5TPC', 'ผู้ดูแลระบบ (สาธิต)', 'admin');

-- ---------- ทีม Demo Phoenix ----------
INSERT INTO users (username, email, password, display_name) VALUES ('demo_coach1', 'demo_coach1@example.com', '$2y$10$xNO3MhorHewxTNv2qdhCLu4RBBuW0UobBhQ93lVgQLyBpHhlZ5TPC', 'โค้ชฟีนิกซ์');
SET @u1 = LAST_INSERT_ID();
INSERT INTO teams (team_name, team_tag, leader_id, discord_link, line_id) VALUES ('Demo Phoenix', 'PHX', @u1, 'https://discord.gg/phxdemo', '@phxdemo');
SET @t1 = LAST_INSERT_ID();
INSERT INTO team_members (team_id, user_id, in_game_name, main_role, current_rank) VALUES (@t1, @u1, 'Blaze', 'Dark Slayer', 'Supreme Conqueror');
SET @t1m1 = LAST_INSERT_ID();
INSERT INTO team_members (team_id, user_id, in_game_name, main_role, current_rank) VALUES (@t1, @u1, 'Ember', 'Jungle', 'Conqueror');
SET @t1m2 = LAST_INSERT_ID();
INSERT INTO team_members (team_id, user_id, in_game_name, main_role, current_rank) VALUES (@t1, @u1, 'Ashen', 'Mid Lane', 'Glorious Ruler');
SET @t1m3 = LAST_INSERT_ID();
INSERT INTO team_members (team_id, user_id, in_game_name, main_role, current_rank) VALUES (@t1, @u1, 'Flare', 'Dragon Lane', 'Conqueror');
SET @t1m4 = LAST_INSERT_ID();
INSERT INTO team_members (team_id, user_id, in_game_name, main_role, current_rank) VALUES (@t1, @u1, 'Cinder', 'Support', 'Glorious Ruler');
SET @t1m5 = LAST_INSERT_ID();
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t1, 'Bacon Time', 1, CURDATE() - INTERVAL 40 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t1, @s, 'Bacon Time', 'WIN', CURDATE() - INTERVAL 40 DAY + INTERVAL 20 HOUR, 'Bo1', 1, '12:41', 'ต้องวางวอร์ดก่อนนาทีที่ 2', 'Violet', 'Arum', 'Laville', 'Zephys', 'Nakroth', 4, 0, 1, 3, 0, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t1m1, 'Zuka', 2, 2, 2, 10.7, 9233, 69896, 53985, 33.3, 19.61, 26.47, 18.98),
  (@m, @t1m2, 'Aoi', 2, 6, 9, 12.6, 10595, 38577, 49966, 91.7, 22.51, 14.61, 17.57),
  (@m, @t1m3, 'Iggy', 1, 3, 8, 8.7, 10453, 42956, 64779, 75.0, 22.21, 16.27, 22.77),
  (@m, @t1m4, 'Capheny', 2, 2, 4, 7.7, 6320, 23983, 78192, 50.0, 13.43, 9.08, 27.49),
  (@m, @t1m5, 'Thane', 5, 5, 13, 9.1, 10473, 88647, 37513, 100.0, 22.25, 33.57, 13.19);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t1, 'Bacon Time', 3, CURDATE() - INTERVAL 34 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t1, @s, 'Bacon Time', 'WIN', CURDATE() - INTERVAL 34 DAY + INTERVAL 20 HOUR, 'Bo3', 1, '20:54', 'ต้องวางวอร์ดก่อนนาทีที่ 2', 'Florentino', 'Allain', 'Ormarr', 'Lumburr', 'Hayate', 3, 0, 0, 3, 1, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t1m1, 'Zuka', 5, 2, 10, 13.2, 13208, 43407, 79427, 68.2, 29.21, 15.44, 32.31),
  (@m, @t1m2, 'Nakroth', 6, 5, 12, 6.1, 8125, 34036, 14807, 81.8, 17.97, 12.11, 6.02),
  (@m, @t1m3, 'Iggy', 4, 2, 4, 9.7, 11546, 89886, 78006, 36.4, 25.53, 31.98, 31.74),
  (@m, @t1m4, 'Yorn', 2, 0, 5, 6.2, 6333, 83981, 34335, 31.8, 14.0, 29.88, 13.97),
  (@m, @t1m5, 'Thane', 5, 0, 12, 9.8, 6012, 29786, 39218, 77.3, 13.29, 10.6, 15.96);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t1, @s, 'Bacon Time', 'WIN', CURDATE() - INTERVAL 34 DAY + INTERVAL 21 HOUR, 'Bo3', 2, '12:45', 'ไฟต์มังกรตัวแรกสื่อสารพลาด', 'Lumburr', 'Florentino', 'Nakroth', 'Iggy', 'Liliana', 1, 3, 2, 1, 1, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t1m1, 'Qi', 4, 2, 10, 8.5, 11279, 68151, 62482, 38.9, 18.28, 27.7, 26.21),
  (@m, @t1m2, 'Aoi', 8, 0, 5, 11.3, 11954, 41367, 69595, 36.1, 19.38, 16.81, 29.19),
  (@m, @t1m3, 'Raz', 7, 5, 13, 9.7, 11953, 21421, 25594, 55.6, 19.38, 8.71, 10.73),
  (@m, @t1m4, 'Yorn', 8, 1, 13, 9.5, 13346, 54012, 39895, 58.3, 21.63, 21.95, 16.73),
  (@m, @t1m5, 'Grakk', 9, 4, 9, 8.5, 13156, 61125, 40866, 50.0, 21.33, 24.84, 17.14);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t1, @s, 'Bacon Time', 'LOSS', CURDATE() - INTERVAL 34 DAY + INTERVAL 22 HOUR, 'Bo3', 3, '19:05', NULL, 'Lauriel', 'Yena', 'Qi', 'Krixi', 'Ormarr', 1, 3, 1, 8, 1, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t1m1, 'Allain', 4, 4, 11, 9.8, 7545, 64799, 25022, 71.4, 18.32, 23.14, 27.67),
  (@m, @t1m2, 'Aoi', 7, 2, 7, 9.2, 9450, 86806, 15136, 66.7, 22.94, 31.0, 16.74),
  (@m, @t1m3, 'Iggy', 2, 5, 9, 13.2, 6001, 27161, 21893, 52.4, 14.57, 9.7, 24.21),
  (@m, @t1m4, 'Hayate', 7, 1, 2, 11.7, 10118, 27040, 17017, 42.9, 24.56, 9.66, 18.82),
  (@m, @t1m5, 'Zip', 1, 2, 2, 12.3, 8077, 74228, 11367, 14.3, 19.61, 26.51, 12.57);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t1, 'Demo Tiger', 3, CURDATE() - INTERVAL 29 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t1, @s, 'Demo Tiger', 'LOSS', CURDATE() - INTERVAL 29 DAY + INTERVAL 20 HOUR, 'Bo3', 1, '15:11', 'ไฟต์มังกรตัวแรกสื่อสารพลาด', 'Violet', 'Ormarr', 'Zephys', 'Raz', 'Florentino', 3, 1, 1, 5, 1, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t1m1, 'Florentino', 9, 1, 12, 11.9, 10592, 47402, 41375, 67.7, 22.89, 18.03, 21.39),
  (@m, @t1m2, 'Zephys', 4, 3, 14, 13.0, 11176, 50333, 57638, 58.1, 24.15, 19.14, 29.8),
  (@m, @t1m3, 'Raz', 5, 6, 12, 13.1, 8068, 85763, 12048, 54.8, 17.44, 32.62, 6.23),
  (@m, @t1m4, 'Yorn', 9, 6, 5, 11.8, 8719, 33290, 68586, 45.2, 18.84, 12.66, 35.46),
  (@m, @t1m5, 'Thane', 4, 3, 3, 10.4, 7715, 46156, 13797, 22.6, 16.67, 17.55, 7.13);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t1, @s, 'Demo Tiger', 'WIN', CURDATE() - INTERVAL 29 DAY + INTERVAL 21 HOUR, 'Bo3', 2, '12:40', NULL, 'Allain', 'Lauriel', 'Nakroth', 'Zephys', 'Hayate', 4, 2, 0, 3, 0, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t1m1, 'Yena', 7, 2, 9, 7.0, 11630, 53809, 71327, 57.1, 24.44, 24.62, 22.31),
  (@m, @t1m2, 'Enzo', 8, 2, 13, 6.6, 8847, 15202, 49500, 75.0, 18.59, 6.96, 15.48),
  (@m, @t1m3, 'Raz', 9, 3, 12, 5.1, 7631, 32369, 78241, 75.0, 16.04, 14.81, 24.47),
  (@m, @t1m4, 'Elsu', 1, 1, 14, 13.0, 9979, 44353, 68463, 53.6, 20.97, 20.29, 21.41),
  (@m, @t1m5, 'Arum', 3, 6, 4, 9.9, 9495, 72819, 52219, 25.0, 19.96, 33.32, 16.33);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t1, 'Demo Kraken', 3, CURDATE() - INTERVAL 23 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t1, @s, 'Demo Kraken', 'LOSS', CURDATE() - INTERVAL 23 DAY + INTERVAL 20 HOUR, 'Bo3', 1, '18:16', 'ไฟต์มังกรตัวแรกสื่อสารพลาด', 'Yorn', 'Florentino', 'Iggy', 'Qi', 'Liliana', 1, 1, 2, 2, 1, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t1m1, 'Zuka', 9, 0, 8, 7.3, 13298, 45648, 58489, 81.0, 27.59, 18.6, 41.64),
  (@m, @t1m2, 'Murad', 3, 2, 12, 9.8, 12632, 32095, 13941, 71.4, 26.21, 13.07, 9.93),
  (@m, @t1m3, 'Iggy', 4, 2, 2, 8.9, 6024, 23762, 28219, 28.6, 12.5, 9.68, 20.09),
  (@m, @t1m4, 'Hayate', 2, 5, 10, 12.5, 6937, 81145, 21885, 57.1, 14.39, 33.06, 15.58),
  (@m, @t1m5, 'Zip', 3, 1, 6, 12.0, 9304, 62834, 17922, 42.9, 19.3, 25.6, 12.76);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t1, @s, 'Demo Kraken', 'WIN', CURDATE() - INTERVAL 23 DAY + INTERVAL 21 HOUR, 'Bo3', 2, '12:58', NULL, 'Violet', 'Iggy', 'Grakk', 'Lumburr', 'Elsu', 4, 1, 0, 9, 0, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t1m1, 'Qi', 2, 4, 6, 8.4, 8399, 50570, 40879, 34.8, 16.05, 23.81, 20.5),
  (@m, @t1m2, 'Murad', 9, 0, 7, 9.1, 13846, 53951, 25168, 69.6, 26.45, 25.4, 12.62),
  (@m, @t1m3, 'Lauriel', 6, 4, 11, 8.0, 13649, 59380, 61531, 73.9, 26.08, 27.96, 30.86),
  (@m, @t1m4, 'Hayate', 2, 6, 2, 8.7, 8876, 17047, 23705, 17.4, 16.96, 8.03, 11.89),
  (@m, @t1m5, 'Grakk', 4, 3, 6, 13.1, 7571, 31447, 48102, 43.5, 14.46, 14.81, 24.13);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t1, @s, 'Demo Kraken', 'LOSS', CURDATE() - INTERVAL 23 DAY + INTERVAL 22 HOUR, 'Bo3', 3, '24:07', 'ดราฟต์ดีขึ้น คุมเลนกลางได้', 'Enzo', 'Zip', 'Violet', 'Lauriel', 'Zephys', 2, 3, 2, 8, 1, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t1m1, 'Allain', 5, 2, 6, 7.5, 7881, 39187, 57151, 52.4, 18.94, 16.0, 24.78),
  (@m, @t1m2, 'Nakroth', 7, 2, 14, 12.7, 9679, 34313, 22150, 100.0, 23.26, 14.01, 9.61),
  (@m, @t1m3, 'Liliana', 1, 0, 7, 12.9, 7921, 66323, 10956, 38.1, 19.03, 27.07, 4.75),
  (@m, @t1m4, 'Hayate', 1, 0, 14, 13.3, 7282, 54392, 65574, 71.4, 17.5, 22.2, 28.44),
  (@m, @t1m5, 'Grakk', 7, 6, 3, 11.4, 8856, 50751, 74764, 47.6, 21.28, 20.72, 32.42);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t1, 'eArena', 1, CURDATE() - INTERVAL 18 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t1, @s, 'eArena', 'WIN', CURDATE() - INTERVAL 18 DAY + INTERVAL 20 HOUR, 'Bo1', 1, '21:36', NULL, 'Elsu', 'Hayate', 'Zephys', 'Lauriel', 'Zip', 3, 3, 2, 7, 1, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t1m1, 'Qi', 4, 2, 12, 9.3, 10406, 33779, 35623, 69.6, 19.01, 12.2, 16.13),
  (@m, @t1m2, 'Enzo', 3, 5, 6, 6.1, 11738, 79180, 54111, 39.1, 21.45, 28.6, 24.49),
  (@m, @t1m3, 'Liliana', 3, 2, 4, 6.1, 8877, 37449, 20651, 30.4, 16.22, 13.53, 9.35),
  (@m, @t1m4, 'Yorn', 7, 4, 2, 9.5, 11094, 88239, 63704, 39.1, 20.27, 31.87, 28.84),
  (@m, @t1m5, 'Zip', 6, 1, 10, 10.3, 12616, 38211, 46826, 69.6, 23.05, 13.8, 21.2);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t1, 'eArena', 3, CURDATE() - INTERVAL 12 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t1, @s, 'eArena', 'LOSS', CURDATE() - INTERVAL 12 DAY + INTERVAL 20 HOUR, 'Bo3', 1, '18:18', 'ต้องวางวอร์ดก่อนนาทีที่ 2', 'Zephys', 'Hayate', 'Aoi', 'Thane', 'Butterfly', 0, 0, 1, 9, 0, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t1m1, 'Yena', 7, 2, 8, 9.9, 8153, 18993, 77634, 53.6, 17.47, 8.75, 29.78),
  (@m, @t1m2, 'Aoi', 1, 6, 5, 10.5, 10581, 53472, 19458, 21.4, 22.68, 24.64, 7.46),
  (@m, @t1m3, 'Liliana', 8, 0, 13, 8.1, 7442, 20753, 60474, 75.0, 15.95, 9.56, 23.2),
  (@m, @t1m4, 'Yorn', 6, 3, 13, 12.1, 11600, 81229, 74320, 67.9, 24.86, 37.42, 28.51),
  (@m, @t1m5, 'Zip', 6, 6, 6, 9.6, 8881, 42609, 28786, 42.9, 19.03, 19.63, 11.04);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t1, @s, 'eArena', 'LOSS', CURDATE() - INTERVAL 12 DAY + INTERVAL 21 HOUR, 'Bo3', 2, '13:48', NULL, 'Butterfly', 'Grakk', 'Nakroth', 'Iggy', 'Lauriel', 1, 2, 2, 3, 1, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t1m1, 'Qi', 6, 3, 3, 8.9, 9622, 18485, 17524, 40.9, 17.87, 7.67, 6.95),
  (@m, @t1m2, 'Nakroth', 1, 0, 5, 13.4, 9040, 43880, 55021, 27.3, 16.79, 18.2, 21.83),
  (@m, @t1m3, 'Lauriel', 4, 3, 14, 9.5, 10514, 29453, 63717, 81.8, 19.53, 12.21, 25.28),
  (@m, @t1m4, 'Capheny', 2, 6, 14, 5.5, 12905, 69155, 37241, 72.7, 23.97, 28.68, 14.77),
  (@m, @t1m5, 'Grakk', 9, 3, 3, 7.9, 11751, 80175, 78575, 54.5, 21.83, 33.25, 31.17);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t1, @s, 'eArena', 'LOSS', CURDATE() - INTERVAL 12 DAY + INTERVAL 22 HOUR, 'Bo3', 3, '15:20', 'ดราฟต์ดีขึ้น คุมเลนกลางได้', 'Violet', 'Allain', 'Grakk', 'Florentino', 'Ormarr', 1, 1, 0, 4, 1, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t1m1, 'Florentino', 7, 2, 9, 9.2, 9453, 26695, 35225, 72.7, 20.52, 10.0, 11.6),
  (@m, @t1m2, 'Murad', 2, 2, 4, 10.7, 6227, 49589, 56151, 27.3, 13.52, 18.58, 18.49),
  (@m, @t1m3, 'Tulen', 6, 2, 14, 12.3, 13628, 47350, 75152, 90.9, 29.58, 17.74, 24.75),
  (@m, @t1m4, 'Capheny', 6, 4, 8, 8.9, 9695, 59521, 74240, 63.6, 21.04, 22.3, 24.45),
  (@m, @t1m5, 'Thane', 1, 3, 13, 10.0, 7068, 83798, 62858, 63.6, 15.34, 31.39, 20.7);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t1, 'Demo Tiger', 1, CURDATE() - INTERVAL 7 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t1, @s, 'Demo Tiger', 'LOSS', CURDATE() - INTERVAL 7 DAY + INTERVAL 20 HOUR, 'Bo1', 1, '20:25', NULL, 'Tulen', 'Murad', 'Qi', 'Arum', 'Yorn', 0, 1, 1, 5, 0, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t1m1, 'Yena', 5, 2, 13, 7.4, 12131, 26162, 31169, 72.0, 21.56, 9.07, 13.11),
  (@m, @t1m2, 'Enzo', 7, 2, 10, 12.0, 11132, 73785, 65273, 68.0, 19.79, 25.59, 27.45),
  (@m, @t1m3, 'Iggy', 3, 3, 9, 12.1, 12744, 64022, 30565, 48.0, 22.65, 22.2, 12.85),
  (@m, @t1m4, 'Elsu', 1, 2, 3, 5.5, 7038, 82690, 71334, 16.0, 12.51, 28.68, 30.0),
  (@m, @t1m5, 'Arum', 9, 1, 3, 7.8, 13209, 41694, 39428, 48.0, 23.48, 14.46, 16.58);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t1, 'Bacon Time', 1, CURDATE() - INTERVAL 1 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t1, @s, 'Bacon Time', 'WIN', CURDATE() - INTERVAL 1 DAY + INTERVAL 20 HOUR, 'Bo1', 1, '13:28', 'ไฟต์มังกรตัวแรกสื่อสารพลาด', 'Nakroth', 'Elsu', 'Zip', 'Butterfly', 'Enzo', 4, 2, 0, 6, 1, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t1m1, 'Florentino', 5, 2, 3, 13.2, 9176, 53539, 52779, 25.8, 18.95, 24.43, 20.79),
  (@m, @t1m2, 'Zephys', 8, 0, 2, 6.2, 7265, 79213, 29826, 32.3, 15.0, 36.15, 11.75),
  (@m, @t1m3, 'Lauriel', 5, 6, 5, 13.2, 11393, 30832, 29632, 32.3, 23.52, 14.07, 11.67),
  (@m, @t1m4, 'Capheny', 4, 3, 14, 12.9, 10167, 34256, 68456, 58.1, 20.99, 15.63, 26.97),
  (@m, @t1m5, 'Thane', 9, 6, 8, 9.8, 10431, 21283, 73122, 54.8, 21.54, 9.71, 28.81);

-- ---------- ทีม Demo Kraken ----------
INSERT INTO users (username, email, password, display_name) VALUES ('demo_coach2', 'demo_coach2@example.com', '$2y$10$xNO3MhorHewxTNv2qdhCLu4RBBuW0UobBhQ93lVgQLyBpHhlZ5TPC', 'โค้ชคราเคน');
SET @u2 = LAST_INSERT_ID();
INSERT INTO teams (team_name, team_tag, leader_id, discord_link, line_id) VALUES ('Demo Kraken', 'KRK', @u2, 'https://discord.gg/krkdemo', '@krkdemo');
SET @t2 = LAST_INSERT_ID();
INSERT INTO team_members (team_id, user_id, in_game_name, main_role, current_rank) VALUES (@t2, @u2, 'Tide', 'Dark Slayer', 'Conqueror');
SET @t2m1 = LAST_INSERT_ID();
INSERT INTO team_members (team_id, user_id, in_game_name, main_role, current_rank) VALUES (@t2, @u2, 'Abyss', 'Jungle', 'Supreme Conqueror');
SET @t2m2 = LAST_INSERT_ID();
INSERT INTO team_members (team_id, user_id, in_game_name, main_role, current_rank) VALUES (@t2, @u2, 'Coral', 'Mid Lane', 'Conqueror');
SET @t2m3 = LAST_INSERT_ID();
INSERT INTO team_members (team_id, user_id, in_game_name, main_role, current_rank) VALUES (@t2, @u2, 'Riptide', 'Dragon Lane', 'Supreme Conqueror');
SET @t2m4 = LAST_INSERT_ID();
INSERT INTO team_members (team_id, user_id, in_game_name, main_role, current_rank) VALUES (@t2, @u2, 'Shoal', 'Support', 'Glorious Ruler');
SET @t2m5 = LAST_INSERT_ID();
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t2, 'King of Gamers', 3, CURDATE() - INTERVAL 40 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t2, @s, 'King of Gamers', 'WIN', CURDATE() - INTERVAL 40 DAY + INTERVAL 20 HOUR, 'Bo3', 1, '16:41', 'ดราฟต์ดีขึ้น คุมเลนกลางได้', 'Ormarr', 'Capheny', 'Thane', 'Elsu', 'Iggy', 4, 0, 0, 7, 1, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t2m1, 'Allain', 6, 1, 11, 8.7, 11277, 81656, 26404, 81.0, 25.76, 38.22, 13.76),
  (@m, @t2m2, 'Murad', 3, 4, 5, 12.1, 8380, 62350, 47869, 38.1, 19.14, 29.19, 24.94),
  (@m, @t2m3, 'Raz', 9, 2, 3, 7.1, 6599, 29829, 19309, 57.1, 15.07, 13.96, 10.06),
  (@m, @t2m4, 'Capheny', 1, 6, 8, 13.2, 8074, 16968, 49714, 42.9, 18.44, 7.94, 25.9),
  (@m, @t2m5, 'Alice', 2, 6, 6, 5.4, 9451, 22834, 48629, 38.1, 21.59, 10.69, 25.34);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t2, @s, 'King of Gamers', 'WIN', CURDATE() - INTERVAL 40 DAY + INTERVAL 21 HOUR, 'Bo3', 2, '17:46', 'ไฟต์มังกรตัวแรกสื่อสารพลาด', 'Nakroth', 'Alice', 'Allain', 'Raz', 'Zephys', 1, 0, 0, 5, 1, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t2m1, 'Zuka', 1, 0, 7, 12.6, 6638, 49818, 15468, 100.0, 13.34, 16.8, 7.39),
  (@m, @t2m2, 'Murad', 1, 1, 4, 8.0, 13054, 47460, 36222, 62.5, 26.23, 16.01, 17.3),
  (@m, @t2m3, 'Raz', 2, 5, 2, 6.4, 10427, 53064, 45253, 50.0, 20.95, 17.9, 21.61),
  (@m, @t2m4, 'Violet', 3, 5, 3, 12.6, 13432, 74463, 41792, 75.0, 26.99, 25.11, 19.96),
  (@m, @t2m5, 'Grakk', 1, 6, 10, 8.7, 6211, 71693, 70687, 100.0, 12.48, 24.18, 33.75);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t2, 'eArena', 1, CURDATE() - INTERVAL 35 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t2, @s, 'eArena', 'WIN', CURDATE() - INTERVAL 35 DAY + INTERVAL 20 HOUR, 'Bo1', 1, '14:35', NULL, 'Enzo', 'Lumburr', 'Zip', 'Qi', 'Hayate', 2, 0, 0, 2, 1, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t2m1, 'Yena', 5, 5, 10, 11.1, 10694, 80613, 73956, 40.5, 21.38, 27.07, 36.57),
  (@m, @t2m2, 'Aoi', 8, 4, 8, 12.7, 11122, 56727, 39981, 43.2, 22.24, 19.05, 19.77),
  (@m, @t2m3, 'Raz', 9, 3, 9, 8.5, 11320, 74658, 10154, 48.6, 22.64, 25.07, 5.02),
  (@m, @t2m4, 'Elsu', 9, 6, 10, 5.7, 10171, 17828, 42030, 51.4, 20.34, 5.99, 20.78),
  (@m, @t2m5, 'Alice', 6, 0, 6, 12.4, 6701, 67951, 36105, 32.4, 13.4, 22.82, 17.85);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t2, 'Full Sense', 1, CURDATE() - INTERVAL 30 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t2, @s, 'Full Sense', 'WIN', CURDATE() - INTERVAL 30 DAY + INTERVAL 20 HOUR, 'Bo1', 1, '20:44', 'ดราฟต์ดีขึ้น คุมเลนกลางได้', 'Lumburr', 'Aoi', 'Zephys', 'Capheny', 'Florentino', 1, 3, 1, 7, 0, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t2m1, 'Florentino', 5, 3, 8, 13.0, 8593, 59736, 14470, 41.9, 17.02, 18.76, 5.52),
  (@m, @t2m2, 'Murad', 5, 6, 12, 5.2, 7167, 25144, 61235, 54.8, 14.19, 7.89, 23.35),
  (@m, @t2m3, 'Lauriel', 8, 0, 6, 8.3, 11532, 79298, 71822, 45.2, 22.84, 24.9, 27.39),
  (@m, @t2m4, 'Elsu', 7, 0, 10, 6.8, 13649, 87619, 60802, 54.8, 27.03, 27.51, 23.19),
  (@m, @t2m5, 'Arum', 6, 0, 4, 9.4, 9559, 66704, 53875, 32.3, 18.93, 20.94, 20.55);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t2, 'eArena', 3, CURDATE() - INTERVAL 25 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t2, @s, 'eArena', 'LOSS', CURDATE() - INTERVAL 25 DAY + INTERVAL 20 HOUR, 'Bo3', 1, '19:16', NULL, 'Krixi', 'Butterfly', 'Laville', 'Enzo', 'Aoi', 1, 3, 1, 1, 1, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t2m1, 'Florentino', 6, 4, 7, 10.8, 13626, 88929, 28199, 40.6, 28.92, 27.77, 10.89),
  (@m, @t2m2, 'Murad', 8, 0, 3, 12.3, 8916, 48062, 13224, 34.4, 18.93, 15.01, 5.11),
  (@m, @t2m3, 'Iggy', 6, 0, 3, 8.7, 6739, 49609, 74820, 28.1, 14.31, 15.49, 28.89),
  (@m, @t2m4, 'Violet', 9, 2, 12, 13.3, 9089, 85684, 73409, 65.6, 19.29, 26.76, 28.35),
  (@m, @t2m5, 'Arum', 3, 1, 5, 11.5, 8739, 47953, 69309, 25.0, 18.55, 14.97, 26.76);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t2, @s, 'eArena', 'WIN', CURDATE() - INTERVAL 25 DAY + INTERVAL 21 HOUR, 'Bo3', 2, '14:38', 'ดราฟต์ดีขึ้น คุมเลนกลางได้', 'Tulen', 'Enzo', 'Lumburr', 'Zuka', 'Lauriel', 4, 3, 1, 7, 1, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t2m1, 'Zuka', 7, 5, 7, 12.8, 10468, 51054, 79674, 48.3, 22.37, 26.06, 34.62),
  (@m, @t2m2, 'Murad', 3, 6, 8, 10.2, 13862, 19112, 38384, 37.9, 29.62, 9.75, 16.68),
  (@m, @t2m3, 'Liliana', 7, 6, 4, 9.4, 8662, 46098, 15929, 37.9, 18.51, 23.53, 6.92),
  (@m, @t2m4, 'Yorn', 4, 4, 11, 12.4, 7618, 35919, 52393, 51.7, 16.28, 18.33, 22.76),
  (@m, @t2m5, 'Grakk', 8, 2, 7, 12.7, 6188, 43741, 43778, 51.7, 13.22, 22.33, 19.02);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t2, 'Demo Tiger', 3, CURDATE() - INTERVAL 20 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t2, @s, 'Demo Tiger', 'LOSS', CURDATE() - INTERVAL 20 DAY + INTERVAL 20 HOUR, 'Bo3', 1, '14:45', 'ต้องวางวอร์ดก่อนนาทีที่ 2', 'Lauriel', 'Yena', 'Liliana', 'Violet', 'Thane', 0, 3, 2, 6, 1, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t2m1, 'Yena', 1, 5, 5, 5.5, 11506, 71976, 35506, 33.3, 21.93, 29.02, 18.46),
  (@m, @t2m2, 'Zephys', 6, 3, 5, 11.0, 7744, 43168, 31370, 61.1, 14.76, 17.41, 16.31),
  (@m, @t2m3, 'Tulen', 3, 6, 2, 13.3, 12781, 30752, 32724, 27.8, 24.36, 12.4, 17.01),
  (@m, @t2m4, 'Hayate', 3, 3, 5, 11.8, 12842, 67620, 56765, 44.4, 24.47, 27.26, 29.51),
  (@m, @t2m5, 'Grakk', 5, 2, 13, 8.5, 7604, 34504, 36003, 100.0, 14.49, 13.91, 18.72);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t2, @s, 'Demo Tiger', 'LOSS', CURDATE() - INTERVAL 20 DAY + INTERVAL 21 HOUR, 'Bo3', 2, '18:51', 'ต้องวางวอร์ดก่อนนาทีที่ 2', 'Aoi', 'Yena', 'Grakk', 'Lauriel', 'Nakroth', 4, 3, 2, 6, 1, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t2m1, 'Qi', 1, 5, 10, 9.3, 11093, 62174, 50412, 57.9, 25.55, 21.52, 21.49),
  (@m, @t2m2, 'Aoi', 4, 3, 4, 12.7, 6611, 28979, 15361, 42.1, 15.23, 10.03, 6.55),
  (@m, @t2m3, 'Liliana', 1, 0, 9, 11.6, 10278, 78304, 62385, 52.6, 23.67, 27.1, 26.6),
  (@m, @t2m4, 'Capheny', 8, 4, 12, 9.6, 6549, 36472, 72598, 100.0, 15.08, 12.62, 30.95),
  (@m, @t2m5, 'Zip', 5, 0, 8, 12.3, 8888, 83005, 33815, 68.4, 20.47, 28.73, 14.42);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t2, 'eArena', 1, CURDATE() - INTERVAL 16 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t2, @s, 'eArena', 'LOSS', CURDATE() - INTERVAL 16 DAY + INTERVAL 20 HOUR, 'Bo1', 1, '18:46', NULL, 'Arum', 'Aoi', 'Yorn', 'Florentino', 'Alice', 0, 3, 1, 4, 1, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t2m1, 'Florentino', 1, 4, 14, 8.3, 12646, 36422, 33898, 78.9, 23.44, 20.34, 14.96),
  (@m, @t2m2, 'Zephys', 9, 5, 14, 7.4, 10968, 35378, 36168, 100.0, 20.33, 19.76, 15.96),
  (@m, @t2m3, 'Liliana', 2, 6, 3, 8.8, 7497, 24575, 57144, 26.3, 13.89, 13.72, 25.21),
  (@m, @t2m4, 'Elsu', 3, 1, 11, 9.3, 13183, 21027, 24190, 73.7, 24.43, 11.74, 10.67),
  (@m, @t2m5, 'Zip', 4, 5, 3, 13.1, 9662, 61656, 75251, 36.8, 17.91, 34.43, 33.2);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t2, 'Demo Tiger', 3, CURDATE() - INTERVAL 11 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t2, @s, 'Demo Tiger', 'WIN', CURDATE() - INTERVAL 11 DAY + INTERVAL 20 HOUR, 'Bo3', 1, '13:37', 'ไฟต์มังกรตัวแรกสื่อสารพลาด', 'Enzo', 'Butterfly', 'Raz', 'Ormarr', 'Krixi', 4, 0, 2, 8, 1, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t2m1, 'Qi', 7, 0, 8, 11.7, 11823, 73365, 17439, 50.0, 21.12, 19.25, 8.16),
  (@m, @t2m2, 'Murad', 3, 3, 9, 11.6, 6339, 87895, 68215, 40.0, 11.32, 23.06, 31.91),
  (@m, @t2m3, 'Tulen', 6, 2, 5, 9.8, 12428, 61215, 52512, 36.7, 22.2, 16.06, 24.56),
  (@m, @t2m4, 'Yorn', 7, 6, 2, 8.9, 13131, 77401, 32971, 30.0, 23.46, 20.31, 15.42),
  (@m, @t2m5, 'Arum', 7, 2, 2, 8.8, 12261, 81256, 42652, 30.0, 21.9, 21.32, 19.95);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t2, @s, 'Demo Tiger', 'WIN', CURDATE() - INTERVAL 11 DAY + INTERVAL 21 HOUR, 'Bo3', 2, '13:56', 'ไฟต์มังกรตัวแรกสื่อสารพลาด', 'Yena', 'Elsu', 'Florentino', 'Krixi', 'Zephys', 0, 3, 2, 7, 1, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t2m1, 'Allain', 1, 0, 4, 13.4, 11020, 49101, 35569, 16.7, 24.75, 20.72, 17.36),
  (@m, @t2m2, 'Murad', 6, 0, 6, 10.6, 6334, 85651, 58791, 40.0, 14.23, 36.14, 28.69),
  (@m, @t2m3, 'Liliana', 9, 5, 7, 12.9, 8418, 54096, 25504, 53.3, 18.91, 22.83, 12.45),
  (@m, @t2m4, 'Yorn', 7, 4, 11, 10.6, 8758, 28671, 21312, 60.0, 19.67, 12.1, 10.4),
  (@m, @t2m5, 'Arum', 7, 0, 12, 10.6, 9992, 19482, 63750, 63.3, 22.44, 8.22, 31.11);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t2, 'Talon Esports', 3, CURDATE() - INTERVAL 6 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t2, @s, 'Talon Esports', 'LOSS', CURDATE() - INTERVAL 6 DAY + INTERVAL 20 HOUR, 'Bo3', 1, '13:26', 'ไฟต์มังกรตัวแรกสื่อสารพลาด', 'Yorn', 'Lumburr', 'Tulen', 'Qi', 'Lauriel', 1, 2, 1, 4, 1, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t2m1, 'Florentino', 9, 3, 13, 12.5, 7855, 76802, 13863, 75.9, 17.95, 25.0, 5.65),
  (@m, @t2m2, 'Aoi', 3, 6, 4, 6.7, 6821, 37493, 56859, 24.1, 15.59, 12.2, 23.16),
  (@m, @t2m3, 'Raz', 1, 6, 8, 8.0, 10652, 50375, 34797, 31.0, 24.35, 16.4, 14.17),
  (@m, @t2m4, 'Violet', 8, 4, 2, 11.9, 7767, 71847, 64442, 34.5, 17.75, 23.39, 26.25),
  (@m, @t2m5, 'Grakk', 8, 3, 2, 7.5, 10655, 70707, 75557, 34.5, 24.35, 23.01, 30.77);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t2, @s, 'Talon Esports', 'WIN', CURDATE() - INTERVAL 6 DAY + INTERVAL 21 HOUR, 'Bo3', 2, '13:55', 'ไฟต์มังกรตัวแรกสื่อสารพลาด', 'Lumburr', 'Ormarr', 'Raz', 'Elsu', 'Qi', 0, 3, 1, 8, 1, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t2m1, 'Florentino', 7, 4, 9, 7.7, 9017, 33163, 55887, 59.3, 16.93, 12.37, 22.85),
  (@m, @t2m2, 'Nakroth', 3, 2, 11, 5.8, 13746, 88387, 52173, 51.9, 25.81, 32.97, 21.33),
  (@m, @t2m3, 'Raz', 8, 2, 2, 8.4, 9540, 24934, 60928, 37.0, 17.91, 9.3, 24.91),
  (@m, @t2m4, 'Yorn', 6, 3, 10, 10.7, 8486, 56852, 43284, 59.3, 15.93, 21.21, 17.7),
  (@m, @t2m5, 'Thane', 3, 2, 7, 11.4, 12474, 64745, 32339, 37.0, 23.42, 24.15, 13.22);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t2, @s, 'Talon Esports', 'WIN', CURDATE() - INTERVAL 6 DAY + INTERVAL 22 HOUR, 'Bo3', 3, '18:10', 'ไฟต์มังกรตัวแรกสื่อสารพลาด', 'Thane', 'Elsu', 'Zuka', 'Murad', 'Lauriel', 1, 3, 1, 9, 1, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t2m1, 'Zuka', 3, 6, 13, 5.5, 8193, 18094, 74132, 94.1, 16.39, 6.91, 28.74),
  (@m, @t2m2, 'Aoi', 1, 2, 5, 6.8, 13983, 49704, 68039, 35.3, 27.98, 18.97, 26.37),
  (@m, @t2m3, 'Lauriel', 9, 4, 7, 8.5, 10405, 42749, 33844, 94.1, 20.82, 16.32, 13.12),
  (@m, @t2m4, 'Yorn', 3, 3, 6, 6.2, 11276, 77518, 40901, 52.9, 22.56, 29.59, 15.85),
  (@m, @t2m5, 'Grakk', 1, 3, 7, 13.3, 6126, 73920, 41056, 47.1, 12.26, 28.22, 15.91);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t2, 'Full Sense', 1, CURDATE() - INTERVAL 1 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t2, @s, 'Full Sense', 'WIN', CURDATE() - INTERVAL 1 DAY + INTERVAL 20 HOUR, 'Bo1', 1, '24:53', 'ต้องวางวอร์ดก่อนนาทีที่ 2', 'Zip', 'Zuka', 'Elsu', 'Ormarr', 'Capheny', 2, 1, 1, 8, 0, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t2m1, 'Allain', 7, 6, 4, 7.1, 9703, 57728, 62961, 31.4, 21.64, 26.15, 29.94),
  (@m, @t2m2, 'Zephys', 9, 1, 2, 5.1, 6997, 41323, 27589, 31.4, 15.61, 18.72, 13.12),
  (@m, @t2m3, 'Iggy', 7, 4, 2, 7.9, 9961, 77423, 37521, 25.7, 22.22, 35.07, 17.84),
  (@m, @t2m4, 'Hayate', 5, 2, 6, 7.6, 9656, 22892, 48527, 31.4, 21.54, 10.37, 23.07),
  (@m, @t2m5, 'Thane', 7, 2, 8, 8.6, 8514, 21370, 33727, 42.9, 18.99, 9.68, 16.04);

-- ---------- ทีม Demo Tiger ----------
INSERT INTO users (username, email, password, display_name) VALUES ('demo_coach3', 'demo_coach3@example.com', '$2y$10$xNO3MhorHewxTNv2qdhCLu4RBBuW0UobBhQ93lVgQLyBpHhlZ5TPC', 'โค้ชไทเกอร์');
SET @u3 = LAST_INSERT_ID();
INSERT INTO teams (team_name, team_tag, leader_id, discord_link, line_id) VALUES ('Demo Tiger', 'TGR', @u3, 'https://discord.gg/tgrdemo', '@tgrdemo');
SET @t3 = LAST_INSERT_ID();
INSERT INTO team_members (team_id, user_id, in_game_name, main_role, current_rank) VALUES (@t3, @u3, 'Fang', 'Dark Slayer', 'Glorious Ruler');
SET @t3m1 = LAST_INSERT_ID();
INSERT INTO team_members (team_id, user_id, in_game_name, main_role, current_rank) VALUES (@t3, @u3, 'Stripe', 'Jungle', 'Conqueror');
SET @t3m2 = LAST_INSERT_ID();
INSERT INTO team_members (team_id, user_id, in_game_name, main_role, current_rank) VALUES (@t3, @u3, 'Claw', 'Mid Lane', 'Glorious Ruler');
SET @t3m3 = LAST_INSERT_ID();
INSERT INTO team_members (team_id, user_id, in_game_name, main_role, current_rank) VALUES (@t3, @u3, 'Roar', 'Dragon Lane', 'Conqueror');
SET @t3m4 = LAST_INSERT_ID();
INSERT INTO team_members (team_id, user_id, in_game_name, main_role, current_rank) VALUES (@t3, @u3, 'Prowl', 'Support', 'Glorious Ruler');
SET @t3m5 = LAST_INSERT_ID();
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t3, 'Talon Esports', 3, CURDATE() - INTERVAL 40 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t3, @s, 'Talon Esports', 'LOSS', CURDATE() - INTERVAL 40 DAY + INTERVAL 20 HOUR, 'Bo3', 1, '21:51', NULL, 'Butterfly', 'Krixi', 'Lumburr', 'Iggy', 'Tulen', 4, 1, 2, 5, 1, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t3m1, 'Allain', 5, 1, 5, 8.5, 6496, 71247, 54413, 38.5, 16.03, 26.54, 21.2),
  (@m, @t3m2, 'Aoi', 7, 5, 5, 6.8, 8595, 77329, 67606, 46.2, 21.2, 28.8, 26.34),
  (@m, @t3m3, 'Iggy', 8, 6, 3, 10.3, 8927, 26795, 56920, 42.3, 22.02, 9.98, 22.18),
  (@m, @t3m4, 'Elsu', 2, 6, 9, 6.7, 10259, 30042, 20011, 42.3, 25.31, 11.19, 7.8),
  (@m, @t3m5, 'Zip', 4, 2, 7, 7.5, 6257, 63049, 57700, 42.3, 15.44, 23.49, 22.48);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t3, @s, 'Talon Esports', 'WIN', CURDATE() - INTERVAL 40 DAY + INTERVAL 21 HOUR, 'Bo3', 2, '19:00', 'ดราฟต์ดีขึ้น คุมเลนกลางได้', 'Allain', 'Alice', 'Thane', 'Zip', 'Yena', 1, 2, 0, 9, 1, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t3m1, 'Yena', 3, 2, 7, 9.0, 10056, 27830, 27331, 76.9, 18.9, 9.54, 10.98),
  (@m, @t3m2, 'Enzo', 1, 1, 2, 8.4, 8713, 89045, 41962, 23.1, 16.38, 30.53, 16.86),
  (@m, @t3m3, 'Iggy', 2, 2, 5, 5.4, 13460, 58151, 54671, 53.8, 25.3, 19.94, 21.96),
  (@m, @t3m4, 'Violet', 4, 5, 14, 12.5, 13211, 85250, 73881, 100.0, 24.83, 29.23, 29.68),
  (@m, @t3m5, 'Zip', 3, 2, 12, 10.3, 7768, 31368, 51079, 100.0, 14.6, 10.76, 20.52);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t3, @s, 'Talon Esports', 'WIN', CURDATE() - INTERVAL 40 DAY + INTERVAL 22 HOUR, 'Bo3', 3, '19:34', 'ไฟต์มังกรตัวแรกสื่อสารพลาด', 'Krixi', 'Tulen', 'Arum', 'Alice', 'Butterfly', 0, 1, 2, 5, 1, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t3m1, 'Yena', 7, 2, 12, 8.4, 8115, 60688, 43080, 63.3, 16.22, 18.61, 21.1),
  (@m, @t3m2, 'Murad', 9, 4, 4, 10.4, 11003, 62423, 11582, 43.3, 22.0, 19.14, 5.67),
  (@m, @t3m3, 'Tulen', 3, 3, 11, 10.2, 11258, 67636, 64188, 46.7, 22.51, 20.74, 31.44),
  (@m, @t3m4, 'Capheny', 8, 0, 5, 9.4, 10975, 62989, 36024, 43.3, 21.94, 19.31, 17.65),
  (@m, @t3m5, 'Arum', 3, 6, 2, 8.3, 8672, 72393, 49284, 16.7, 17.34, 22.2, 24.14);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t3, 'Demo Kraken', 1, CURDATE() - INTERVAL 34 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t3, @s, 'Demo Kraken', 'WIN', CURDATE() - INTERVAL 34 DAY + INTERVAL 20 HOUR, 'Bo1', 1, '23:08', 'ต้องวางวอร์ดก่อนนาทีที่ 2', 'Arum', 'Capheny', 'Enzo', 'Ormarr', 'Violet', 0, 1, 1, 7, 0, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t3m1, 'Yena', 6, 3, 14, 10.8, 13338, 16026, 48115, 69.0, 22.16, 5.98, 24.1),
  (@m, @t3m2, 'Enzo', 2, 0, 5, 9.9, 11033, 61666, 46556, 24.1, 18.33, 23.0, 23.32),
  (@m, @t3m3, 'Iggy', 8, 1, 6, 6.2, 12042, 78676, 38942, 48.3, 20.0, 29.35, 19.51),
  (@m, @t3m4, 'Elsu', 6, 4, 8, 11.3, 11287, 33793, 53479, 48.3, 18.75, 12.6, 26.79),
  (@m, @t3m5, 'Alice', 7, 0, 2, 10.6, 12502, 77938, 12515, 31.0, 20.77, 29.07, 6.27);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t3, 'Full Sense', 1, CURDATE() - INTERVAL 27 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t3, @s, 'Full Sense', 'WIN', CURDATE() - INTERVAL 27 DAY + INTERVAL 20 HOUR, 'Bo1', 1, '17:11', 'ไฟต์มังกรตัวแรกสื่อสารพลาด', 'Elsu', 'Ormarr', 'Yena', 'Grakk', 'Florentino', 3, 0, 1, 9, 0, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t3m1, 'Qi', 6, 3, 11, 7.2, 13787, 82432, 52555, 70.8, 25.5, 25.74, 20.74),
  (@m, @t3m2, 'Nakroth', 2, 1, 11, 13.1, 8731, 33314, 70308, 54.2, 16.15, 10.4, 27.75),
  (@m, @t3m3, 'Raz', 7, 1, 8, 8.7, 11176, 87653, 37958, 62.5, 20.67, 27.37, 14.98),
  (@m, @t3m4, 'Elsu', 1, 1, 11, 12.8, 8033, 60933, 43725, 50.0, 14.85, 19.02, 17.26),
  (@m, @t3m5, 'Thane', 8, 1, 10, 7.1, 12350, 55965, 48821, 75.0, 22.84, 17.47, 19.27);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t3, 'Talon Esports', 1, CURDATE() - INTERVAL 20 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t3, @s, 'Talon Esports', 'WIN', CURDATE() - INTERVAL 20 DAY + INTERVAL 20 HOUR, 'Bo1', 1, '24:31', NULL, 'Elsu', 'Hayate', 'Arum', 'Lauriel', 'Yorn', 0, 2, 1, 3, 0, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t3m1, 'Florentino', 2, 2, 11, 7.6, 6014, 41201, 75413, 72.2, 15.06, 17.6, 22.43),
  (@m, @t3m2, 'Zephys', 4, 1, 9, 10.3, 7455, 70538, 70691, 72.2, 18.67, 30.13, 21.03),
  (@m, @t3m3, 'Lauriel', 8, 3, 7, 5.3, 7910, 47734, 55811, 83.3, 19.81, 20.39, 16.6),
  (@m, @t3m4, 'Hayate', 1, 3, 3, 5.9, 9194, 29325, 71548, 22.2, 23.02, 12.53, 21.28),
  (@m, @t3m5, 'Thane', 3, 4, 10, 5.0, 9365, 45301, 62692, 72.2, 23.45, 19.35, 18.65);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t3, 'Bacon Time', 3, CURDATE() - INTERVAL 14 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t3, @s, 'Bacon Time', 'WIN', CURDATE() - INTERVAL 14 DAY + INTERVAL 20 HOUR, 'Bo3', 1, '19:04', 'ดราฟต์ดีขึ้น คุมเลนกลางได้', 'Iggy', 'Murad', 'Zephys', 'Violet', 'Tulen', 2, 1, 1, 8, 0, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t3m1, 'Qi', 4, 3, 2, 10.1, 8988, 19243, 21563, 23.1, 20.1, 11.34, 8.93),
  (@m, @t3m2, 'Zephys', 2, 4, 5, 5.3, 6090, 19020, 61119, 26.9, 13.62, 11.21, 25.31),
  (@m, @t3m3, 'Tulen', 8, 3, 10, 8.0, 11586, 44924, 68800, 69.2, 25.91, 26.47, 28.49),
  (@m, @t3m4, 'Yorn', 9, 1, 2, 9.0, 11383, 64338, 35918, 42.3, 25.46, 37.92, 14.87),
  (@m, @t3m5, 'Arum', 3, 4, 2, 6.1, 6666, 22163, 54090, 19.2, 14.91, 13.06, 22.4);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t3, @s, 'Bacon Time', 'WIN', CURDATE() - INTERVAL 14 DAY + INTERVAL 21 HOUR, 'Bo3', 2, '24:04', 'ดราฟต์ดีขึ้น คุมเลนกลางได้', 'Murad', 'Zuka', 'Hayate', 'Zephys', 'Elsu', 4, 1, 1, 9, 1, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t3m1, 'Zuka', 8, 5, 3, 10.6, 8589, 37039, 30019, 39.3, 18.03, 15.28, 10.04),
  (@m, @t3m2, 'Murad', 2, 1, 7, 8.3, 8995, 31412, 72695, 32.1, 18.89, 12.96, 24.32),
  (@m, @t3m3, 'Tulen', 6, 2, 14, 11.2, 7292, 16230, 79791, 71.4, 15.31, 6.7, 26.69),
  (@m, @t3m4, 'Yorn', 4, 4, 6, 6.1, 10846, 85584, 60717, 35.7, 22.77, 35.31, 20.31),
  (@m, @t3m5, 'Grakk', 8, 2, 4, 11.0, 11904, 72128, 55678, 42.9, 24.99, 29.76, 18.63);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t3, @s, 'Bacon Time', 'WIN', CURDATE() - INTERVAL 14 DAY + INTERVAL 22 HOUR, 'Bo3', 3, '18:40', NULL, 'Elsu', 'Butterfly', 'Qi', 'Hayate', 'Laville', 4, 2, 0, 7, 0, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t3m1, 'Zuka', 9, 0, 10, 12.4, 7564, 86761, 12616, 86.4, 19.58, 31.77, 6.46),
  (@m, @t3m2, 'Enzo', 3, 2, 10, 7.6, 7265, 89900, 48515, 59.1, 18.8, 32.92, 24.83),
  (@m, @t3m3, 'Tulen', 5, 2, 13, 6.3, 6198, 27816, 59933, 81.8, 16.04, 10.18, 30.67),
  (@m, @t3m4, 'Elsu', 3, 6, 6, 8.8, 7376, 35140, 49119, 40.9, 19.09, 12.87, 25.14),
  (@m, @t3m5, 'Thane', 2, 4, 12, 12.5, 10234, 33496, 25226, 63.6, 26.49, 12.26, 12.91);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t3, 'eArena', 3, CURDATE() - INTERVAL 8 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t3, @s, 'eArena', 'WIN', CURDATE() - INTERVAL 8 DAY + INTERVAL 20 HOUR, 'Bo3', 1, '20:46', NULL, 'Lauriel', 'Laville', 'Tulen', 'Iggy', 'Aoi', 1, 1, 1, 7, 1, 0);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t3m1, 'Florentino', 4, 6, 14, 10.9, 9295, 31405, 66371, 62.1, 20.04, 19.39, 24.21),
  (@m, @t3m2, 'Zephys', 4, 0, 11, 9.4, 9199, 72543, 47270, 51.7, 19.84, 44.8, 17.24),
  (@m, @t3m3, 'Lauriel', 8, 0, 2, 9.6, 12238, 15727, 19748, 34.5, 26.39, 9.71, 7.2),
  (@m, @t3m4, 'Yorn', 8, 4, 14, 13.4, 6619, 22499, 74977, 75.9, 14.27, 13.89, 27.35),
  (@m, @t3m5, 'Zip', 5, 0, 13, 7.5, 9020, 19757, 65811, 62.1, 19.45, 12.2, 24.0);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t3, @s, 'eArena', 'LOSS', CURDATE() - INTERVAL 8 DAY + INTERVAL 21 HOUR, 'Bo3', 2, '13:17', 'ดราฟต์ดีขึ้น คุมเลนกลางได้', 'Zip', 'Enzo', 'Thane', 'Zuka', 'Raz', 0, 2, 0, 7, 1, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t3m1, 'Yena', 2, 3, 10, 8.1, 13376, 74943, 15777, 52.2, 24.32, 26.31, 6.73),
  (@m, @t3m2, 'Nakroth', 3, 4, 11, 8.0, 12926, 71061, 58025, 60.9, 23.5, 24.95, 24.77),
  (@m, @t3m3, 'Lauriel', 6, 3, 13, 5.9, 9099, 23587, 51068, 82.6, 16.54, 8.28, 21.8),
  (@m, @t3m4, 'Capheny', 5, 0, 12, 12.2, 11468, 76008, 41142, 73.9, 20.85, 26.68, 17.56),
  (@m, @t3m5, 'Alice', 7, 3, 7, 6.9, 8133, 39237, 68260, 60.9, 14.79, 13.78, 29.14);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t3, @s, 'eArena', 'WIN', CURDATE() - INTERVAL 8 DAY + INTERVAL 22 HOUR, 'Bo3', 3, '18:09', NULL, 'Aoi', 'Thane', 'Ormarr', 'Murad', 'Krixi', 4, 0, 1, 6, 0, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t3m1, 'Florentino', 4, 6, 11, 7.9, 6166, 18371, 74543, 100.0, 13.34, 7.2, 31.52),
  (@m, @t3m2, 'Murad', 2, 6, 8, 8.0, 11066, 37382, 45508, 83.3, 23.93, 14.64, 19.25),
  (@m, @t3m3, 'Lauriel', 2, 1, 4, 10.3, 12456, 68288, 22543, 50.0, 26.94, 26.75, 9.53),
  (@m, @t3m4, 'Elsu', 2, 1, 10, 5.5, 9621, 70878, 71790, 100.0, 20.81, 27.77, 30.36),
  (@m, @t3m5, 'Arum', 2, 4, 2, 10.1, 6929, 60347, 22073, 33.3, 14.99, 23.64, 9.33);
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (@t3, 'Bacon Time', 3, CURDATE() - INTERVAL 1 DAY + INTERVAL 20 HOUR);
SET @s = LAST_INSERT_ID();
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t3, @s, 'Bacon Time', 'LOSS', CURDATE() - INTERVAL 1 DAY + INTERVAL 20 HOUR, 'Bo3', 1, '12:50', NULL, 'Murad', 'Hayate', 'Qi', 'Laville', 'Krixi', 3, 2, 2, 8, 1, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t3m1, 'Qi', 1, 5, 14, 12.0, 7034, 41021, 38707, 71.4, 13.69, 13.81, 25.77),
  (@m, @t3m2, 'Enzo', 1, 4, 9, 12.2, 9941, 57550, 12983, 47.6, 19.35, 19.37, 8.64),
  (@m, @t3m3, 'Liliana', 9, 3, 11, 12.6, 11764, 61988, 43878, 95.2, 22.9, 20.86, 29.21),
  (@m, @t3m4, 'Elsu', 2, 2, 2, 7.7, 12199, 60579, 37476, 19.0, 23.75, 20.39, 24.95),
  (@m, @t3m5, 'Thane', 8, 5, 12, 11.2, 10428, 75963, 17177, 95.2, 20.3, 25.57, 11.43);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t3, @s, 'Bacon Time', 'WIN', CURDATE() - INTERVAL 1 DAY + INTERVAL 21 HOUR, 'Bo3', 2, '17:21', 'ไฟต์มังกรตัวแรกสื่อสารพลาด', 'Arum', 'Murad', 'Butterfly', 'Hayate', 'Liliana', 0, 1, 2, 8, 1, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t3m1, 'Florentino', 5, 3, 12, 12.0, 12483, 52126, 26508, 58.6, 27.34, 21.14, 15.73),
  (@m, @t3m2, 'Aoi', 7, 2, 2, 11.0, 7258, 73778, 30369, 31.0, 15.89, 29.92, 18.02),
  (@m, @t3m3, 'Liliana', 5, 0, 3, 10.1, 6893, 43086, 14776, 27.6, 15.09, 17.47, 8.77),
  (@m, @t3m4, 'Hayate', 5, 2, 6, 11.5, 8979, 56480, 59485, 37.9, 19.66, 22.9, 35.3),
  (@m, @t3m5, 'Arum', 7, 4, 2, 8.4, 10053, 21134, 37366, 31.0, 22.01, 8.57, 22.18);
INSERT INTO scrim_matches (team_id, series_id, opponent_name, match_result, match_date, match_type, game_number, match_duration, match_note, enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5, team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower) VALUES (@t3, @s, 'Bacon Time', 'LOSS', CURDATE() - INTERVAL 1 DAY + INTERVAL 22 HOUR, 'Bo3', 3, '20:29', NULL, 'Lumburr', 'Hayate', 'Liliana', 'Allain', 'Ormarr', 4, 1, 0, 7, 0, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO match_player_stats (match_id, member_id, hero_name, kills, deaths, assists, mvp_score, gold, damage_dealt, damage_taken, participation_rate, gold_share, damage_share, taken_share) VALUES
  (@m, @t3m1, 'Florentino', 9, 4, 3, 7.0, 7415, 82288, 13546, 40.0, 14.59, 29.36, 6.03),
  (@m, @t3m2, 'Enzo', 2, 1, 14, 9.4, 12848, 62004, 27731, 53.3, 25.29, 22.12, 12.35),
  (@m, @t3m3, 'Lauriel', 3, 5, 7, 9.5, 12938, 36920, 54087, 33.3, 25.46, 13.17, 24.1),
  (@m, @t3m4, 'Violet', 8, 6, 2, 7.8, 9399, 73453, 68505, 33.3, 18.5, 26.21, 30.52),
  (@m, @t3m5, 'Alice', 8, 2, 4, 7.6, 8210, 25630, 60603, 40.0, 16.16, 9.14, 27.0);

-- ---------- ประกาศหาทีมซ้อม + คำขอซ้อม ----------
-- 1) Phoenix เปิดประกาศ และ Kraken ส่งคำขอรออยู่
INSERT INTO scrim_posts (team_id, avg_rank, match_format, scrim_time, note, status) VALUES (@t1, 'Conqueror', 'BO3', NOW() + INTERVAL 2 DAY, 'หาทีมซ้อมดราฟต์จริงจัง', 'open');
SET @p1 = LAST_INSERT_ID();
INSERT INTO scrim_requests (post_id, team_id, message) VALUES (@p1, @t2, 'ว่างช่วงสามทุ่มครับ');
-- 2) Tiger ประกาศแล้วรับคำขอของ Phoenix: มีซีรีส์รอบันทึกผลให้ทั้งสองทีม
INSERT INTO scrim_posts (team_id, avg_rank, match_format, scrim_time, status) VALUES (@t3, 'Supreme Conqueror', 'BO5', NOW() + INTERVAL 3 DAY, 'matched');
SET @p2 = LAST_INSERT_ID();
INSERT INTO scrim_requests (post_id, team_id, status, responded_at) VALUES (@p2, @t1, 'accepted', NOW());
SET @r2 = LAST_INSERT_ID();
INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date, request_id) VALUES (@t3, 'Demo Phoenix', 5, NOW() + INTERVAL 3 DAY, @r2), (@t1, 'Demo Tiger', 5, NOW() + INTERVAL 3 DAY, @r2);
-- 3) Kraken ประกาศ BO2 (ยังเปิดรับ)
INSERT INTO scrim_posts (team_id, avg_rank, match_format, scrim_time, status) VALUES (@t2, 'Glorious Ruler', 'BO2', NOW() + INTERVAL 5 DAY, 'open');

COMMIT;
