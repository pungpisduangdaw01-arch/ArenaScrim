-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 10, 2026 at 05:33 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `arenascrim_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `heroes`
--

CREATE TABLE `heroes` (
  `hero_id` int(11) NOT NULL,
  `hero_name` varchar(50) NOT NULL,
  `hero_role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `heroes`
--

INSERT INTO `heroes` (`hero_id`, `hero_name`, `hero_role`) VALUES
(1, 'Allain', 'Dark Slayer'),
(2, 'Bijan', 'Dark Slayer'),
(3, 'Charlotte', 'Dark Slayer'),
(4, 'Dextra', 'Dark Slayer'),
(5, 'Errol', 'Dark Slayer'),
(6, 'Florentino', 'Dark Slayer'),
(7, 'Lu Bu', 'Dark Slayer'),
(8, 'Maloch', 'Dark Slayer'),
(9, 'Omen', 'Dark Slayer'),
(10, 'Qi', 'Dark Slayer'),
(11, 'Riktor', 'Dark Slayer'),
(12, 'Roxie', 'Dark Slayer'),
(13, 'Skud', 'Dark Slayer'),
(14, 'Tachi', 'Dark Slayer'),
(15, 'Yena', 'Dark Slayer'),
(16, 'Zuka', 'Dark Slayer'),
(17, 'Aoi', 'Jungle'),
(18, 'Biron', 'Jungle'),
(19, 'Butterfly', 'Jungle'),
(20, 'Enzo', 'Jungle'),
(21, 'Keera', 'Jungle'),
(22, 'Kriknak', 'Jungle'),
(23, 'Murad', 'Jungle'),
(24, 'Nakroth', 'Jungle'),
(25, 'Pain', 'Jungle'),
(26, 'Quillen', 'Jungle'),
(27, 'Sinestrea', 'Jungle'),
(28, 'Wukong', 'Jungle'),
(29, 'Yan', 'Jungle'),
(30, 'Zill', 'Jungle'),
(31, 'Zephys', 'Jungle'),
(32, 'Aleister', 'Mid Lane'),
(33, 'Azzen\'Ka', 'Mid Lane'),
(34, 'Diao Chan', 'Mid Lane'),
(35, 'Dirak', 'Mid Lane'),
(36, 'Iggy', 'Mid Lane'),
(37, 'Ilumia', 'Mid Lane'),
(38, 'Jinna', 'Mid Lane'),
(39, 'Kahlii', 'Mid Lane'),
(40, 'Krixi', 'Mid Lane'),
(41, 'Lauriel', 'Mid Lane'),
(42, 'Liliana', 'Mid Lane'),
(43, 'Lorion', 'Mid Lane'),
(44, 'Manga', 'Mid Lane'),
(45, 'Marja', 'Mid Lane'),
(46, 'Natalya', 'Mid Lane'),
(47, 'Preyta', 'Mid Lane'),
(48, 'Raz', 'Mid Lane'),
(49, 'Sephera', 'Mid Lane'),
(50, 'Tulen', 'Mid Lane'),
(51, 'Veera', 'Mid Lane'),
(52, 'Yue', 'Mid Lane'),
(53, 'Capheny', 'Dragon Lane'),
(54, 'Celica', 'Dragon Lane'),
(55, 'Eland\'orr', 'Dragon Lane'),
(56, 'Elsu', 'Dragon Lane'),
(57, 'Hayate', 'Dragon Lane'),
(58, 'Joker', 'Dragon Lane'),
(59, 'Klass', 'Dragon Lane'),
(60, 'Laville', 'Dragon Lane'),
(61, 'Moren', 'Dragon Lane'),
(62, 'Slimz', 'Dragon Lane'),
(63, 'Stacia', 'Dragon Lane'),
(64, 'Teeri', 'Dragon Lane'),
(65, 'Tel\'Annas', 'Dragon Lane'),
(66, 'Thorne', 'Dragon Lane'),
(67, 'Valhein', 'Dragon Lane'),
(68, 'Violet', 'Dragon Lane'),
(69, 'Wisp', 'Dragon Lane'),
(70, 'Yorn', 'Dragon Lane'),
(71, 'Alice', 'Support / Tank'),
(72, 'Annette', 'Support / Tank'),
(73, 'Arum', 'Support / Tank'),
(74, 'Aya', 'Support / Tank'),
(75, 'Baldum', 'Support / Tank'),
(76, 'Chaugnar', 'Support / Tank'),
(77, 'Cresht', 'Support / Tank'),
(78, 'Gildur', 'Support / Tank'),
(79, 'Grakk', 'Support / Tank'),
(80, 'Helen', 'Support / Tank'),
(81, 'Krouros', 'Support / Tank'),
(82, 'Lumburr', 'Support / Tank'),
(83, 'Mina', 'Support / Tank'),
(84, 'Ming', 'Support / Tank'),
(85, 'Ormarr', 'Support / Tank'),
(86, 'TeeMee', 'Support / Tank'),
(87, 'Thane', 'Support / Tank'),
(88, 'Toro', 'Support / Tank'),
(89, 'Wonder Woman', 'Support / Tank'),
(90, 'Xeniel', 'Support / Tank'),
(91, 'Y\'bneth', 'Support / Tank'),
(92, 'Zip', 'Support / Tank');

