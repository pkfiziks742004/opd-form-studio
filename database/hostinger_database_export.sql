-- MySQL dump 10.13  Distrib 9.6.0, for macos15.7 (arm64)
--
-- Host: 127.0.0.1    Database: opd_form_studio
-- ------------------------------------------------------
-- Server version	9.6.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `action` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_created` (`created_at`),
  KEY `fk_audit_user` (`user_id`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'create','patient',1,'{\"name\":\"Wevdev\"}','2026-09-20 08:35:58'),(2,1,'create','patient',2,'{\"name\":\"hb\\\\\\\\\\\\\"}','2026-09-20 11:49:43'),(3,2,'create','patient',4,'{\"name\":\"hb\\\\\\\\\\\\\"}','2026-09-20 12:16:54');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uhid` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `age` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sex` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_number` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `bill_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time DEFAULT NULL,
  `panel` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doctor_dept` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `room_no` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_no` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_patients_visit_date` (`visit_date`),
  KEY `idx_patients_uhid` (`uhid`),
  KEY `idx_patients_name` (`name`),
  KEY `idx_patients_bill` (`bill_no`),
  KEY `idx_patients_phone` (`contact_number`),
  KEY `fk_patients_user` (`created_by`),
  CONSTRAINT `fk_patients_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
INSERT INTO `patients` VALUES (1,'UHID-20260920-140538','Wevdev','','Male','hj','hb','gh','BILL-20260920-140549','2026-09-20','14:05:58','vgg','gh','','',1,'2026-09-20 08:35:58','2026-09-20 11:44:20'),(4,'UHID-20260920-174641','hb\\\\\\','8','Female','hj','9898','','','2026-09-20','17:46:00','','IVF','','',2,'2026-09-20 12:16:54','2026-09-20 12:16:54');
/*!40000 ALTER TABLE `patients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `setting_key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('default_template_id','2','2026-09-20 09:08:08'),('motherland_tpl_accent_color','#068938','2026-09-20 09:25:03'),('motherland_tpl_accent_height','28','2026-09-20 10:48:16'),('motherland_tpl_default_print_pages','2','2026-09-20 12:15:18'),('motherland_tpl_doc_title','Consultation Paper(OPD)','2026-09-20 09:30:53'),('motherland_tpl_doctor_dept','IVF','2026-09-20 09:07:30'),('motherland_tpl_doctor_name','Dr. ANVITI SARAF','2026-09-20 09:07:30'),('motherland_tpl_email','info@motherlandhospital.com','2026-09-20 09:07:30'),('motherland_tpl_enable_two_pages','0','2026-09-20 10:05:34'),('motherland_tpl_hospital_address','Hospital.: Sector 119, Noida - 201305, U.P., India','2026-09-20 09:07:30'),('motherland_tpl_hospital_name','Motherland','2026-09-20 09:30:53'),('motherland_tpl_hospital_tagline','HOSPITAL','2026-09-20 09:24:30'),('motherland_tpl_icon_path','uploads/templates/icon_de10a3721f2f6da9.jpg','2026-09-20 10:13:27'),('motherland_tpl_lbl_address','Address','2026-09-20 09:30:53'),('motherland_tpl_lbl_age_sex','Age/Sex','2026-09-20 09:30:53'),('motherland_tpl_lbl_allergies','Allergies If Any:','2026-09-20 09:30:53'),('motherland_tpl_lbl_app','App No','2026-09-20 09:30:53'),('motherland_tpl_lbl_bill','Bill No.','2026-09-20 09:30:53'),('motherland_tpl_lbl_bmi','BMI:','2026-09-20 09:30:53'),('motherland_tpl_lbl_bp','BP:','2026-09-20 09:30:53'),('motherland_tpl_lbl_contact','Contact No.','2026-09-20 09:30:53'),('motherland_tpl_lbl_date','Date','2026-09-20 09:30:53'),('motherland_tpl_lbl_dept','Doctor Dept','2026-09-20 09:30:53'),('motherland_tpl_lbl_guardian','Guardian','2026-09-20 09:30:53'),('motherland_tpl_lbl_height','Height:','2026-09-20 09:30:53'),('motherland_tpl_lbl_name','Name','2026-09-20 09:30:53'),('motherland_tpl_lbl_pain','Pain Score(0-10):','2026-09-20 09:30:53'),('motherland_tpl_lbl_panel','Panel','2026-09-20 09:30:53'),('motherland_tpl_lbl_pulse','Pulse:','2026-09-20 09:30:53'),('motherland_tpl_lbl_room','Room No','2026-09-20 09:30:53'),('motherland_tpl_lbl_temp','Temp:','2026-09-20 09:30:53'),('motherland_tpl_lbl_uhid','UHID','2026-09-20 09:30:53'),('motherland_tpl_lbl_weight','Weight:','2026-09-20 09:30:53'),('motherland_tpl_phone_landline','+91 120 4154949','2026-09-20 09:07:30'),('motherland_tpl_phone_whatsapp','+91 99937 77444','2026-09-20 09:07:30'),('motherland_tpl_reg_office','Reg. Office.: House No. - 227, Main Road\r\nVillage Khichipur, Delhi - 110091\r\nCIN: U85110DL1999PTC098269','2026-09-20 09:07:30'),('motherland_tpl_show_address','1','2026-09-20 10:05:34'),('motherland_tpl_show_age_sex','1','2026-09-20 10:05:34'),('motherland_tpl_show_app','1','2026-09-20 10:05:34'),('motherland_tpl_show_bill','1','2026-09-20 10:05:34'),('motherland_tpl_show_contact','1','2026-09-20 10:05:34'),('motherland_tpl_show_date','1','2026-09-20 10:05:34'),('motherland_tpl_show_dept','1','2026-09-20 10:05:34'),('motherland_tpl_show_doctor_box','0','2026-09-20 10:21:43'),('motherland_tpl_show_footer','1','2026-09-20 10:05:34'),('motherland_tpl_show_guardian','1','2026-09-20 10:05:34'),('motherland_tpl_show_header','1','2026-09-20 10:05:34'),('motherland_tpl_show_name','1','2026-09-20 10:05:34'),('motherland_tpl_show_panel','1','2026-09-20 10:05:34'),('motherland_tpl_show_patient_info','1','2026-09-20 10:05:34'),('motherland_tpl_show_room','1','2026-09-20 10:05:34'),('motherland_tpl_show_title','1','2026-09-20 10:05:34'),('motherland_tpl_show_uhid','1','2026-09-20 10:05:34'),('motherland_tpl_show_validity_note','0','2026-09-20 12:12:34'),('motherland_tpl_show_vital_allergies','1','2026-09-20 10:05:34'),('motherland_tpl_show_vital_bmi','1','2026-09-20 10:05:34'),('motherland_tpl_show_vital_bp','1','2026-09-20 10:05:34'),('motherland_tpl_show_vital_height','1','2026-09-20 10:05:34'),('motherland_tpl_show_vital_pain','1','2026-09-20 10:05:34'),('motherland_tpl_show_vital_pulse','1','2026-09-20 10:05:34'),('motherland_tpl_show_vital_temp','1','2026-09-20 10:05:34'),('motherland_tpl_show_vital_weight','1','2026-09-20 10:05:34'),('motherland_tpl_show_vitals','0','2026-09-20 10:20:59'),('motherland_tpl_show_watermark','1','2026-09-20 10:05:34'),('motherland_tpl_unit_bp','(mmHG)','2026-09-20 09:30:53'),('motherland_tpl_unit_height','(cm)','2026-09-20 09:30:53'),('motherland_tpl_unit_pulse','/min','2026-09-20 09:30:53'),('motherland_tpl_unit_temp','°C','2026-09-20 09:30:53'),('motherland_tpl_unit_weight','(kg)','2026-09-20 09:30:53'),('motherland_tpl_validity_note','Bill is valid for 3 days Including date of Billing.','2026-09-20 09:07:30'),('motherland_tpl_watermark_opacity','0.06','2026-09-20 10:05:34'),('motherland_tpl_watermark_position','bottom-right','2026-09-20 10:48:16'),('motherland_tpl_watermark_size','170','2026-09-20 10:48:56'),('motherland_tpl_website','www.motherlandhospital.com','2026-09-20 09:07:30');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sheet_sync_queue`
--

