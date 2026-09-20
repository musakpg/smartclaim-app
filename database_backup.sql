-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: smartclaim_db
-- ------------------------------------------------------
-- Server version	8.0.30

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
-- Table structure for table `ai_learning_feedbacks`
--

DROP TABLE IF EXISTS `ai_learning_feedbacks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_learning_feedbacks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `merchant_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `predicted_category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `corrected_category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `raw_text_sample` longtext COLLATE utf8mb4_unicode_ci,
  `extracted_keywords` json DEFAULT NULL,
  `is_applied` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_learning_feedbacks_user_id_foreign` (`user_id`),
  CONSTRAINT `ai_learning_feedbacks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_learning_feedbacks`
--

LOCK TABLES `ai_learning_feedbacks` WRITE;
/*!40000 ALTER TABLE `ai_learning_feedbacks` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_learning_feedbacks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json DEFAULT NULL,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'CLAIM_SUBMITTED','\"Staff submitted expense voucher #CLM-2 (Star Grocer Sdn Bhd) valued at RM 19.80\"','192.168.0.50','2026-08-21 16:26:10','2026-08-21 16:26:10'),(2,2,'CLAIM_Pre-Approved','\"Claim #CLM-2 status updated to Pre-Approved by Hazman (Finance Auditor)\"','192.168.0.50','2026-08-21 16:26:53','2026-08-21 16:26:53'),(3,3,'CLAIM_Approved','\"Claim #CLM-2 status updated to Approved by Aero Art Manager\"','192.168.0.50','2026-08-21 16:29:09','2026-08-21 16:29:09'),(4,2,'PAYMENT_DISBURSED','\"Payment of RM 19.80 settled to staff for #CLM-2 (Ref: sawdadas)\"','192.168.0.50','2026-08-23 14:52:32','2026-08-23 14:52:32'),(5,2,'CLAIM_Pre-Approved','\"Claim #CLM-1 status updated to Pre-Approved by Hazman (Finance Auditor)\"','192.168.0.50','2026-08-23 15:18:40','2026-08-23 15:18:40'),(6,3,'CLAIM_Approved','\"Claim #CLM-1 status updated to Approved by Aero Art Manager\"','192.168.0.50','2026-08-23 15:19:00','2026-08-23 15:19:00'),(7,2,'PAYMENT_DISBURSED','\"Payment of RM 111.56 settled to staff for #CLM-1 (Ref: 20260821BKRMMYKL040OQR40974071)\"','192.168.0.50','2026-08-23 15:24:11','2026-08-23 15:24:11'),(8,1,'CLAIM_SUBMITTED','\"Staff submitted expense voucher #CLM-3 (Petronas) valued at RM 140.00\"','192.168.0.50','2026-08-23 16:37:07','2026-08-23 16:37:07'),(9,2,'CLAIM_Pre-Approved','\"Claim #CLM-3 status updated to Pre-Approved by Hazman (Finance Auditor)\"','192.168.0.50','2026-08-23 16:37:30','2026-08-23 16:37:30'),(10,3,'CLAIM_Approved','\"Claim #CLM-3 status updated to Approved by Aero Art Manager\"','192.168.0.50','2026-08-23 16:38:07','2026-08-23 16:38:07'),(11,2,'PAYMENT_DISBURSED','\"Payment of RM 140.00 settled to staff for #CLM-3 (Ref: 202608217DDAE28E)\"','192.168.0.50','2026-08-23 16:40:50','2026-08-23 16:40:50'),(12,1,'CLAIM_SUBMITTED','\"Staff submitted expense voucher #CLM-4 (Petronas) valued at RM 140.00\"','192.168.0.50','2026-08-25 13:31:13','2026-08-25 13:31:13');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_advances`
--

DROP TABLE IF EXISTS `cash_advances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_advances` (
  `advance_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purpose` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_amount` decimal(10,2) NOT NULL,
  `settled_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `remaining_balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `required_date` date NOT NULL,
  `status` enum('Pending','Approved','Rejected','Settled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `manager_remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`advance_id`),
  KEY `cash_advances_user_id_foreign` (`user_id`),
  CONSTRAINT `cash_advances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_advances`
--

