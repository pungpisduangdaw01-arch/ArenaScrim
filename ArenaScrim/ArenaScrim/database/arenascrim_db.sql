-- ArenaScrim database schema + hero list
CREATE DATABASE IF NOT EXISTS arenascrim_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE arenascrim_db;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `heroes` (
  `hero_id` int(11) NOT NULL AUTO_INCREMENT,
  `hero_name` varchar(50) NOT NULL,
  `hero_role` varchar(50) NOT NULL,
  PRIMARY KEY (`hero_id`),
  UNIQUE KEY `hero_name` (`hero_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `match_player_stats` (
  `stat_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `taken_share` decimal(5,2) DEFAULT 0.00 COMMENT '% ดาเมจที่รับ',
  PRIMARY KEY (`stat_id`),
  KEY `match_id` (`match_id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `match_player_stats_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `scrim_matches` (`match_id`) ON DELETE CASCADE,
  CONSTRAINT `match_player_stats_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `team_members` (`member_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scrim_matches` (
  `match_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `team_god_slayers` int(11) DEFAULT 0,
  PRIMARY KEY (`match_id`),
  KEY `team_id` (`team_id`),
  CONSTRAINT `scrim_matches_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scrim_posts` (
  `post_id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `avg_rank` varchar(50) NOT NULL,
  `match_format` enum('BO1','BO3','BO5') DEFAULT 'BO3',
  `scrim_time` datetime NOT NULL,
  `note` text DEFAULT NULL,
  `status` enum('open','matched','expired') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`post_id`),
  KEY `team_id` (`team_id`),
  CONSTRAINT `scrim_posts_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `team_members` (
  `member_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `position` varchar(50) DEFAULT '',
  PRIMARY KEY (`member_id`),
  KEY `team_id` (`team_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE,
  CONSTRAINT `team_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teams` (
  `team_id` int(11) NOT NULL AUTO_INCREMENT,
  `team_name` varchar(100) NOT NULL,
  `team_tag` varchar(10) NOT NULL,
  `leader_id` int(11) NOT NULL,
  `discord_link` varchar(255) DEFAULT NULL,
  `facebook_link` varchar(255) DEFAULT NULL,
  `line_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`team_id`),
  UNIQUE KEY `team_name` (`team_name`),
  KEY `leader_id` (`leader_id`),
  CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`leader_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `nickname` varchar(100) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `open_id` varchar(255) DEFAULT NULL,
  `full_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

LOCK TABLES `heroes` WRITE;
/*!40000 ALTER TABLE `heroes` DISABLE KEYS */;
INSERT INTO `heroes` VALUES (1,'Allain','Dark Slayer'),(2,'Bijan','Dark Slayer'),(3,'Charlotte','Dark Slayer'),(4,'Dextra','Dark Slayer'),(5,'Errol','Dark Slayer'),(6,'Florentino','Dark Slayer'),(7,'Lu Bu','Dark Slayer'),(8,'Maloch','Dark Slayer'),(9,'Omen','Dark Slayer'),(10,'Qi','Dark Slayer'),(11,'Riktor','Dark Slayer'),(12,'Roxie','Dark Slayer'),(13,'Skud','Dark Slayer'),(14,'Tachi','Dark Slayer'),(15,'Yena','Dark Slayer'),(16,'Zuka','Dark Slayer'),(17,'Aoi','Jungle'),(18,'Biron','Jungle'),(19,'Butterfly','Jungle'),(20,'Enzo','Jungle'),(21,'Keera','Jungle'),(22,'Kriknak','Jungle'),(23,'Murad','Jungle'),(24,'Nakroth','Jungle'),(25,'Pain','Jungle'),(26,'Quillen','Jungle'),(27,'Sinestrea','Jungle'),(28,'Wukong','Jungle'),(29,'Yan','Jungle'),(30,'Zill','Jungle'),(31,'Zephys','Jungle'),(32,'Aleister','Mid Lane'),(33,'Azzen\'Ka','Mid Lane'),(34,'Diao Chan','Mid Lane'),(35,'Dirak','Mid Lane'),(36,'Iggy','Mid Lane'),(37,'Ilumia','Mid Lane'),(38,'Jinna','Mid Lane'),(39,'Kahlii','Mid Lane'),(40,'Krixi','Mid Lane'),(41,'Lauriel','Mid Lane'),(42,'Liliana','Mid Lane'),(43,'Lorion','Mid Lane'),(44,'Manga','Mid Lane'),(45,'Marja','Mid Lane'),(46,'Natalya','Mid Lane'),(47,'Preyta','Mid Lane'),(48,'Raz','Mid Lane'),(49,'Sephera','Mid Lane'),(50,'Tulen','Mid Lane'),(51,'Veera','Mid Lane'),(52,'Yue','Mid Lane'),(53,'Capheny','Dragon Lane'),(54,'Celica','Dragon Lane'),(55,'Eland\'orr','Dragon Lane'),(56,'Elsu','Dragon Lane'),(57,'Hayate','Dragon Lane'),(58,'Joker','Dragon Lane'),(59,'Klass','Dragon Lane'),(60,'Laville','Dragon Lane'),(61,'Moren','Dragon Lane'),(62,'Slimz','Dragon Lane'),(63,'Stacia','Dragon Lane'),(64,'Teeri','Dragon Lane'),(65,'Tel\'Annas','Dragon Lane'),(66,'Thorne','Dragon Lane'),(67,'Valhein','Dragon Lane'),(68,'Violet','Dragon Lane'),(69,'Wisp','Dragon Lane'),(70,'Yorn','Dragon Lane'),(71,'Alice','Support / Tank'),(72,'Annette','Support / Tank'),(73,'Arum','Support / Tank'),(74,'Aya','Support / Tank'),(75,'Baldum','Support / Tank'),(76,'Chaugnar','Support / Tank'),(77,'Cresht','Support / Tank'),(78,'Gildur','Support / Tank'),(79,'Grakk','Support / Tank'),(80,'Helen','Support / Tank'),(81,'Krouros','Support / Tank'),(82,'Lumburr','Support / Tank'),(83,'Mina','Support / Tank'),(84,'Ming','Support / Tank'),(85,'Ormarr','Support / Tank'),(86,'TeeMee','Support / Tank'),(87,'Thane','Support / Tank'),(88,'Toro','Support / Tank'),(89,'Wonder Woman','Support / Tank'),(90,'Xeniel','Support / Tank'),(91,'Y\'bneth','Support / Tank'),(92,'Zip','Support / Tank');
/*!40000 ALTER TABLE `heroes` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- สร้างบัญชี Admin: สมัครสมาชิกผ่านหน้าเว็บก่อน แล้วรันคำสั่งนี้ (เปลี่ยน your_username)
-- UPDATE users SET role = 'admin' WHERE username = 'your_username';