DROP TABLE IF EXISTS `sheet_sync_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sheet_sync_queue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint unsigned NOT NULL,
  `payload_json` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','synced','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `attempts` int NOT NULL DEFAULT '0',
  `last_error` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_attempt_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_id` (`patient_id`),
  CONSTRAINT `fk_sync_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sheet_sync_queue`
--

LOCK TABLES `sheet_sync_queue` WRITE;
/*!40000 ALTER TABLE `sheet_sync_queue` DISABLE KEYS */;
INSERT INTO `sheet_sync_queue` VALUES (1,1,'{\"patient_id\":1,\"uhid\":\"UHID-20260920-140538\",\"name\":\"Wevdev\",\"age\":\"\",\"sex\":\"Male\",\"guardian\":\"hj\",\"contact_number\":\"hb\",\"address\":\"gh\",\"bill_no\":\"BILL-20260920-140549\",\"visit_date\":\"2026-09-20\",\"visit_time\":\"08:35:58\",\"panel\":\"vgg\",\"doctor_dept\":\"gh\",\"room_no\":\"\",\"app_no\":\"\",\"created_by\":\"Administrator\",\"created_at\":\"2026-09-20 14:05:58\",\"updated_at\":\"2026-09-20 14:05:58\"}','pending',0,NULL,NULL,'2026-09-20 08:35:58'),(3,4,'{\"patient_id\":4,\"uhid\":\"UHID-20260920-174641\",\"name\":\"hb\\\\\\\\\\\\\",\"age\":\"8\",\"sex\":\"Female\",\"guardian\":\"hj\",\"contact_number\":\"9898\",\"address\":\"\",\"bill_no\":\"\",\"visit_date\":\"2026-09-20\",\"visit_time\":\"17:46:00\",\"panel\":\"\",\"doctor_dept\":\"IVF\",\"room_no\":\"\",\"app_no\":\"\",\"created_by\":\"rec\",\"created_at\":\"2026-09-20 17:46:54\",\"updated_at\":\"2026-09-20 17:46:54\"}','pending',0,NULL,NULL,'2026-09-20 12:16:54');
/*!40000 ALTER TABLE `sheet_sync_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `template_layouts`
--

DROP TABLE IF EXISTS `template_layouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `template_layouts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `layout_json` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_template_user` (`template_id`,`user_id`),
  KEY `fk_layout_user` (`user_id`),
  CONSTRAINT `fk_layout_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_layout_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `template_layouts`
--

LOCK TABLES `template_layouts` WRITE;
/*!40000 ALTER TABLE `template_layouts` DISABLE KEYS */;
INSERT INTO `template_layouts` VALUES (1,1,1,'{\"uhid\":{\"x\":18,\"y\":12.6,\"fontSize\":12,\"width\":250,\"fontWeight\":\"500\"},\"name\":{\"x\":18,\"y\":14.4,\"fontSize\":12,\"width\":250,\"fontWeight\":\"500\"},\"age_sex\":{\"x\":18,\"y\":16.2,\"fontSize\":12,\"width\":250,\"fontWeight\":\"500\"},\"guardian\":{\"x\":18,\"y\":18,\"fontSize\":12,\"width\":250,\"fontWeight\":\"500\"},\"contact_number\":{\"x\":18,\"y\":19.8,\"fontSize\":12,\"width\":250,\"fontWeight\":\"500\"},\"address\":{\"x\":18,\"y\":21.6,\"fontSize\":12,\"width\":320,\"fontWeight\":\"500\"},\"bill_no\":{\"x\":74,\"y\":12.6,\"fontSize\":12,\"width\":190,\"fontWeight\":\"500\"},\"date\":{\"x\":74,\"y\":14.4,\"fontSize\":12,\"width\":190,\"fontWeight\":\"500\"},\"panel\":{\"x\":74,\"y\":16.2,\"fontSize\":12,\"width\":190,\"fontWeight\":\"500\"},\"doctor_dept\":{\"x\":74,\"y\":18,\"fontSize\":12,\"width\":190,\"fontWeight\":\"500\"},\"room_no\":{\"x\":74,\"y\":19.8,\"fontSize\":12,\"width\":190,\"fontWeight\":\"500\"},\"app_no\":{\"x\":74,\"y\":21.6,\"fontSize\":12,\"width\":190,\"fontWeight\":\"500\"}}','2026-09-20 09:09:14');
/*!40000 ALTER TABLE `template_layouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `templates`
--

DROP TABLE IF EXISTS `templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `templates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `width` int DEFAULT NULL,
  `height` int DEFAULT NULL,
  `default_layout_json` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `template_type` enum('image','code') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'image',
  PRIMARY KEY (`id`),
  KEY `fk_templates_user` (`created_by`),
  CONSTRAINT `fk_templates_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `templates`
--

LOCK TABLES `templates` WRITE;
/*!40000 ALTER TABLE `templates` DISABLE KEYS */;
INSERT INTO `templates` VALUES (1,'Sample OPD Template','assets/sample-opd-template.jpg','sample-opd-template.jpg','image/jpeg',960,1280,'{\"uhid\":{\"x\":18.0,\"y\":12.6,\"fontSize\":12,\"width\":250,\"fontWeight\":\"500\"},\"name\":{\"x\":18.0,\"y\":14.4,\"fontSize\":12,\"width\":250,\"fontWeight\":\"500\"},\"age_sex\":{\"x\":18.0,\"y\":16.2,\"fontSize\":12,\"width\":250,\"fontWeight\":\"500\"},\"guardian\":{\"x\":18.0,\"y\":18.0,\"fontSize\":12,\"width\":250,\"fontWeight\":\"500\"},\"contact_number\":{\"x\":18.0,\"y\":19.8,\"fontSize\":12,\"width\":250,\"fontWeight\":\"500\"},\"address\":{\"x\":18.0,\"y\":21.6,\"fontSize\":12,\"width\":320,\"fontWeight\":\"500\"},\"bill_no\":{\"x\":74.0,\"y\":12.6,\"fontSize\":12,\"width\":190,\"fontWeight\":\"500\"},\"date\":{\"x\":74.0,\"y\":14.4,\"fontSize\":12,\"width\":190,\"fontWeight\":\"500\"},\"panel\":{\"x\":74.0,\"y\":16.2,\"fontSize\":12,\"width\":190,\"fontWeight\":\"500\"},\"doctor_dept\":{\"x\":74.0,\"y\":18.0,\"fontSize\":12,\"width\":190,\"fontWeight\":\"500\"},\"room_no\":{\"x\":74.0,\"y\":19.8,\"fontSize\":12,\"width\":190,\"fontWeight\":\"500\"},\"app_no\":{\"x\":74.0,\"y\":21.6,\"fontSize\":12,\"width\":190,\"fontWeight\":\"500\"}}',0,NULL,'2026-09-20 08:34:43','image'),(2,'Motherland Hospital (Code Template)','code:motherland','motherland_code_template','text/html',794,1123,'{}',1,NULL,'2026-09-20 08:59:55','code');
/*!40000 ALTER TABLE `templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_field_permissions`
--

DROP TABLE IF EXISTS `user_field_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_field_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `field_key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_field` (`user_id`,`field_key`),
  CONSTRAINT `fk_permission_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_field_permissions`
--

LOCK TABLES `user_field_permissions` WRITE;
/*!40000 ALTER TABLE `user_field_permissions` DISABLE KEYS */;
INSERT INTO `user_field_permissions` VALUES (1,2,'uhid',1,1),(2,2,'name',1,1),(3,2,'age_sex',1,1),(4,2,'guardian',1,1),(5,2,'contact_number',1,1),(6,2,'address',1,1),(7,2,'bill_no',1,1),(8,2,'date',1,1),(9,2,'panel',1,1),(10,2,'doctor_dept',1,1),(11,2,'room_no',1,1),(12,2,'app_no',1,1);
/*!40000 ALTER TABLE `user_field_permissions` ENABLE KEYS */;
--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('info','alert','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `target_role` enum('all','reception') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reception',
  `created_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_created` (`created_at`),
  KEY `idx_notif_target` (`target_role`),
  KEY `fk_notif_user` (`created_by`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notification_reads`
--

DROP TABLE IF EXISTS `notification_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_reads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notification_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notif_user` (`notification_id`,`user_id`),
  KEY `idx_reads_user` (`user_id`),
  KEY `fk_reads_notif` (`notification_id`),
  CONSTRAINT `fk_reads_notif` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','reception') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reception',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrator','admin','$2y$12$HKfFYFeAt78bYbnXlopjXOWgkOBt3xwxB3rt1GxkFwHDu15KMHLIK','admin',1,'2026-09-20 08:34:44'),(2,'rec','rec','$2y$12$Zf3mkjZnxuagSDJgL2zIi.zTAEnJ9d7.VJrQ1X2LVjMlMpdcAEIC6','reception',1,'2026-09-20 12:16:02');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-20 19:18:37
