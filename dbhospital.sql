-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for db_hospital
CREATE DATABASE IF NOT EXISTS `db_hospital` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `db_hospital`;

-- Dumping structure for table db_hospital.departments
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table db_hospital.departments: ~18 rows (approximately)
INSERT INTO `departments` (`id`, `name`, `link_url`) VALUES
	(10, 'OPD (ผู้ป่วยนอก)', NULL),
	(11, 'อุบัติเหตุ-ฉุกเฉิน', NULL),
	(13, 'CCU', NULL),
	(14, 'NICU', NULL),
	(15, 'Stroke Unit', NULL),
	(16, 'ห้องผ่าตัด', NULL),
	(17, 'วิสัญญี', NULL),
	(18, 'อายุรกรรมชาย', NULL),
	(19, 'อายุรกรรมหญิง', NULL),
	(20, 'ศัลยกรรมชาย', NULL),
	(21, 'ศัลยกรรมหญิง', NULL),
	(22, 'สูตินรีเวชกรรม', NULL),
	(23, 'ห้องคลอด', NULL),
	(24, 'กุมารเวชกรรม', NULL),
	(25, 'ศัลยกรรมกระดูก', NULL),
	(26, 'ไตเทียม', NULL),
	(27, 'CSSD', 'https://www.google.com/?hl=th&zx=1781661033936'),
	(28, 'รักษ์จิต', NULL);