LOCK TABLES `cash_advances` WRITE;
/*!40000 ALTER TABLE `cash_advances` DISABLE KEYS */;
INSERT INTO `cash_advances` VALUES (1,1,'COMPTIAA','TEST',1111.00,0.00,1111.00,'2026-08-27','Pending',NULL,'2026-08-25 13:41:34','2026-08-25 13:41:34');
/*!40000 ALTER TABLE `cash_advances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `claim_items`
--

DROP TABLE IF EXISTS `claim_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `claim_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `claim_id` bigint unsigned NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `claim_items_claim_id_foreign` (`claim_id`),
  CONSTRAINT `claim_items_claim_id_foreign` FOREIGN KEY (`claim_id`) REFERENCES `claims` (`claim_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `claim_items`
--

LOCK TABLES `claim_items` WRITE;
/*!40000 ALTER TABLE `claim_items` DISABLE KEYS */;
INSERT INTO `claim_items` VALUES (6,4,'RON95 Fuel',1,140.00,140.00,'2026-08-25 13:31:13','2026-08-25 13:31:13');
/*!40000 ALTER TABLE `claim_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `claims`
--

DROP TABLE IF EXISTS `claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `claims` (
  `claim_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `vehicle_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `claim_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Receipt',
  `cash_advance_id` bigint unsigned DEFAULT NULL,
  `merchant_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_invoice_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_date` date DEFAULT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Cash',
  `business_purpose` text COLLATE utf8mb4_unicode_ci,
  `receipt_image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_image_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extracted_raw_text` longtext COLLATE utf8mb4_unicode_ci,
  `predicted_category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Unassigned',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `is_policy_violation` tinyint(1) NOT NULL DEFAULT '0',
  `risk_score` tinyint unsigned NOT NULL DEFAULT '0',
  `fraud_flags` json DEFAULT NULL,
  `exif_date_taken` datetime DEFAULT NULL,
  `policy_violation_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `payment_proof_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reimbursed_at` timestamp NULL DEFAULT NULL,
  `reimbursed_by` bigint unsigned DEFAULT NULL,
  `estimated_payout_date` date DEFAULT NULL,
  `mileage_km` decimal(8,2) DEFAULT NULL,
  `vehicle_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destination_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicle_plate_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`claim_id`),
  KEY `claims_user_id_foreign` (`user_id`),
  KEY `claims_vehicle_id_foreign` (`vehicle_id`),
  KEY `claims_reimbursed_by_foreign` (`reimbursed_by`),
  KEY `claims_cash_advance_id_foreign` (`cash_advance_id`),
  CONSTRAINT `claims_cash_advance_id_foreign` FOREIGN KEY (`cash_advance_id`) REFERENCES `cash_advances` (`advance_id`) ON DELETE SET NULL,
  CONSTRAINT `claims_reimbursed_by_foreign` FOREIGN KEY (`reimbursed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `claims_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `claims_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `claims`
--

LOCK TABLES `claims` WRITE;
/*!40000 ALTER TABLE `claims` DISABLE KEYS */;
INSERT INTO `claims` VALUES (1,1,1,'tests','Mileage',NULL,'Aero Art Transport (Car)','UTHM Kampus Cawangan Pagoh, Jalan Panchor, Panchor, Johor, Malaysia','NOT FOUND','2026-08-19','Allowance','test','receipts/akoPi2gI0FJW4cQOjFOuvoz3cCDWl6I3YOzR4GU0.jpg',NULL,'','Fuel / Automotive',111.56,'Reimbursed',0,0,NULL,NULL,NULL,'20260821BKRMMYKL040OQR40974071','2026-08-23 15:24:11','payment_proofs/22e2zWf433S49NY29mpmU7l63OLl3P7nLfI0jXU1.pdf',NULL,NULL,'2026-08-26',185.94,'Car','UTHM Kampus Cawangan Pagoh, Jalan Panchor, Panchor, Johor, Malaysia','Sheraton Petaling Jaya Hotel, Lorong Utara C, Pjs 52, Petaling Jaya, Selangor, Malaysia','JWA 1234','2026-08-18 16:32:07','2026-08-23 15:24:11'),(4,1,NULL,'Claim at Petronas','Receipt',NULL,'Petronas','PS KM 0.7 Besraya Arah Utara (Solaris Serdang), KM 0.7, Lebuhraya Sg. Besi','2BD13E','2026-06-10','Cash','TESTR','receipts/jjWCf66iCC2yeceXX5Gc1Pjc4Mmm497tpNMQpgy7.jpg','5780ccdeeef4cbfdf23c896b34ec112f4653ca21b879f8e95f088c046f1984d8','PETRONAS\r\nBESJAYA ENTERPRISE (002151855-H)\r\nPS KM 0.7 Besraya Arah Utara (Solaris Serdang)\r\nKM 0.7, Lebuhraya Sg. Besi\r\nPhone: 0389406046\r\nFINAL RECEIPT (COPY)\r\nDATE :10 JUN 2026\r\n:18:01:00\r\nTIME\r\nINV NO.: 2bd13e\r\nCashier: AMIRAQIFF BIN AMIR REZALI\r\n: POS A-PS KM 0.7 Besraya Arah Utara\r\nPOS\r\nSmartpay Topup\r\n(70838153....1421)\r\nPurchased Item(s)\r\n@140.00\r\nx1\r\n140.00\r\nSubtotal\r\n140.00\r\nRounding\r\n0.00\r\nGrand Total\r\n140.00\r\nAmount Paid (Cash)\r\n140.00\r\nAmount Tendered (Cash)\r\n140.00\r\nChange\r\n0.00\r\nScan for E-Invoice\r\nValidity to claim E-Invoice are in the same month only\r\n\"Grand Total is subject to Rounding Mechanism','Office Supplies',140.00,'Pending',1,0,'[]',NULL,'Exceeded single claim ceiling (Max: RM100.00)',NULL,NULL,NULL,NULL,NULL,'2026-09-01',NULL,NULL,NULL,NULL,NULL,'2026-08-25 13:31:13','2026-08-25 13:31:13');
/*!40000 ALTER TABLE `claims` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_policies`
--

DROP TABLE IF EXISTS `expense_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monthly_budget_cap` decimal(10,2) NOT NULL DEFAULT '500.00',
  `max_single_claim_limit` decimal(10,2) NOT NULL DEFAULT '150.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_policies_category_name_unique` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_policies`
--

LOCK TABLES `expense_policies` WRITE;
/*!40000 ALTER TABLE `expense_policies` DISABLE KEYS */;
INSERT INTO `expense_policies` VALUES (1,'Meals & Entertainment',300.00,60.00,1,'Staff refreshment & client meeting meals cap.','2026-08-21 13:04:41','2026-08-21 13:04:41'),(2,'Fuel / Automotive',450.00,120.00,1,'Official outstation & operational fleet fuel quota.','2026-08-21 13:04:41','2026-08-21 13:04:41'),(3,'Office Supplies',200.00,100.00,1,'Stationery, printing & office essentials ceiling.','2026-08-21 13:04:41','2026-08-21 13:04:41'),(4,'Accommodations',600.00,100.00,1,'Hotel lodging allowance for official company travel.','2026-08-21 13:04:41','2026-08-25 13:17:52');
/*!40000 ALTER TABLE `expense_policies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `in_app_notifications`
--

DROP TABLE IF EXISTS `in_app_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `in_app_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `target_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `in_app_notifications_user_id_foreign` (`user_id`),
  CONSTRAINT `in_app_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `in_app_notifications`
--

LOCK TABLES `in_app_notifications` WRITE;
/*!40000 ALTER TABLE `in_app_notifications` DISABLE KEYS */;
INSERT INTO `in_app_notifications` VALUES (1,1,'Claim Submitted','Your claim voucher #CLM-2 for RM 19.80 has been submitted for audit.','info','http://192.168.0.50:8000/claims/history',1,'2026-08-21 16:26:10','2026-08-23 14:56:14'),(2,1,'Claim Status: Pre-Approved','Your claim voucher #CLM-2 (Star Grocer Sdn Bhd) has been marked as Pre-Approved.','info','http://192.168.0.50:8000/claims/history',1,'2026-08-21 16:26:53','2026-08-23 14:56:14'),(3,1,'Claim Status: Approved','Your claim voucher #CLM-2 (Star Grocer Sdn Bhd) has been marked as Approved.','success','http://192.168.0.50:8000/claims/history',1,'2026-08-21 16:29:09','2026-08-23 14:56:14'),(4,1,'Payment Reimbursed','Your claim #CLM-2 (RM 19.80) has been successfully paid out. Reference: sawdadas','success','http://192.168.0.50:8000/reimbursement',1,'2026-08-23 14:52:32','2026-08-23 14:56:14'),(5,1,'Claim Status: Pre-Approved','Your claim voucher #CLM-1 (Aero Art Transport (Car)) has been marked as Pre-Approved.','info','http://192.168.0.50:8000/claims/history',1,'2026-08-23 15:18:40','2026-08-23 16:31:34'),(6,1,'Claim Status: Approved','Your claim voucher #CLM-1 (Aero Art Transport (Car)) has been marked as Approved.','success','http://192.168.0.50:8000/claims/history',1,'2026-08-23 15:19:00','2026-08-23 16:31:34'),(7,1,'Payment Reimbursed','Your claim voucher #CLM-1 (RM 111.56) has been successfully paid out. Reference: 20260821BKRMMYKL040OQR40974071','success','http://192.168.0.50:8000/reimbursement',1,'2026-08-23 15:24:11','2026-08-23 16:31:34'),(8,1,'Claim Submitted','Your claim voucher #CLM-3 for RM 140.00 has been submitted for audit.','info','http://192.168.0.50:8000/claims/history',1,'2026-08-23 16:37:07','2026-08-25 14:10:25'),(9,3,'Audit Alert: Flagged Claim','Staff submitted claim #CLM-3 flagged with Policy Breach','danger','http://192.168.0.50:8000/manager/verification?status=Pre-Approved',0,'2026-08-23 16:37:07','2026-08-23 16:37:07'),(10,1,'Claim Status: Pre-Approved','Your claim voucher #CLM-3 (Petronas) has been marked as Pre-Approved.','info','http://192.168.0.50:8000/claims/history',1,'2026-08-23 16:37:30','2026-08-25 14:10:25'),(11,1,'Claim Status: Approved','Your claim voucher #CLM-3 (Petronas) has been marked as Approved.','success','http://192.168.0.50:8000/claims/history',1,'2026-08-23 16:38:07','2026-08-25 14:10:25'),(12,1,'Payment Reimbursed','Your claim voucher #CLM-3 (RM 140.00) has been successfully paid out. Reference: 202608217DDAE28E','success','http://192.168.0.50:8000/reimbursement',1,'2026-08-23 16:40:50','2026-08-25 14:10:25'),(13,2,'🚀 Test Push SmartClaim','Notifikasi telefon berjaya berfungsi!','success','http://192.168.0.50:8000/dashboard',0,'2026-08-23 16:41:45','2026-08-23 16:41:45'),(14,2,'🚀 Test Push SmartClaim','Notifikasi telefon berjaya berfungsi!','success','http://192.168.0.50:8000/dashboard',0,'2026-08-23 16:42:08','2026-08-23 16:42:08'),(15,1,'🚀 Test Push SmartClaim','Notifikasi telefon berjaya berfungsi!','success','http://192.168.0.50:8000/dashboard',1,'2026-08-23 16:51:15','2026-08-25 14:10:25'),(16,1,'🚀 Test Push SmartClaim','Notifikasi telefon berjaya berfungsi!','success','http://192.168.0.50:8000/dashboard',1,'2026-08-23 16:53:26','2026-08-25 14:10:25'),(17,1,'🚀 Test Push SmartClaim','Notifikasi telefon berjaya berfungsi!','success','http://192.168.0.50:8000/dashboard',1,'2026-08-23 16:54:13','2026-08-25 14:10:25'),(18,1,'🚀 Test Push SmartClaim','Notifikasi telefon berjaya berfungsi!','success','http://192.168.0.50:8000/dashboard',1,'2026-08-23 16:54:25','2026-08-25 14:10:25'),(19,1,'🚀 Test Push SmartClaim','Notifikasi telefon berjaya berfungsi!','success','http://192.168.0.50:8000/dashboard',1,'2026-08-23 16:54:58','2026-08-25 14:10:25'),(20,1,'Claim Submitted','Your claim voucher #CLM-4 for RM 140.00 has been submitted for audit.','info','http://192.168.0.50:8000/claims/history',1,'2026-08-25 13:31:13','2026-08-25 14:10:25'),(21,3,'Audit Alert: Flagged Claim','Staff submitted claim #CLM-4 flagged with Policy Breach','danger','http://192.168.0.50:8000/manager/verification?status=Pre-Approved',0,'2026-08-25 13:31:13','2026-08-25 13:31:13'),(22,3,'New Cash Advance Requisition','Staff submitted Advance Requisition #ADV-1 for RM 1,111.00','info','http://192.168.0.50:8000/manager/cash-advances',0,'2026-08-25 13:41:34','2026-08-25 13:41:34');
/*!40000 ALTER TABLE `in_app_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2019_12_14_000001_create_personal_access_tokens_table',1),(2,'2026_06_10_180401_create_smart_claim_tables',1),(3,'2026_06_11_124152_create_claim_items_table',1),(4,'2026_06_14_050911_create_vehicle_logs_table',1),(5,'2026_06_15_030139_create_mileage_rates_table',1),(6,'2026_06_15_035529_create_audit_logs_table',1),(7,'2026_08_18_235906_create_vehicle_assignments_table',1),(8,'2026_08_19_005326_update_vehicles_table_add_verification_and_documents',2),(9,'2026_08_19_231130_add_banking_and_payout_fields_to_tables',3),(10,'2026_08_19_231450_create_model_benchmarks_table',4),(11,'2026_08_21_210344_create_expense_policies_table',5),(12,'2026_08_21_212300_create_ai_learning_feedbacks_table',6),(13,'2026_08_21_225427_add_fraud_metrics_to_claims_table',6),(14,'2026_08_21_230549_create_in_app_notifications_table',7),(15,'2026_08_21_231505_create_cash_advances_table',8),(16,'2026_08_23_223752_add_payment_settlement_fields_to_claims_table',9),(17,'2026_08_24_002558_create_push_subscriptions_table',10);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mileage_rates`
--

DROP TABLE IF EXISTS `mileage_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mileage_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_km` int NOT NULL,
  `max_km` int DEFAULT NULL,
  `rate` decimal(8,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mileage_rates`
--

LOCK TABLES `mileage_rates` WRITE;
/*!40000 ALTER TABLE `mileage_rates` DISABLE KEYS */;
INSERT INTO `mileage_rates` VALUES (1,'Car',0,50,0.80,'2026-08-18 16:07:20','2026-08-18 16:07:20'),(2,'Car',51,150,0.70,'2026-08-18 16:07:20','2026-08-18 16:07:20'),(3,'Car',151,9999,0.60,'2026-08-18 16:07:20','2026-08-18 16:07:20'),(4,'Motorcycle',0,50,0.50,'2026-08-18 16:07:20','2026-08-18 16:07:20'),(5,'Motorcycle',51,150,0.40,'2026-08-18 16:07:20','2026-08-18 16:07:20'),(6,'Motorcycle',151,9999,0.30,'2026-08-18 16:07:20','2026-08-18 16:07:20');
/*!40000 ALTER TABLE `mileage_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_benchmarks`
--

DROP TABLE IF EXISTS `model_benchmarks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_benchmarks` (
  `benchmark_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sample_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `raw_ocr_payload` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `actual_category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `predicted_category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actual_amount` decimal(10,2) NOT NULL,
  `extracted_amount` decimal(10,2) DEFAULT NULL,
  `is_category_correct` tinyint(1) NOT NULL DEFAULT '0',
  `is_amount_correct` tinyint(1) NOT NULL DEFAULT '0',
  `processing_time_ms` decimal(8,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`benchmark_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_benchmarks`
--

LOCK TABLES `model_benchmarks` WRITE;
/*!40000 ALTER TABLE `model_benchmarks` DISABLE KEYS */;
INSERT INTO `model_benchmarks` VALUES (1,'Petronas Dagangan Receipt #01','STESEN MINYAK PETRONAS SEKSYEN 7 SHAH ALAM PRIMAX 95 RON95 RM 50.00 CASH PAYMENT THANK YOU COME AGAIN','Fuel / Automotive','Fuel / Automotive',50.00,50.00,1,1,0.11,'2026-08-19 15:15:51','2026-08-25 13:29:38'),(2,'Starbucks Coffee Receipt #02','STARBUCKS COFFEE MALAYSIA SDN BHD 1X CARAMEL MACCHIATO 16.50 1X CHICKEN PIE 8.90 TOTAL AMOUNT RM 25.40 MASTER CARD','Meals & Entertainment','Meals & Entertainment',25.40,25.40,1,1,0.07,'2026-08-19 15:15:51','2026-08-25 13:29:38'),(3,'Popular Bookstore Supplies #03','POPULAR BOOK CO (M) SDN BHD A4 PAPER 80GSM INK CARTRIDGE 680 BLACK STAPLER BULLET TOTAL RM 88.50 BAYAR CASH','Office Supplies','Office Supplies',88.50,88.50,1,1,0.03,'2026-08-19 15:15:51','2026-08-25 13:29:38'),(4,'Shell Petrol Station #04','SHELL MALAYSIA TRADING SDN BHD PUMP 04 FUELS DIESEL EURO 5 RM 120.00 TOUCH N GO EWALLET','Fuel / Automotive','Fuel / Automotive',120.00,120.00,1,1,0.02,'2026-08-19 15:15:51','2026-08-25 13:29:38'),(5,'McDonalds Drive-Thru #05','GERBANG ALAF RESTAURANTS SDN BHD MCDONALDS AYAM GORENG MCD 3PC MEAL MCCHICKEN SET TOTAL DUE RM 34.20 VISA DEBIT','Meals & Entertainment','Meals & Entertainment',34.20,34.20,1,1,0.02,'2026-08-19 15:15:51','2026-08-25 13:29:38'),(6,'MR DIY Hardware & Stationery #06','MR D.I.Y. (KUCHAI LAMA) SDN BHD HIGHLIGHTER PEN TRANSPARENT TAPE SCISSORS OFFICE FILE TOTAL RM 19.90 CASH','Office Supplies','Office Supplies',19.90,19.90,1,1,0.02,'2026-08-19 15:15:51','2026-08-25 13:29:38'),(7,'Tealive Boba Beverage #07','LOOB HOLDING SDN BHD TEALIVE SIGNATURE BROWN SUGAR PEARL MILK TEA ROASTED MILK TEA TOTAL RM 18.00 DUITNOW QR','Meals & Entertainment','Meals & Entertainment',18.00,18.00,1,1,0.02,'2026-08-19 15:15:51','2026-08-25 13:29:38'),(8,'Caltex Petrol & Lubricant #08','CALTEX PETROLEUM MALAYSIA RON97 HAVOLINE ENGINE OIL TOTAL RM 95.00 CREDIT CARD APPROVED','Fuel / Automotive','Fuel / Automotive',95.00,95.00,1,1,0.02,'2026-08-19 15:15:51','2026-08-25 13:29:38'),(9,'Office Supplies & Toner #09','STATIONERY ENTERPRISE HP LASERJET TONER 85A A4 PHOTO PAPER PRINTER RIBBON TOTAL RM 210.00 CASH PAYMENT','Office Supplies','Office Supplies',210.00,210.00,1,1,0.02,'2026-08-19 15:15:51','2026-08-25 13:29:38'),(10,'KFC Restaurant Dinner #10','QSR STORES SDN BHD KFC RESTAURANT 9PC HOLIDAY BUCKET CHEEZY WEDGES DRINKS TOTAL RM 62.90 CASH','Meals & Entertainment','Meals & Entertainment',62.90,62.90,1,1,0.02,'2026-08-19 15:15:51','2026-08-25 13:29:38'),(11,'Petronas','PETRONAS\nWANIMAS ENTERPRISE (CA0154288-X)\nPS Sek 16 Bdr Baru Bangi\nLot Pt 71313 Hsd 140427, Seksyen 16 Bandar Baru\nBangi\nPhone: 03-89223811\nPREPAY\nPrimax 95 RM 97.23\nDATE\n14 MAY 2026\nRM3.870/L\nTOTAL\nTIME\n14:51:14\n(26.124 L)\nPump 3\nINV NO\nd5ff45\nMarket Price\nSubsidy Price\nSubsidised Litre\nMyKad Number\nBUDI RON95 Subsidy Details)\nPrevious Balance\nRemaining Balance\nCDB Reference No.\nCashier\nPOS\nRM 3.870/L\nRM 1.990/L\n25.124 L\n0665\n109.545L\n84.420L\n***138358423300650\nNorairen Atira Binti Zamri\nPOS B-PS Seksyen 16, Bandar Baru\nPrimax 95\nBudi95\nPurchased Item(s)\nBUDI\nMADANI\nRONGS\n97.23\n-47.23\nSubtotal\n50.00\nRounding\n0.00\nGrand Total\n50.00\nAmount Paid (Cash)\n50.00\nAmount Tendered (Cash)\n50.00\nChange\n0.00\nScan for E-invoice and Subsidy Details\nValidity to claim E-Invoice are in the same month only\n\"Grand Total is subject to Rounding Mechanism','Fuel / Automotive','Fuel / Automotive',50.00,97.23,1,0,0.05,'2026-08-19 15:37:35','2026-08-25 13:29:38'),(12,'PETRONAS','PETRONAS\nKOPERASI LLM BERHAD\nB3 0302(BHD)\nLEBUHRAYA SILK\n0387309333\n15MAY2026 04:53PM\nPRIMAX 95\nRM50.00\n12.920L@RM3.870/L\nTOTAL\nRM50.00\nINVOICE\nCard Desc\nVisa\nTerminal\n06510011\nsite Id\n430000016202101\nSite Tran No\n108423\nPOS STAN\n594859\nInvoice No\n72d38f\nRef\n005321065368\nCard\n463225.....4630\nCard Exp\nXX/XX\nAC Type\nCredit\nB. Approval\n178117\nTC\nA65730BFD17ABAE8\nApp\nVISA DEBIT\nApp ID\nA0000000031010\nAPPROVED 00\nIssued\n13\nAvailable Balance\n962\nMobile No\n6019.4466\nThank You For Visiting\nPETRONAS\nScan for E-Invoice','Fuel / Automotive','Fuel / Automotive',50.00,50.00,1,1,0.04,'2026-08-19 15:38:42','2026-08-25 13:29:38'),(13,'Petronas','PETRONAS\nWANIMAS ENTERPRISE (CA0154288-X)\nPS Sek 16 Bdr Baru Bangi\nLot Pt 71313 Hsd 140427, Seksyen 16 Bandar Baru\nBangi\nPhone: 03-89223811\nPREPAY\nPrimax 95 RM 97.23\nDATE\nRM3.870/L\nTOTAL\nTIME\n14 MAY 2026\n14:51:14\n(26.124 L)\nPump 3\nINV NO\nd5ff45\nMarket Price\nSubsidy Price\nSubsidised Litre\nMyKad Number\nBUDI RON95 Subsidy Details)\nPrevious Balance\nRemaining Balance\nCDB Reference No.\nCashier\nPOS\nRM 3.870/L\nRM 1.990/L\n25.124 L\n0665\n109.545L\n84.420L\n***138358423300650\nNorairen Atira Binti Zamri\nPOS B-PS Seksyen 16, Bandar Baru\nPrimax 95\nBudi95\nPurchased Item(s)\nBUDI\nMADANI\nRONGS\n97.23\n-47.23\nSubtotal\n50.00\nRounding\n0.00\nGrand Total\n50.00\nAmount Paid (Cash)\n50.00\nAmount Tendered (Cash)\n50.00\nChange\n0.00\nScan for E-invoice and Subsidy Details\nValidity to claim E-Invoice are in the same month only\n\"Grand Total is subject to Rounding Mechanism','Fuel / Automotive','Fuel / Automotive',50.00,97.23,1,0,0.05,'2026-08-19 15:39:30','2026-08-25 13:29:38'),(14,'SHELL','PETRONAS\nBESJAYA ENTERPRISE (002151855-H)\nPS KM 0.7 Besraya Arah Utara (Solaris Serdang)\nKM 0.7, Lebuhraya Sg. Besi\nPhone: 0389406046\nFINAL RECEIPT (COPY)\nDATE :10 JUN 2026\n:18:01:00\nTIME\nINV NO.: 2bd13e\nCashier: AMIRAQIFF BIN AMIR REZALI\n: POS A-PS KM 0.7 Besraya Arah Utara\nPOS\nSmartpay Topup\n(70838153....1421)\nPurchased Item(s)\n@140.00\nx1\n140.00\nSubtotal\n140.00\nRounding\n0.00\nGrand Total\n140.00\nAmount Paid (Cash)\n140.00\nAmount Tendered (Cash)\n140.00\nChange\n0.00\nScan for E-Invoice\nValidity to claim E-Invoice are in the same month only\n\"Grand Total is subject to Rounding Mechanism','Office Supplies','Fuel / Automotive',10.00,140.00,0,0,0.04,'2026-08-25 13:29:07','2026-08-25 13:29:38');
/*!40000 ALTER TABLE `model_benchmarks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `push_subscriptions`
--

DROP TABLE IF EXISTS `push_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subscribable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subscribable_id` bigint unsigned NOT NULL,
  `endpoint` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `public_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auth_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content_encoding` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `push_subscriptions_endpoint_unique` (`endpoint`),
  KEY `push_subscriptions_subscribable_type_subscribable_id_index` (`subscribable_type`,`subscribable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `push_subscriptions`
--

LOCK TABLES `push_subscriptions` WRITE;
/*!40000 ALTER TABLE `push_subscriptions` DISABLE KEYS */;
INSERT INTO `push_subscriptions` VALUES (9,'App\\Models\\User',1,'https://fcm.googleapis.com/fcm/send/fxPxopQlS1I:APA91bERNaFpuBvhY1RofEHgSjB4xR3AbfI4dVb0tbouj5II5rLWXHjFS0qKWy_CDnz0r20Pb4T6_lUvy5Iaj5rXuc7ytYFu_iT4Sty8lIUovGxC5_dilMOSaLJXLSk-aPPITiaofYli','BDcjE7h3REtp-LwCDXRsdkr8fQY0swGQmU_Uhe0mf3gnPc3TYVA26TJlfruyPBPwUIKIZeeYhX6m6fGRz8yuWME','gQ6TLreGEeD9KLmkNohV7w','aes128gcm','2026-08-23 16:57:30','2026-08-23 16:57:30');
/*!40000 ALTER TABLE `push_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_holder` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('Staff','Finance','Manager') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Staff',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Musa (Staff Employee)','musa@aeroart.com',NULL,NULL,NULL,'$2y$12$q0m4UrROrQMrp9Gn3zAqNOT.jBy6bkx9Cn5uJoa.yeeEKev/uB3tO','Staff','2026-08-18 16:07:19','2026-08-18 16:07:19'),(2,'Hazman (Finance Auditor)','finance@aeroart.com',NULL,NULL,NULL,'$2y$12$xYUX4Ib8tEyhIU26.KlceeabaufIUgZ6eeM4so1CyBl1yNxCZaWaq','Finance','2026-08-18 16:07:19','2026-08-18 16:07:19'),(3,'Aero Art Manager','manager@aeroart.com',NULL,NULL,NULL,'$2y$12$wkwQgLi5zSeMu/N0AGMKB.M/USw6KC0GVAPCoDh/97Qbmn.uK40ya','Manager','2026-08-18 16:07:20','2026-08-18 16:07:20');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_assignments`
--

DROP TABLE IF EXISTS `vehicle_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_assignments` (
  `assignment_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `checkout_at` datetime NOT NULL,
  `checkin_at` datetime DEFAULT NULL,
  `purpose` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`assignment_id`),
  KEY `vehicle_assignments_vehicle_id_foreign` (`vehicle_id`),
  KEY `vehicle_assignments_user_id_foreign` (`user_id`),
  CONSTRAINT `vehicle_assignments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `vehicle_assignments_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_assignments`
--

LOCK TABLES `vehicle_assignments` WRITE;
/*!40000 ALTER TABLE `vehicle_assignments` DISABLE KEYS */;
INSERT INTO `vehicle_assignments` VALUES (1,3,1,'2026-08-18 21:07:20',NULL,'Penghantaran drone & lawatan tapak klien Senai','2026-08-18 16:07:20','2026-08-18 16:07:20');
/*!40000 ALTER TABLE `vehicle_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_logs`
--

DROP TABLE IF EXISTS `vehicle_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_logs` (
  `log_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `operator_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_event` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plate_index` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_logs`
--

LOCK TABLES `vehicle_logs` WRITE;
/*!40000 ALTER TABLE `vehicle_logs` DISABLE KEYS */;
INSERT INTO `vehicle_logs` VALUES (1,'Aero Art Manager','VEHICLE_CREATE','WTP5642','Registered new company asset node: VOLVO S60 (Car) with deployment status set to Active.','192.168.0.50','2026-08-18 16:33:36','2026-08-18 16:33:36'),(2,'Musa (Staff Employee)','STAFF_VEHICLE_UPDATE','JWA 1234','Updated personal vehicle application parameters. Status reset to Pending review.','192.168.0.50','2026-08-25 14:13:58','2026-08-25 14:13:58'),(3,'Musa (Staff Employee)','STAFF_VEHICLE_UPDATE','JWA 1234','Updated personal vehicle application parameters. Status reset to Pending review.','192.168.0.50','2026-08-25 14:14:04','2026-08-25 14:14:04');
/*!40000 ALTER TABLE `vehicle_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `vehicle_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `plate_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand_model` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_type` enum('Car','Motorcycle') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Car',
  `engine_capacity` int NOT NULL DEFAULT '1500',
  `roadtax_expiry` date DEFAULT NULL,
  `grant_document_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `roadtax_document_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ownership_type` enum('personal','company') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'personal',
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `approval_status` enum('Pending','Approved','Rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `roadtax_renewal_status` enum('None','Pending_Review') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'None',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vehicle_id`),
  UNIQUE KEY `vehicles_plate_number_unique` (`plate_number`),
  KEY `vehicles_user_id_foreign` (`user_id`),
  KEY `vehicles_approved_by_foreign` (`approved_by`),
  CONSTRAINT `vehicles_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `vehicles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
INSERT INTO `vehicles` VALUES (1,1,'JWA 1234','PERODUA MYVI 1.5 AV','Car',1500,'2027-04-19',NULL,NULL,'personal','Active','Pending',NULL,NULL,NULL,'None','2026-08-18 16:07:20','2026-08-25 14:14:04'),(2,1,'JWB 5678','Yamaha Y15ZR','Motorcycle',150,'2026-08-09',NULL,NULL,'personal','Active','Pending',NULL,NULL,NULL,'None','2026-08-18 16:07:20','2026-08-18 16:07:20'),(3,NULL,'VAA 9988','Toyota Hiace Panel Van','Car',2500,'2027-08-19',NULL,NULL,'company','Active','Pending',NULL,NULL,NULL,'None','2026-08-18 16:07:20','2026-08-18 16:07:20'),(4,NULL,'WTP5642','VOLVO S60','Car',1500,NULL,NULL,NULL,'personal','Active','Pending',NULL,NULL,NULL,'None','2026-08-18 16:33:36','2026-08-18 16:33:36');
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-20 15:42:52