-- --------------------------------------------------------

--
-- Table structure for table `match_player_stats`
--

CREATE TABLE `match_player_stats` (
  `stat_id` int(11) NOT NULL,
  `match_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `hero` varchar(50) DEFAULT NULL,
  `hero_name` varchar(50) NOT NULL,
  `kills` int(11) DEFAULT 0,
  `deaths` int(11) DEFAULT 0,
  `assists` int(11) DEFAULT 0,
  `mvp_score` decimal(3,1) DEFAULT 0.0,
  `gold` int(11) DEFAULT 0,
  `damage_dealt` int(11) DEFAULT 0,
  `damage_taken` int(11) DEFAULT 0,
  `participation_rate` decimal(4,1) DEFAULT 0.0,
  `gold_share` decimal(5,2) DEFAULT 0.00 COMMENT '% เงินของทีม',
  `damage_share` decimal(5,2) DEFAULT 0.00 COMMENT '% ดาเมจที่ทำ',
  `taken_share` decimal(5,2) DEFAULT 0.00 COMMENT '% ดาเมจที่รับ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `match_player_stats`
--

INSERT INTO `match_player_stats` (`stat_id`, `match_id`, `member_id`, `hero`, `hero_name`, `kills`, `deaths`, `assists`, `mvp_score`, `gold`, `damage_dealt`, `damage_taken`, `participation_rate`, `gold_share`, `damage_share`, `taken_share`) VALUES
(99, 26, 55, NULL, 'Tachi', 2, 4, 3, 6.1, 8670, 45951, 117912, 50.0, 18.90, 12.30, 26.90),
(100, 26, 56, NULL, 'Yan', 2, 7, 6, 5.8, 10380, 95496, 55014, 80.0, 22.70, 25.50, 12.50),
(101, 26, 57, NULL, 'Krixi', 3, 6, 6, 4.9, 9645, 110917, 73302, 90.0, 21.10, 29.60, 16.70),
(102, 26, 58, NULL, 'Stacia', 3, 7, 2, 4.5, 10260, 95418, 80028, 50.0, 22.40, 25.40, 18.20),
(103, 26, 59, NULL, 'Wonder Woman', 0, 5, 1, 4.5, 6825, 27300, 112612, 10.0, 14.90, 7.30, 25.70),
(104, 27, 55, NULL, 'Lu Bu', 0, 4, 6, 5.3, 9673, 47397, 147029, 100.0, 18.70, 11.80, 28.10),
(105, 27, 56, NULL, 'Biron', 0, 4, 4, 4.6, 11713, 113616, 85504, 100.0, 22.60, 28.20, 16.30),
(106, 27, 57, NULL, 'Manga', 1, 5, 6, 4.4, 10897, 98073, 76279, 100.0, 21.10, 24.40, 14.60),
(107, 27, 58, NULL, 'Joker', 2, 7, 1, 4.4, 11934, 107406, 107406, 100.0, 23.10, 26.70, 20.50),
(108, 27, 59, NULL, 'Toro', 0, 7, 6, 5.6, 7531, 36148, 107693, 100.0, 14.60, 9.00, 20.60),
(109, 28, 55, NULL, 'Tachi', 0, 4, 10, 7.7, 10400, 72800, 141440, 40.0, 17.20, 13.00, 27.60),
(110, 28, 56, NULL, 'Butterfly', 7, 0, 11, 16.0, 14420, 155736, 77868, 72.0, 23.80, 27.70, 15.20),
(111, 28, 57, NULL, 'Krixi', 7, 2, 6, 10.2, 12680, 150892, 103976, 52.0, 20.90, 26.80, 20.30),
(112, 28, 58, NULL, 'Joker', 10, 1, 9, 16.0, 14080, 132352, 71808, 76.0, 23.20, 23.50, 14.00),
(113, 28, 59, NULL, 'Wonder Woman', 1, 2, 5, 8.3, 8980, 50288, 116740, 24.0, 14.80, 8.90, 22.80),
(114, 29, 55, NULL, 'Skud', 1, 3, 6, 5.8, 7215, 38961, 121933, 100.0, 17.70, 10.80, 29.20),
(115, 29, 56, NULL, 'Butterfly', 1, 4, 2, 5.2, 9230, 94146, 79378, 100.0, 22.60, 26.20, 19.00),
(116, 29, 57, NULL, 'Manga', 0, 4, 2, 3.8, 8736, 99590, 63772, 66.7, 21.40, 27.70, 15.30),
(117, 29, 58, NULL, 'Yorn', 1, 6, 1, 4.1, 9802, 101940, 66653, 66.7, 24.00, 28.30, 16.00),
(118, 29, 59, NULL, 'Lumburr', 0, 7, 4, 5.0, 5811, 24987, 85421, 100.0, 14.20, 6.90, 20.50),
(119, 30, 55, NULL, 'Lu Bu', 2, 4, 12, 8.5, 11514, 77143, 145076, 60.9, 19.80, 14.60, 28.00),
(120, 30, 56, NULL, 'Yan', 4, 3, 6, 9.5, 13262, 153839, 68962, 43.5, 22.80, 29.10, 13.30),
(121, 30, 57, NULL, 'Krixi', 9, 0, 12, 16.0, 13851, 138510, 120503, 91.3, 23.80, 26.20, 23.30),
(122, 30, 58, NULL, 'Stacia', 8, 3, 8, 10.0, 12255, 122550, 66177, 69.6, 21.10, 23.10, 12.80),
(123, 30, 59, NULL, 'Wonder Woman', 0, 2, 9, 9.0, 7334, 37403, 117344, 39.1, 12.60, 7.10, 22.70),
(124, 31, 55, NULL, 'Skud', 1, 3, 5, 6.2, 7579, 42442, 115200, 66.7, 19.10, 11.80, 32.50),
(125, 31, 56, NULL, 'Butterfly', 3, 6, 5, 5.0, 9022, 82100, 62251, 88.9, 22.80, 22.80, 17.50),
(126, 31, 57, NULL, 'Manga', 1, 3, 3, 5.6, 8827, 114751, 45017, 44.4, 22.30, 31.90, 12.70),
(127, 31, 58, NULL, 'Yorn', 4, 4, 4, 5.2, 8619, 88775, 43095, 88.9, 21.80, 24.70, 12.10),
(128, 31, 59, NULL, 'Lumburr', 0, 2, 5, 5.6, 5551, 31640, 89371, 55.6, 14.00, 8.80, 25.20),
(129, 32, 55, NULL, 'Lu Bu', 2, 3, 2, 4.7, 13354, 64099, 170931, 25.0, 19.70, 10.70, 27.10),
(130, 32, 56, NULL, 'Butterfly', 5, 3, 1, 5.1, 15422, 175810, 106411, 37.5, 22.70, 29.50, 16.90),
(131, 32, 57, NULL, 'Manga', 5, 4, 6, 5.9, 14828, 142348, 112692, 68.8, 21.80, 23.80, 17.90),
(132, 32, 58, NULL, 'Joker', 3, 7, 1, 4.9, 14828, 169039, 124555, 25.0, 21.80, 28.30, 19.80),
(133, 32, 59, NULL, 'Toro', 1, 7, 2, 5.0, 9504, 45619, 115948, 18.8, 14.00, 7.60, 18.40),
(134, 33, 55, NULL, 'Lu Bu', 1, 0, 3, 9.2, 9864, 59184, 126259, 15.4, 17.00, 10.30, 26.50),
(135, 33, 56, NULL, 'Biron', 8, 1, 10, 16.0, 13518, 170326, 82459, 69.2, 23.30, 29.60, 17.30),
(136, 33, 57, NULL, 'Krixi', 5, 3, 7, 9.8, 13554, 170780, 81324, 46.2, 23.40, 29.70, 17.10),
(137, 33, 58, NULL, 'Joker', 10, 0, 11, 16.0, 12690, 119286, 74871, 80.8, 21.90, 20.70, 15.70),
(138, 33, 59, NULL, 'Lumburr', 2, 0, 8, 13.2, 8280, 56304, 111780, 38.5, 14.30, 9.80, 23.40),
(139, 34, 55, NULL, 'Skud', 2, 3, 11, 9.1, 11720, 82040, 181660, 81.3, 19.50, 13.40, 32.60),
(140, 34, 56, NULL, 'Butterfly', 3, 3, 5, 8.4, 13400, 168840, 84420, 50.0, 22.30, 27.50, 15.20),
(141, 34, 57, NULL, 'Krixi', 7, 1, 6, 15.2, 13040, 163000, 66504, 81.3, 21.70, 26.50, 11.90),
(142, 34, 58, NULL, 'Stacia', 3, 3, 10, 10.6, 13120, 140384, 74784, 81.3, 21.80, 22.90, 13.40),
(143, 34, 59, NULL, 'Lumburr', 1, 3, 11, 8.8, 8800, 59840, 149600, 75.0, 14.60, 9.70, 26.90),
(144, 35, 55, NULL, 'Tachi', 2, 7, 3, 5.1, 8672, 53766, 126611, 83.3, 17.90, 13.60, 27.10),
(145, 35, 56, NULL, 'Butterfly', 0, 7, 6, 5.3, 10240, 92160, 80896, 100.0, 21.10, 23.30, 17.30),
(146, 35, 57, NULL, 'Manga', 2, 5, 4, 4.8, 11888, 111747, 64195, 100.0, 24.50, 28.30, 13.70),
(147, 35, 58, NULL, 'Stacia', 0, 5, 6, 5.2, 10400, 102960, 79040, 100.0, 21.50, 26.10, 16.90),
(148, 35, 59, NULL, 'Toro', 2, 4, 2, 5.7, 7280, 34216, 116480, 66.7, 15.00, 8.70, 24.90),
(149, 36, 55, NULL, 'Lu Bu', 0, 5, 1, 3.9, 11200, 49280, 162400, 10.0, 17.90, 8.70, 26.90),
(150, 36, 56, NULL, 'Yan', 2, 4, 6, 6.2, 13420, 174460, 100650, 80.0, 21.50, 30.70, 16.70),
(151, 36, 57, NULL, 'Jinna', 5, 3, 6, 6.2, 14080, 133760, 73216, 100.0, 22.50, 23.60, 12.10),
(152, 36, 58, NULL, 'Yorn', 1, 6, 2, 4.4, 15000, 162000, 126000, 30.0, 24.00, 28.50, 20.90),
(153, 36, 59, NULL, 'Wonder Woman', 2, 7, 6, 4.5, 8800, 48400, 141680, 80.0, 14.10, 8.50, 23.50),
(154, 37, 55, NULL, 'Tachi', 2, 5, 5, 5.9, 5753, 37969, 89746, 70.0, 17.70, 12.20, 27.10),
(155, 37, 56, NULL, 'Yan', 0, 2, 5, 6.3, 7436, 81796, 41641, 50.0, 22.80, 26.40, 12.60),
(156, 37, 57, NULL, 'Krixi', 5, 2, 4, 7.5, 7084, 90675, 60922, 90.0, 21.70, 29.20, 18.40),
(157, 37, 58, NULL, 'Stacia', 1, 7, 2, 4.6, 7623, 73943, 63270, 30.0, 23.40, 23.80, 19.10),
(158, 37, 59, NULL, 'Toro', 2, 5, 6, 4.9, 4675, 25712, 75267, 80.0, 14.40, 8.30, 22.70);

-- --------------------------------------------------------

--
-- Table structure for table `scrim_matches`
--

CREATE TABLE `scrim_matches` (
  `match_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `opponent_name` varchar(100) NOT NULL,
  `match_result` enum('WIN','LOSS') NOT NULL,
  `match_date` datetime DEFAULT current_timestamp(),
  `match_type` varchar(10) DEFAULT 'Bo3',
  `game_number` int(11) DEFAULT 1,
  `match_duration` varchar(10) DEFAULT NULL,
  `replay_url` varchar(255) DEFAULT NULL,
  `match_note` text DEFAULT NULL,
  `enemy_hero_1` varchar(50) DEFAULT NULL,
  `enemy_hero_2` varchar(50) DEFAULT NULL,
  `enemy_hero_3` varchar(50) DEFAULT NULL,
  `enemy_hero_4` varchar(50) DEFAULT NULL,
  `enemy_hero_5` varchar(50) DEFAULT NULL,
  `match_image_url` varchar(255) DEFAULT NULL,
  `team_dragons` int(11) DEFAULT 0,
  `team_slayers` int(11) DEFAULT 0,
  `team_towers` int(11) DEFAULT 0,
  `first_dragon` tinyint(1) DEFAULT 0,
  `first_tower` tinyint(1) DEFAULT 0,
  `team_god_slayers` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scrim_matches`
--

INSERT INTO `scrim_matches` (`match_id`, `team_id`, `opponent_name`, `match_result`, `match_date`, `match_type`, `game_number`, `match_duration`, `replay_url`, `match_note`, `enemy_hero_1`, `enemy_hero_2`, `enemy_hero_3`, `enemy_hero_4`, `enemy_hero_5`, `match_image_url`, `team_dragons`, `team_slayers`, `team_towers`, `first_dragon`, `first_tower`, `team_god_slayers`) VALUES
(26, 18, 'Bacon Time', 'LOSS', '2026-09-10 21:00:00', 'Bo1', 1, '15:13', NULL, 'ดราฟต์ดี ป่าคุมจังหวะได้ตั้งแต่ต้น เก็บมังกรตัวแรกได้ไว', 'Pain', 'Zephys', 'Aleister', 'Dirak', 'Krixi', NULL, 0, 0, 3, 1, 0, 0),
(27, 18, 'Buriram United Esports', 'LOSS', '2026-09-12 20:00:00', 'Bo3', 1, '17:36', NULL, 'ออฟเลนโดนเติม 3 คนบ่อยช่วงต้นเกม ซัพต้องโรมมาช่วยเร็วกว่านี้', 'Azzen\'Ka', 'Veera', 'Krouros', 'Ormarr', 'Thane', NULL, 0, 1, 3, 0, 0, 0),
(28, 18, 'Talon Esports', 'WIN', '2026-09-14 23:00:00', 'Bo3', 1, '20:12', NULL, 'ไฟต์ Dark Slayer ตัวแรกสื่อสารพลาด เข้าไม่พร้อมกัน', 'Yan', 'Diao Chan', 'Eland\'orr', 'Joker', 'TeeMee', NULL, 3, 3, 7, 0, 1, 1),
(29, 18, 'King of Gamers', 'LOSS', '2026-09-16 21:00:00', 'Bo1', 1, '13:25', NULL, 'แครี่ฟาร์มดีมาก ไลน์อัพสายเลทเกมเห็นผล', 'Murad', 'Lauriel', 'Manga', 'Tulen', 'TeeMee', NULL, 1, 0, 1, 0, 0, 0),
(30, 18, 'eArena', 'WIN', '2026-09-18 21:00:00', 'Bo3', 1, '19:37', NULL, 'โดนปิดเกมเร็ว วอร์ดป่าฝั่งเราไม่พอ ต้องซ้อมวิชั่นเพิ่ม', 'Aoi', 'Aleister', 'Krixi', 'Elsu', 'Aya', NULL, 4, 3, 8, 1, 1, 1),
(31, 18, 'Valencia CF Esports', 'LOSS', '2026-09-20 23:00:00', 'Bo3', 2, '13:04', NULL, 'ชนะไฟต์ทีมช่วงท้ายเกมได้ เพราะมิดเก็บ 3 คนได้ในจังหวะเดียว', 'Charlotte', 'Zephys', 'Marja', 'Sephera', 'Yue', NULL, 0, 0, 3, 0, 0, 0),
(32, 18, 'Full Sense', 'LOSS', '2026-09-22 20:00:00', 'Bo1', 1, '22:29', NULL, NULL, 'Dirak', 'Veera', 'Stacia', 'Annette', 'TeeMee', NULL, 0, 1, 1, 0, 0, 0),
(33, 18, 'Bacon Time', 'WIN', '2026-09-24 20:00:00', 'Bo3', 2, '18:10', NULL, 'ดราฟต์ดี ป่าคุมจังหวะได้ตั้งแต่ต้น เก็บมังกรตัวแรกได้ไว', 'Raz', 'Elsu', 'Moren', 'Arum', 'Wonder Woman', NULL, 2, 3, 5, 0, 1, 1),
(34, 18, 'Talon Esports', 'WIN', '2026-09-26 21:00:00', 'Bo3', 2, '20:03', NULL, 'ออฟเลนโดนเติม 3 คนบ่อยช่วงต้นเกม ซัพต้องโรมมาช่วยเร็วกว่านี้', 'Riktor', 'Skud', 'Laville', 'Teeri', 'Gildur', NULL, 2, 2, 9, 1, 0, 1),
(35, 18, 'King of Gamers', 'LOSS', '2026-09-28 23:00:00', 'Bo1', 1, '16:48', NULL, 'ไฟต์ Dark Slayer ตัวแรกสื่อสารพลาด เข้าไม่พร้อมกัน', 'Maloch', 'Skud', 'Tachi', 'Keera', 'Violet', NULL, 1, 1, 4, 0, 0, 0),
(36, 18, 'eArena', 'LOSS', '2026-09-30 21:00:00', 'Bo3', 3, '20:32', NULL, 'แครี่ฟาร์มดีมาก ไลน์อัพสายเลทเกมเห็นผล', 'Charlotte', 'Wukong', 'Eland\'orr', 'Mina', 'Xeniel', NULL, 1, 1, 4, 1, 0, 0),
(37, 18, 'Full Sense', 'LOSS', '2026-10-02 22:00:00', 'Bo3', 3, '11:09', NULL, 'โดนปิดเกมเร็ว วอร์ดป่าฝั่งเราไม่พอ ต้องซ้อมวิชั่นเพิ่ม', 'Charlotte', 'Quillen', 'Klass', 'Yorn', 'Grakk', NULL, 1, 1, 2, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `scrim_posts`
--

CREATE TABLE `scrim_posts` (
  `post_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `avg_rank` varchar(50) NOT NULL,
  `match_format` enum('BO1','BO3','BO5') DEFAULT 'BO3',
  `scrim_time` datetime NOT NULL,
  `note` text DEFAULT NULL,
  `status` enum('open','matched','expired') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `team_id` int(11) NOT NULL,
  `team_name` varchar(100) NOT NULL,
  `team_tag` varchar(10) NOT NULL,
  `leader_id` int(11) NOT NULL,
  `discord_link` varchar(255) DEFAULT NULL,
  `facebook_link` varchar(255) DEFAULT NULL,
  `line_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`team_id`, `team_name`, `team_tag`, `leader_id`, `discord_link`, `facebook_link`, `line_id`, `created_at`) VALUES
(18, 'zboy', 'ZB', 2, NULL, NULL, NULL, '2026-09-05 12:06:08');

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `member_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `in_game_name` varchar(100) NOT NULL,
  `main_role` varchar(50) DEFAULT NULL,
  `nickname` varchar(100) DEFAULT NULL COMMENT 'ชื่อเล่น',
  `fullname` varchar(255) DEFAULT NULL COMMENT 'ชื่อ-นามสกุลจริง',
  `open_id` varchar(100) DEFAULT NULL COMMENT 'OpenID ในเกม',
  `full_id` varchar(100) DEFAULT NULL COMMENT 'Full ID ตัวละคร',
  `current_rank` varchar(50) DEFAULT NULL COMMENT 'แรงก์ปัจจุบัน',
  `contact` varchar(255) DEFAULT NULL COMMENT 'ช่องทางติดต่อ เช่น Line / Discord',
  `member_note` text DEFAULT NULL COMMENT 'หมายเหตุ ฮีโร่ถนัด ฯลฯ',
  `info_updated_at` datetime DEFAULT NULL COMMENT 'แก้ไขข้อมูลส่วนตัวล่าสุด',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `position` varchar(50) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`member_id`, `team_id`, `user_id`, `in_game_name`, `main_role`, `nickname`, `fullname`, `open_id`, `full_id`, `current_rank`, `contact`, `member_note`, `info_updated_at`, `joined_at`, `position`) VALUES