-- Dumping structure for table db_hospital.department_contents
CREATE TABLE IF NOT EXISTS `department_contents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `department_id` int NOT NULL,
  `section` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text,
  `file_name` varchar(255) DEFAULT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_department_section` (`department_id`,`section`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table db_hospital.department_contents: ~6 rows (approximately)
INSERT INTO `department_contents` (`id`, `department_id`, `section`, `title`, `content`, `file_name`, `link_url`, `sort_order`, `created_at`) VALUES
	(1, 24, 'knowledge', 'แนะนำหอผู้ป่วยกุมารเวชกรรม', '', '1781844524_dept_content_______________________________________________________________________________.MP4', NULL, 1, '2026-06-19 04:48:44'),
	(4, 24, 'knowledge', 'กำหนดแนวทางลดข้อร้องเรียน', '', '1781851229_dept_content____________________________________________________________________________.pdf', 'https://drive.google.com/drive/folders/100m-vsj3rdw-SrBSzZSlE4VycbyOanxI', 2, '2026-06-19 06:40:29'),
	(5, 24, 'knowledge', 'บอร์ดให้ความรู้ หอผู้ป่วยกุมานเวชกรรม ชักจากไข้สูง', '', '1781851836_dept_content___________________________________________________________________________________________________________________________________________________.pdf', NULL, 3, '2026-06-19 06:50:36'),
	(6, 24, 'knowledge', 'บอร์ดให้ความรู้ หอผู้ป่วยกุมารเวชกรรม', '', '1781851981_dept_content______________________________________________________________________________________________________________.pdf', NULL, 4, '2026-06-19 06:53:01'),
	(8, 24, 'personnel', 'นางสาวจุฑาทิพย์ ถนอมทรัพย์', 'ผู้ช่วยพยาบาล', '1781853231_dept_content_IMG_4124.JPG', NULL, 1, '2026-06-19 07:11:57'),
	(9, 24, 'service_profile', 'SP_หอผู้ป่วยกุมารเวชกรรมปีงบประมาณ2568', '', '1782110692_dept_content_SP______________________________________________________________________________68__1_.docx', NULL, 1, '2026-06-22 06:44:52'),
	(10, 24, 'structure', 'โครงสร้างการบริหารงานตึกเด็ก', '', '1782118135_dept_content______________________________________________________________________________________.pdf', NULL, 1, '2026-06-22 08:48:55'),
	(11, 24, 'knowledge', 'รายชื่อคณะกรรมการ หน่วยงาน หอผู้ป่วยเด็ก', '', '1782118171_dept_content_____________________________________________________________________________________________________________________.docx', NULL, 2, '2026-06-22 08:49:31'),
	(12, 24, 'structure', 'รายชื่อคณะกรรมการ หน่อยงาน หอผู้ป่วยเด็ก', '', '1782118335_dept_content_____________________________________________________________________________________________________________________.docx', NULL, 2, '2026-06-22 08:52:15'),
	(13, 24, 'wi', 'CNPG AGE', '', '1782118432_dept_content_CNPG_AGE.pdf', NULL, 1, '2026-06-22 08:53:52'),
	(14, 24, 'wi', 'CNPG febrile seizure', '', '1782118477_dept_content_CNPG_febrile_seizure.pdf', NULL, 2, '2026-06-22 08:54:37'),
	(15, 24, 'wi', 'CNPG-Thalassemia ในเด็ก', '', '1782118518_dept_content_CNPG-_Thalassemia___________________.pdf', NULL, 3, '2026-06-22 08:55:18'),
	(16, 24, 'knowledge', 'WI การพยาบาลป้องกันพลัดตกหกล้ม', '', '1782118568_dept_content_WI__________________________________________________________________________________.docx', NULL, 4, '2026-06-22 08:56:08'),
	(17, 24, 'wi', 'WI การพยาบาลป้องกันพลัดตกหกล้ม', '', '1782118660_dept_content_WI__________________________________________________________________________________.docx', NULL, 4, '2026-06-22 08:57:40');

-- Dumping structure for table db_hospital.department_pages
CREATE TABLE IF NOT EXISTS `department_pages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dept_id` int NOT NULL,
  `content_title` varchar(255) NOT NULL,
  `content_body` longtext,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `dept_id` (`dept_id`),
  CONSTRAINT `department_pages_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table db_hospital.department_pages: ~0 rows (approximately)

-- Dumping structure for table db_hospital.events
CREATE TABLE IF NOT EXISTS `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text,
  `event_date` date NOT NULL,
  `image_name` varchar(255) DEFAULT 'default.jpg',
  `link_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table db_hospital.events: ~4 rows (approximately)
INSERT INTO `events` (`id`, `title`, `content`, `event_date`, `image_name`, `link_url`) VALUES
	(4, 'กิจกรรมสวมชุดไทย ร่วมใส่บาตร เนื่องในวันสำคัญทางศาสนา', '', '2026-06-16', 'default.jpg', NULL),
	(5, 'ประชุมวิชาการ KM Day กลุ่มงานการพยาบาล ประจำปี 2569', '', '2026-06-16', 'default.jpg', NULL),
	(6, 'กิจกรรมวันพยาบาลสากล 12 พฤษภาคม 2569 "Our Nurses. Our Future."', '', '2026-06-16', 'default.jpg', NULL),
	(7, 'โครงการอบรมการช่วยฟื้นคืนชีพ (CPR) สำหรับบุคลากร รุ่นที่ 3/2569', '', '2026-06-16', 'default.jpg', NULL);

-- Dumping structure for table db_hospital.news
CREATE TABLE IF NOT EXISTS `news` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text,
  `image_name` varchar(255) DEFAULT 'default.jpg',
  `is_new` tinyint(1) DEFAULT '1',
  `link_url` varchar(255) DEFAULT NULL,
  `created_at` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table db_hospital.news: ~6 rows (approximately)
INSERT INTO `news` (`id`, `title`, `content`, `image_name`, `is_new`, `link_url`, `created_at`) VALUES
	(4, 'แจ้งกำหนดการประชุมวิชาการประจำปีกลุ่มงานการพยาบาล', '', 'default.jpg', 0, NULL, '2026-06-16'),
	(5, 'ผลการประเมินคุณภาพการพยาบาล ประจำไตรมาส 1/2569', '', 'default.jpg', 0, NULL, '2026-06-16'),
	(6, 'โครงการอบรมการช่วยฟื้นคืนชีพขั้นพื้นฐาน (BLS) รุ่นที่ 2', '', 'default.jpg', 0, NULL, '2026-06-16'),
	(7, ' แนวทางปฏิบัติการดูแลผู้ป่วย Sepsis ฉบับปรับปรุง 2569', '', 'default.jpg', 0, NULL, '2026-06-16'),
	(8, 'ประกาศผลการคัดเลือกพยาบาลวิชาชีพ (สัญญาจ้าง)', '', 'default.jpg', 1, NULL, '2026-06-16'),
	(9, 'รับสมัครพยาบาลวิชาชีพ ประจำปี 2569 จำนวน 12 อัตรา', '', 'default.jpg', 1, NULL, '2026-06-16');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