(55, 18, 2, 'yu', 'Dark Slayer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-04 07:36:53', ''),
(56, 18, 2, 'pat', 'Jungle', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-04 07:36:58', ''),
(57, 18, 2, 'K', 'Mid Lane', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-04 07:37:10', ''),
(58, 18, 2, 'AA', 'Dragon Lane', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-04 07:37:16', ''),
(59, 18, 2, 'boy', 'Support', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-04 07:37:22', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `nickname` varchar(100) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `open_id` varchar(255) DEFAULT NULL,
  `full_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `display_name`, `role`, `created_at`, `nickname`, `fullname`, `open_id`, `full_id`) VALUES
(2, 'P1', 'P1@gmail.com', '$2y$10$qSdTDrYFz5oFLokUM9n4teuBTxbkyTbkCACI4oup.X6Yqb4TGc6gu', 'P1', 'user', '2026-08-28 18:45:50', NULL, NULL, NULL, NULL),
(3, 'P2', 'P2@gmail.com', '$2y$10$rNuTVE.HbpXwRQOyuD.cAuRwrPvLakbzLn.j4U0HXMj9yxa5mF1GC', 'P2', 'user', '2026-08-28 19:04:42', NULL, NULL, NULL, NULL),
(13, 'PungzZz', 'PungzZz@gmail.com', '$2y$10$pToZ6Q50JzBuH4I4TvLbsee3aT8xI6c9rmiZV3kYy8m3QheTXWtyK', 'PungzZz', 'admin', '2026-10-04 09:01:22', NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `heroes`
--
ALTER TABLE `heroes`
  ADD PRIMARY KEY (`hero_id`),
  ADD UNIQUE KEY `hero_name` (`hero_name`);

--
-- Indexes for table `match_player_stats`
--
ALTER TABLE `match_player_stats`
  ADD PRIMARY KEY (`stat_id`),
  ADD KEY `match_id` (`match_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `scrim_matches`
--
ALTER TABLE `scrim_matches`
  ADD PRIMARY KEY (`match_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `scrim_posts`
--
ALTER TABLE `scrim_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`team_id`),
  ADD UNIQUE KEY `team_name` (`team_name`),
  ADD KEY `leader_id` (`leader_id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`member_id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `heroes`
--
ALTER TABLE `heroes`
  MODIFY `hero_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `match_player_stats`
--
ALTER TABLE `match_player_stats`
  MODIFY `stat_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=159;

--
-- AUTO_INCREMENT for table `scrim_matches`
--
ALTER TABLE `scrim_matches`
  MODIFY `match_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `scrim_posts`
--
ALTER TABLE `scrim_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `team_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `match_player_stats`
--
ALTER TABLE `match_player_stats`
  ADD CONSTRAINT `match_player_stats_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `scrim_matches` (`match_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `match_player_stats_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `team_members` (`member_id`) ON DELETE CASCADE;

--
-- Constraints for table `scrim_matches`
--
ALTER TABLE `scrim_matches`
  ADD CONSTRAINT `scrim_matches_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE;

--
-- Constraints for table `scrim_posts`
--
ALTER TABLE `scrim_posts`
  ADD CONSTRAINT `scrim_posts_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE;

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`leader_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
