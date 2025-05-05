-- MySQL dump 10.13  Distrib 8.0.41, for Linux (x86_64)
--
-- Host: localhost    Database: elitemailboxes
-- ------------------------------------------------------
-- Server version	8.0.41-0ubuntu0.24.04.1

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
-- Table structure for table `custom_roles`
--

DROP TABLE IF EXISTS `custom_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` smallint NOT NULL DEFAULT '1' COMMENT '0:inactive, 1:active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custom_roles`
--

LOCK TABLES `custom_roles` WRITE;
/*!40000 ALTER TABLE `custom_roles` DISABLE KEYS */;
INSERT INTO `custom_roles` VALUES (1,'Admin',1,'2025-05-03 12:27:08','2025-05-03 12:27:08'),(2,'Sub-Admin',1,'2025-05-03 12:27:08','2025-05-03 12:27:08'),(3,'Customer',1,'2025-05-03 12:27:08','2025-05-03 12:27:08'),(4,'Contractor',1,'2025-05-03 12:27:08','2025-05-03 12:27:08'),(5,'Mod',1,'2025-05-03 12:27:08','2025-05-03 12:27:08');
/*!40000 ALTER TABLE `custom_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feature_plan`
--

DROP TABLE IF EXISTS `feature_plan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_plan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plan_id` bigint unsigned NOT NULL,
  `feature_id` bigint unsigned NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `feature_plan_plan_id_feature_id_unique` (`plan_id`,`feature_id`),
  KEY `feature_plan_feature_id_foreign` (`feature_id`),
  CONSTRAINT `feature_plan_feature_id_foreign` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feature_plan_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feature_plan`
--

LOCK TABLES `feature_plan` WRITE;
/*!40000 ALTER TABLE `feature_plan` DISABLE KEYS */;
INSERT INTO `feature_plan` VALUES (4,3,2,NULL,'2025-05-04 11:48:31','2025-05-04 11:48:31'),(5,3,1,NULL,'2025-05-04 11:48:31','2025-05-04 11:48:31'),(6,4,1,NULL,'2025-05-04 11:49:54','2025-05-04 11:49:54'),(7,4,2,NULL,'2025-05-04 11:49:54','2025-05-04 11:49:54');
/*!40000 ALTER TABLE `feature_plan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `features`
--

DROP TABLE IF EXISTS `features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `features` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `features`
--

LOCK TABLES `features` WRITE;
/*!40000 ALTER TABLE `features` DISABLE KEYS */;
INSERT INTO `features` VALUES (1,'Real time dashboards',NULL,1,'2025-05-03 12:37:27','2025-05-03 12:37:27'),(2,'US IP address',NULL,1,'2025-05-04 11:47:32','2025-05-04 11:47:32');
/*!40000 ALTER TABLE `features` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hosting_platforms`
--

DROP TABLE IF EXISTS `hosting_platforms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hosting_platforms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `requires_tutorial` tinyint(1) NOT NULL DEFAULT '0',
  `tutorial_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `fields` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hosting_platforms_value_unique` (`value`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hosting_platforms`
--

LOCK TABLES `hosting_platforms` WRITE;
/*!40000 ALTER TABLE `hosting_platforms` DISABLE KEYS */;
INSERT INTO `hosting_platforms` VALUES (1,'Namecheap','namecheap',1,1,'#',1,'2025-05-03 12:27:08','2025-05-03 12:27:08','{\"backup_codes\": {\"type\": \"textarea\", \"label\": \"Domain Hosting Platform - Namecheap - Backup Codes\", \"required\": true}, \"platform_login\": {\"type\": \"text\", \"label\": \"Domain Hosting Platform - Login\", \"required\": true}, \"access_tutorial\": {\"type\": \"select\", \"label\": \"Domain Hosting Platform - Namecheap - Access Tutorial\", \"options\": {\"no\": \"No - I haven\'t reviewed the tutorial, and understand that incorrect submission might delay the delivery.\", \"yes\": \"Yes - I reviewed the tutorial and am submitting the access information in requested format.\"}, \"required\": true}, \"platform_password\": {\"type\": \"password\", \"label\": \"Domain Hosting Platform - Password\", \"required\": true}}'),(2,'GoDaddy','godaddy',1,1,'#',2,'2025-05-03 12:27:08','2025-05-03 12:27:08','{\"account_name\": {\"type\": \"text\", \"label\": \"Domain Hosting Platform - Your GoDaddy Account Name (NOT Email)\", \"required\": true}, \"access_tutorial\": {\"type\": \"select\", \"label\": \"Domain Hosting Platform - GoDaddy - Access Tutorial\", \"options\": {\"no\": \"No - I haven\'t reviewed the tutorial, and understand that incorrect submission might delay the delivery.\", \"yes\": \"Yes - I sent DELEGATE ACCESS to hello@premiuminboxes.com and entered my GoDaddy Account Name (NOT email) below.\"}, \"required\": true}}'),(3,'Porkbun','porkbun',1,1,'#',3,'2025-05-03 12:27:08','2025-05-03 12:27:08','{\"platform_login\": {\"type\": \"text\", \"label\": \"Domain Hosting Platform - Login\", \"required\": true}, \"access_tutorial\": {\"type\": \"select\", \"label\": \"Domain Hosting Platform - Porkbun - Access Tutorial\", \"options\": {\"no\": \"No - I haven\'t reviewed the tutorial, and understand that incorrect submission might delay the delivery.\", \"yes\": \"Yes - I disabled all 3 2FAs AND the \'Unrecognized Device 2FA\'\"}, \"required\": true}, \"platform_password\": {\"type\": \"password\", \"label\": \"Domain Hosting Platform - Password\", \"required\": true}}'),(4,'Squarespace','squarespace',1,0,NULL,4,'2025-05-03 12:27:08','2025-05-03 12:27:08','{\"platform_login\": {\"type\": \"text\", \"label\": \"Domain Hosting Platform - Login\", \"required\": true}, \"platform_password\": {\"type\": \"password\", \"label\": \"Domain Hosting Platform - Password\", \"required\": true}}'),(5,'Spaceship','spaceship',1,0,NULL,5,'2025-05-03 12:27:08','2025-05-03 12:27:08','{\"platform_login\": {\"type\": \"text\", \"label\": \"Domain Hosting Platform - Login\", \"required\": true}, \"platform_password\": {\"type\": \"password\", \"label\": \"Domain Hosting Platform - Password\", \"required\": true}}'),(6,'Hostinger','hostinger',1,0,NULL,6,'2025-05-03 12:27:08','2025-05-03 12:27:08','{\"platform_login\": {\"type\": \"text\", \"label\": \"Domain Hosting Platform - Login\", \"required\": true}, \"platform_password\": {\"type\": \"password\", \"label\": \"Domain Hosting Platform - Password\", \"required\": true}}'),(7,'Other','other',1,0,NULL,99,'2025-05-03 12:27:08','2025-05-03 12:27:08','{\"other_platform\": {\"type\": \"text\", \"label\": \"Please specify your hosting platform\", \"required\": true}, \"platform_login\": {\"type\": \"text\", \"label\": \"Domain Hosting Platform - Login\", \"required\": true}, \"platform_password\": {\"type\": \"password\", \"label\": \"Domain Hosting Platform - Password\", \"required\": true}}'),(8,'Cloudflare','cloudflare',1,0,NULL,7,'2025-05-03 12:27:08','2025-05-03 12:27:08','{\"platform_login\": {\"type\": \"text\", \"label\": \"Domain Hosting Platform - Login\", \"required\": true}, \"platform_password\": {\"type\": \"password\", \"label\": \"Domain Hosting Platform - Password\", \"required\": true}}');
/*!40000 ALTER TABLE `hosting_platforms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `chargebee_invoice_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chargebee_customer_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chargebee_subscription_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `plan_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_chargebee_invoice_id_unique` (`chargebee_invoice_id`),
  KEY `invoices_user_id_foreign` (`user_id`),
  KEY `invoices_order_id_foreign` (`order_id`),
  CONSTRAINT `invoices_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (1,'65','16Bl6gUk8eJHH6HRu','16Bl6gUk8dtKM6HHl',8,'2',1,100.00,'paid','2025-05-03 12:56:36','\"{\\\"invoice\\\":{\\\"id\\\":\\\"65\\\",\\\"customer_id\\\":\\\"16Bl6gUk8eJHH6HRu\\\",\\\"subscription_id\\\":\\\"16Bl6gUk8dtKM6HHl\\\",\\\"recurring\\\":true,\\\"status\\\":\\\"paid\\\",\\\"price_type\\\":\\\"tax_exclusive\\\",\\\"date\\\":1746276995,\\\"due_date\\\":1746276995,\\\"net_term_days\\\":0,\\\"exchange_rate\\\":1,\\\"total\\\":10000,\\\"amount_paid\\\":10000,\\\"amount_adjusted\\\":0,\\\"write_off_amount\\\":0,\\\"credits_applied\\\":0,\\\"amount_due\\\":0,\\\"paid_at\\\":1746276996,\\\"updated_at\\\":1746276996,\\\"resource_version\\\":1746276996046,\\\"deleted\\\":false,\\\"object\\\":\\\"invoice\\\",\\\"first_invoice\\\":true,\\\"amount_to_collect\\\":0,\\\"round_off_amount\\\":0,\\\"new_sales_amount\\\":10000,\\\"has_advance_charges\\\":false,\\\"currency_code\\\":\\\"USD\\\",\\\"base_currency_code\\\":\\\"USD\\\",\\\"generated_at\\\":1746276995,\\\"is_gifted\\\":false,\\\"term_finalized\\\":true,\\\"channel\\\":\\\"web\\\",\\\"tax\\\":0,\\\"line_items\\\":[{\\\"id\\\":\\\"li_16Bl6gUk8eJKi6HRy\\\",\\\"date_from\\\":1746276995,\\\"date_to\\\":1748955395,\\\"unit_amount\\\":200,\\\"quantity\\\":50,\\\"amount\\\":10000,\\\"pricing_model\\\":\\\"per_unit\\\",\\\"is_taxed\\\":false,\\\"tax_amount\\\":0,\\\"object\\\":\\\"line_item\\\",\\\"subscription_id\\\":\\\"16Bl6gUk8dtKM6HHl\\\",\\\"customer_id\\\":\\\"16Bl6gUk8eJHH6HRu\\\",\\\"description\\\":\\\"unlimited Monthly Plan\\\",\\\"entity_type\\\":\\\"plan_item_price\\\",\\\"entity_id\\\":\\\"unlimited_1746275957-monthly\\\",\\\"tax_exempt_reason\\\":\\\"tax_not_configured\\\",\\\"discount_amount\\\":0,\\\"item_level_discount_amount\\\":0}],\\\"sub_total\\\":10000,\\\"linked_payments\\\":[{\\\"txn_id\\\":\\\"txn_16Bl6gUk8eJLs6HS2\\\",\\\"applied_amount\\\":10000,\\\"applied_at\\\":1746276996,\\\"txn_status\\\":\\\"success\\\",\\\"txn_date\\\":1746276996,\\\"txn_amount\\\":10000}],\\\"applied_credits\\\":[],\\\"adjustment_credit_notes\\\":[],\\\"issued_credit_notes\\\":[],\\\"linked_orders\\\":[],\\\"dunning_attempts\\\":[],\\\"billing_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"ggg\\\",\\\"line1\\\":\\\"Tt. Vasumweg\\\",\\\"line2\\\":\\\"Amsterdam-Noord\\\",\\\"city\\\":\\\"Amsterdam\\\",\\\"state\\\":\\\"Noord-Holland\\\",\\\"country\\\":\\\"NL\\\",\\\"zip\\\":\\\"1033\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"shipping_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"ggg\\\",\\\"line1\\\":\\\"Tt. Vasumweg\\\",\\\"line2\\\":\\\"Amsterdam-Noord\\\",\\\"city\\\":\\\"Amsterdam\\\",\\\"state\\\":\\\"Noord-Holland\\\",\\\"country\\\":\\\"NL\\\",\\\"zip\\\":\\\"1033\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"site_details_at_creation\\\":{\\\"timezone\\\":\\\"Asia\\\\/Dubai\\\"}}}\"','2025-05-03 12:56:37','2025-05-03 12:56:42'),(2,'66','169zeEUk8g8w269fm','169zeEUk8g2Qn69cA',8,'2',2,300.00,'paid','2025-05-03 13:03:52','\"{\\\"invoice\\\":{\\\"id\\\":\\\"66\\\",\\\"customer_id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"subscription_id\\\":\\\"169zeEUk8g2Qn69cA\\\",\\\"recurring\\\":true,\\\"status\\\":\\\"paid\\\",\\\"price_type\\\":\\\"tax_exclusive\\\",\\\"date\\\":1746277432,\\\"due_date\\\":1746277432,\\\"net_term_days\\\":0,\\\"exchange_rate\\\":1,\\\"total\\\":30000,\\\"amount_paid\\\":30000,\\\"amount_adjusted\\\":0,\\\"write_off_amount\\\":0,\\\"credits_applied\\\":0,\\\"amount_due\\\":0,\\\"paid_at\\\":1746277432,\\\"updated_at\\\":1746277432,\\\"resource_version\\\":1746277432853,\\\"deleted\\\":false,\\\"object\\\":\\\"invoice\\\",\\\"first_invoice\\\":true,\\\"amount_to_collect\\\":0,\\\"round_off_amount\\\":0,\\\"new_sales_amount\\\":30000,\\\"has_advance_charges\\\":false,\\\"currency_code\\\":\\\"USD\\\",\\\"base_currency_code\\\":\\\"USD\\\",\\\"generated_at\\\":1746277432,\\\"is_gifted\\\":false,\\\"term_finalized\\\":true,\\\"channel\\\":\\\"web\\\",\\\"tax\\\":0,\\\"line_items\\\":[{\\\"id\\\":\\\"li_169zeEUk8g8yT69fp\\\",\\\"date_from\\\":1746277432,\\\"date_to\\\":1748955832,\\\"unit_amount\\\":200,\\\"quantity\\\":150,\\\"amount\\\":30000,\\\"pricing_model\\\":\\\"per_unit\\\",\\\"is_taxed\\\":false,\\\"tax_amount\\\":0,\\\"object\\\":\\\"line_item\\\",\\\"subscription_id\\\":\\\"169zeEUk8g2Qn69cA\\\",\\\"customer_id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"description\\\":\\\"unlimited Monthly Plan\\\",\\\"entity_type\\\":\\\"plan_item_price\\\",\\\"entity_id\\\":\\\"unlimited_1746275957-monthly\\\",\\\"tax_exempt_reason\\\":\\\"tax_not_configured\\\",\\\"discount_amount\\\":0,\\\"item_level_discount_amount\\\":0}],\\\"sub_total\\\":30000,\\\"linked_payments\\\":[{\\\"txn_id\\\":\\\"txn_169zeEUk8g8zJ69fq\\\",\\\"applied_amount\\\":30000,\\\"applied_at\\\":1746277432,\\\"txn_status\\\":\\\"success\\\",\\\"txn_date\\\":1746277432,\\\"txn_amount\\\":30000}],\\\"applied_credits\\\":[],\\\"adjustment_credit_notes\\\":[],\\\"issued_credit_notes\\\":[],\\\"linked_orders\\\":[],\\\"dunning_attempts\\\":[],\\\"billing_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"shipping_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"site_details_at_creation\\\":{\\\"timezone\\\":\\\"Asia\\\\/Dubai\\\"}}}\"','2025-05-03 13:03:53','2025-05-03 13:03:59'),(3,'67','169zeEUk8g8w269fm','16Bl6gUk8mHrD6KpU',8,'2',2,400.00,'paid','2025-05-03 13:28:31','\"{\\\"invoice\\\":{\\\"id\\\":\\\"67\\\",\\\"customer_id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"subscription_id\\\":\\\"16Bl6gUk8mHrD6KpU\\\",\\\"recurring\\\":true,\\\"status\\\":\\\"paid\\\",\\\"price_type\\\":\\\"tax_exclusive\\\",\\\"date\\\":1746278911,\\\"due_date\\\":1746278911,\\\"net_term_days\\\":0,\\\"exchange_rate\\\":1,\\\"total\\\":40000,\\\"amount_paid\\\":40000,\\\"amount_adjusted\\\":0,\\\"write_off_amount\\\":0,\\\"credits_applied\\\":0,\\\"amount_due\\\":0,\\\"paid_at\\\":1746278911,\\\"updated_at\\\":1746278911,\\\"resource_version\\\":1746278911193,\\\"deleted\\\":false,\\\"object\\\":\\\"invoice\\\",\\\"first_invoice\\\":true,\\\"amount_to_collect\\\":0,\\\"round_off_amount\\\":0,\\\"new_sales_amount\\\":40000,\\\"has_advance_charges\\\":false,\\\"currency_code\\\":\\\"USD\\\",\\\"base_currency_code\\\":\\\"USD\\\",\\\"generated_at\\\":1746278911,\\\"is_gifted\\\":false,\\\"term_finalized\\\":true,\\\"channel\\\":\\\"web\\\",\\\"tax\\\":0,\\\"line_items\\\":[{\\\"id\\\":\\\"li_169zeEUk8mLYf6CMe\\\",\\\"date_from\\\":1746278911,\\\"date_to\\\":1748957311,\\\"unit_amount\\\":200,\\\"quantity\\\":200,\\\"amount\\\":40000,\\\"pricing_model\\\":\\\"per_unit\\\",\\\"is_taxed\\\":false,\\\"tax_amount\\\":0,\\\"object\\\":\\\"line_item\\\",\\\"subscription_id\\\":\\\"16Bl6gUk8mHrD6KpU\\\",\\\"customer_id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"description\\\":\\\"unlimited Monthly Price\\\",\\\"entity_type\\\":\\\"plan_item_price\\\",\\\"entity_id\\\":\\\"unlimited_1746275957-monthly\\\",\\\"tax_exempt_reason\\\":\\\"tax_not_configured\\\",\\\"discount_amount\\\":0,\\\"item_level_discount_amount\\\":0}],\\\"sub_total\\\":40000,\\\"linked_payments\\\":[{\\\"txn_id\\\":\\\"txn_169zeEUk8mLZU6CMg\\\",\\\"applied_amount\\\":40000,\\\"applied_at\\\":1746278911,\\\"txn_status\\\":\\\"success\\\",\\\"txn_date\\\":1746278911,\\\"txn_amount\\\":40000}],\\\"applied_credits\\\":[],\\\"adjustment_credit_notes\\\":[],\\\"issued_credit_notes\\\":[],\\\"linked_orders\\\":[],\\\"dunning_attempts\\\":[],\\\"billing_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"shipping_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"site_details_at_creation\\\":{\\\"timezone\\\":\\\"Asia\\\\/Dubai\\\"}}}\"','2025-05-03 13:28:32','2025-05-03 13:28:37'),(4,'68','AzyfgQUkEIRL99Rlp','16Bl6gUkEI7bY9bdr',12,'3',4,350.00,'paid','2025-05-04 12:07:21','\"{\\\"invoice\\\":{\\\"id\\\":\\\"68\\\",\\\"customer_id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"subscription_id\\\":\\\"16Bl6gUkEI7bY9bdr\\\",\\\"recurring\\\":true,\\\"status\\\":\\\"paid\\\",\\\"price_type\\\":\\\"tax_exclusive\\\",\\\"date\\\":1746360441,\\\"due_date\\\":1746360441,\\\"net_term_days\\\":0,\\\"exchange_rate\\\":1,\\\"total\\\":35000,\\\"amount_paid\\\":35000,\\\"amount_adjusted\\\":0,\\\"write_off_amount\\\":0,\\\"credits_applied\\\":0,\\\"amount_due\\\":0,\\\"paid_at\\\":1746360441,\\\"updated_at\\\":1746360441,\\\"resource_version\\\":1746360441853,\\\"deleted\\\":false,\\\"object\\\":\\\"invoice\\\",\\\"first_invoice\\\":true,\\\"amount_to_collect\\\":0,\\\"round_off_amount\\\":0,\\\"new_sales_amount\\\":35000,\\\"has_advance_charges\\\":false,\\\"currency_code\\\":\\\"USD\\\",\\\"base_currency_code\\\":\\\"USD\\\",\\\"generated_at\\\":1746360441,\\\"is_gifted\\\":false,\\\"term_finalized\\\":true,\\\"channel\\\":\\\"web\\\",\\\"tax\\\":0,\\\"line_items\\\":[{\\\"id\\\":\\\"li_AzyfgQUkEIROc9Rlu\\\",\\\"date_from\\\":1746360441,\\\"date_to\\\":1749038841,\\\"unit_amount\\\":350,\\\"quantity\\\":100,\\\"amount\\\":35000,\\\"pricing_model\\\":\\\"per_unit\\\",\\\"is_taxed\\\":false,\\\"tax_amount\\\":0,\\\"object\\\":\\\"line_item\\\",\\\"subscription_id\\\":\\\"16Bl6gUkEI7bY9bdr\\\",\\\"customer_id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"description\\\":\\\"start up plan Monthly Plan\\\",\\\"entity_type\\\":\\\"plan_item_price\\\",\\\"entity_id\\\":\\\"start_up_plan_1746359310-monthly\\\",\\\"tax_exempt_reason\\\":\\\"tax_not_configured\\\",\\\"discount_amount\\\":0,\\\"item_level_discount_amount\\\":0}],\\\"sub_total\\\":35000,\\\"linked_payments\\\":[{\\\"txn_id\\\":\\\"txn_AzyfgQUkEIRPu9Rlv\\\",\\\"applied_amount\\\":35000,\\\"applied_at\\\":1746360441,\\\"txn_status\\\":\\\"success\\\",\\\"txn_date\\\":1746360441,\\\"txn_amount\\\":35000}],\\\"applied_credits\\\":[],\\\"adjustment_credit_notes\\\":[],\\\"issued_credit_notes\\\":[],\\\"linked_orders\\\":[],\\\"dunning_attempts\\\":[],\\\"billing_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"shipping_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"site_details_at_creation\\\":{\\\"timezone\\\":\\\"Asia\\\\/Dubai\\\"}}}\"','2025-05-04 12:07:23','2025-05-04 12:07:28'),(5,'69','AzyfgQUkEIRL99Rlp','169zeEUkEMM7a9VE2',12,'3',4,300.00,'paid','2025-05-04 12:23:17','\"{\\\"invoice\\\":{\\\"id\\\":\\\"69\\\",\\\"customer_id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"subscription_id\\\":\\\"169zeEUkEMM7a9VE2\\\",\\\"recurring\\\":true,\\\"status\\\":\\\"paid\\\",\\\"price_type\\\":\\\"tax_exclusive\\\",\\\"date\\\":1746361397,\\\"due_date\\\":1746361397,\\\"net_term_days\\\":0,\\\"exchange_rate\\\":1,\\\"total\\\":30000,\\\"amount_paid\\\":30000,\\\"amount_adjusted\\\":0,\\\"write_off_amount\\\":0,\\\"credits_applied\\\":0,\\\"amount_due\\\":0,\\\"paid_at\\\":1746361397,\\\"updated_at\\\":1746361398,\\\"resource_version\\\":1746361398018,\\\"deleted\\\":false,\\\"object\\\":\\\"invoice\\\",\\\"first_invoice\\\":true,\\\"amount_to_collect\\\":0,\\\"round_off_amount\\\":0,\\\"new_sales_amount\\\":30000,\\\"has_advance_charges\\\":false,\\\"currency_code\\\":\\\"USD\\\",\\\"base_currency_code\\\":\\\"USD\\\",\\\"generated_at\\\":1746361397,\\\"is_gifted\\\":false,\\\"term_finalized\\\":true,\\\"channel\\\":\\\"web\\\",\\\"tax\\\":0,\\\"line_items\\\":[{\\\"id\\\":\\\"li_169zeEUkEMS9I9VGN\\\",\\\"date_from\\\":1746361397,\\\"date_to\\\":1749039797,\\\"unit_amount\\\":200,\\\"quantity\\\":150,\\\"amount\\\":30000,\\\"pricing_model\\\":\\\"per_unit\\\",\\\"is_taxed\\\":false,\\\"tax_amount\\\":0,\\\"object\\\":\\\"line_item\\\",\\\"subscription_id\\\":\\\"169zeEUkEMM7a9VE2\\\",\\\"customer_id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"description\\\":\\\"enterprise plan Monthly Plan\\\",\\\"entity_type\\\":\\\"plan_item_price\\\",\\\"entity_id\\\":\\\"enterprise_plan_1746359393-monthly\\\",\\\"tax_exempt_reason\\\":\\\"tax_not_configured\\\",\\\"discount_amount\\\":0,\\\"item_level_discount_amount\\\":0}],\\\"sub_total\\\":30000,\\\"linked_payments\\\":[{\\\"txn_id\\\":\\\"txn_169zeEUkEMSA99VGO\\\",\\\"applied_amount\\\":30000,\\\"applied_at\\\":1746361397,\\\"txn_status\\\":\\\"success\\\",\\\"txn_date\\\":1746361397,\\\"txn_amount\\\":30000}],\\\"applied_credits\\\":[],\\\"adjustment_credit_notes\\\":[],\\\"issued_credit_notes\\\":[],\\\"linked_orders\\\":[],\\\"dunning_attempts\\\":[],\\\"billing_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"shipping_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"site_details_at_creation\\\":{\\\"timezone\\\":\\\"Asia\\\\/Dubai\\\"}}}\"','2025-05-04 12:23:19','2025-05-04 12:23:24');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `action_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `performed_by` bigint unsigned DEFAULT NULL,
  `performed_on_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `performed_on_id` bigint unsigned NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `logs_performed_on_type_performed_on_id_index` (`performed_on_type`,`performed_on_id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
INSERT INTO `logs` VALUES (1,'customer-order-created',8,'App\\Models\\Order',1,'Order created successfully: 1','{\"amount\": 100, \"status\": \"paid\", \"paid_at\": \"2025-05-03 12:56:36\", \"plan_id\": 2, \"user_id\": 8, \"order_id\": 1, \"chargebee_invoice_id\": \"65\", \"chargebee_subscription_id\": \"16Bl6gUk8dtKM6HHl\"}','2025-05-03 12:56:37','2025-05-03 12:56:37'),(2,'customer-subscription-created',8,'App\\Models\\Subscription',1,'Subscription created successfully: 1','{\"amount\": 100, \"status\": \"paid\", \"paid_at\": \"2025-05-03 12:56:36\", \"plan_id\": 2, \"user_id\": 8, \"order_id\": 1, \"chargebee_invoice_id\": \"65\", \"chargebee_subscription_id\": \"16Bl6gUk8dtKM6HHl\"}','2025-05-03 12:56:37','2025-05-03 12:56:37'),(3,'customer-invoice-processed',8,'App\\Models\\Invoice',1,'Invoice created successfully: 1','{\"amount\": 100, \"status\": \"paid\", \"paid_at\": \"2025-05-03 12:56:36\", \"plan_id\": 2, \"user_id\": 8, \"order_id\": 1, \"chargebee_invoice_id\": \"65\", \"chargebee_subscription_id\": \"16Bl6gUk8dtKM6HHl\"}','2025-05-03 12:56:37','2025-05-03 12:56:37'),(4,'customer-invoice-processed',8,'App\\Models\\Invoice',1,'Invoice processed successfully: 1','{\"amount\": 100, \"status\": \"paid\", \"paid_at\": \"2025-05-03 12:56:36\", \"user_id\": 8, \"invoice_id\": 1, \"chargebee_invoice_id\": \"65\"}','2025-05-03 12:56:45','2025-05-03 12:56:45'),(5,'customer-subscription-cancelled',8,'App\\Models\\Subscription',1,'Subscription cancelled successfully: 1','{\"status\": \"cancelled\", \"user_id\": 8, \"subscription_id\": 1}','2025-05-03 13:01:06','2025-05-03 13:01:06'),(6,'customer-order-created',8,'App\\Models\\Order',2,'Order created successfully: 2','{\"amount\": 300, \"status\": \"paid\", \"paid_at\": \"2025-05-03 13:03:52\", \"plan_id\": 2, \"user_id\": 8, \"order_id\": 2, \"chargebee_invoice_id\": \"66\", \"chargebee_subscription_id\": \"169zeEUk8g2Qn69cA\"}','2025-05-03 13:03:53','2025-05-03 13:03:53'),(7,'customer-subscription-created',8,'App\\Models\\Subscription',2,'Subscription created successfully: 2','{\"amount\": 300, \"status\": \"paid\", \"paid_at\": \"2025-05-03 13:03:52\", \"plan_id\": 2, \"user_id\": 8, \"order_id\": 2, \"chargebee_invoice_id\": \"66\", \"chargebee_subscription_id\": \"169zeEUk8g2Qn69cA\"}','2025-05-03 13:03:53','2025-05-03 13:03:53'),(8,'customer-invoice-processed',8,'App\\Models\\Invoice',2,'Invoice created successfully: 2','{\"amount\": 300, \"status\": \"paid\", \"paid_at\": \"2025-05-03 13:03:52\", \"plan_id\": 2, \"user_id\": 8, \"order_id\": 2, \"chargebee_invoice_id\": \"66\", \"chargebee_subscription_id\": \"169zeEUk8g2Qn69cA\"}','2025-05-03 13:03:53','2025-05-03 13:03:53'),(9,'customer-invoice-processed',8,'App\\Models\\Invoice',2,'Invoice processed successfully: 2','{\"amount\": 300, \"status\": \"paid\", \"paid_at\": \"2025-05-03 13:03:52\", \"user_id\": 8, \"invoice_id\": 2, \"chargebee_invoice_id\": \"66\"}','2025-05-03 13:04:02','2025-05-03 13:04:02'),(10,'contractor-order-status-update',9,'App\\Models\\Order',2,'Order status updated : 2','{\"order_id\": 2, \"new_status\": \"Reject\", \"old_status\": \"pending\", \"updated_by\": 9}','2025-05-03 13:06:57','2025-05-03 13:06:57'),(11,'customer-order-update',8,'App\\Models\\Order',2,'Order updated: 2','{\"domains\": \"example.com,google.com,facebook.com,twitter.com,linkedin.com,amazon.com,apple.com,microsoft.com,netflix.com,youtube.com,instagram.com,whatsapp.com,pinterest.com,reddit.com,github.com,stackoverflow.com,quora.com,dropbox.com,adobe.com,wordpress.com,shopify.com,paypal.com,tiktok.com,airbnb.com,uber.com,zoom.us,slack.com,weebly.com,wixw.com,blogger.com,cnn.com,bbc.com,nytimes.com,forbes.com,bloomberg.com,foxnews.com,msn.com,yahoo.com,outlook.com,live.com,office.com,booking.com,tripadvisor.com,expedia.com,indeed.com,glassdoor.com,medium.com,kickstarter.com,coursera.org,udemy.com\", \"plan_id\": \"2\", \"user_id\": \"8\", \"bison_url\": null, \"last_name\": \"Prohaska\", \"first_name\": \"Josue\", \"backup_codes\": null, \"total_inboxes\": 150, \"forwarding_url\": \"Amina94 update\", \"other_platform\": null, \"platform_login\": \"77401 Cummings Squares\", \"additional_info\": \"Delectus aliquam facere dolore.\", \"bison_workspace\": null, \"sequencer_login\": \"your.email+fakedata76119@gmail.com\", \"hosting_platform\": \"squarespace\", \"persona_password\": \"6ZYRSZmi9Xd2FR0\", \"prefix_variant_1\": \"Magnam et adipisci.\", \"prefix_variant_2\": \"Sapiente omnis numqua\", \"sending_platform\": \"smartlead\", \"platform_password\": \"6ZYRSZmi9Xd2FR0\", \"inboxes_per_domain\": \"3\", \"master_inbox_email\": \"your.email+fakedata13343@gmail.com\", \"sequencer_password\": \"6ZYRSZmi9Xd2FR0\", \"profile_picture_link\": null, \"email_persona_password\": \"6ZYRSZmi9Xd2FR0\", \"email_persona_picture_link\": null}','2025-05-03 13:12:23','2025-05-03 13:12:23'),(12,'contractor-order-status-update',9,'App\\Models\\Order',2,'Order status updated : 2','{\"order_id\": 2, \"new_status\": \"Completed\", \"old_status\": \"In-approval\", \"updated_by\": 9}','2025-05-03 13:17:09','2025-05-03 13:17:09'),(13,'customer-order-created',8,'App\\Models\\Order',3,'Order created successfully: 3','{\"amount\": 400, \"status\": \"paid\", \"paid_at\": \"2025-05-03 13:28:31\", \"plan_id\": 2, \"user_id\": 8, \"order_id\": 3, \"chargebee_invoice_id\": \"67\", \"chargebee_subscription_id\": \"16Bl6gUk8mHrD6KpU\"}','2025-05-03 13:28:32','2025-05-03 13:28:32'),(14,'customer-subscription-created',8,'App\\Models\\Subscription',3,'Subscription created successfully: 3','{\"amount\": 400, \"status\": \"paid\", \"paid_at\": \"2025-05-03 13:28:31\", \"plan_id\": 2, \"user_id\": 8, \"order_id\": 3, \"chargebee_invoice_id\": \"67\", \"chargebee_subscription_id\": \"16Bl6gUk8mHrD6KpU\"}','2025-05-03 13:28:32','2025-05-03 13:28:32'),(15,'customer-invoice-processed',8,'App\\Models\\Invoice',3,'Invoice created successfully: 3','{\"amount\": 400, \"status\": \"paid\", \"paid_at\": \"2025-05-03 13:28:31\", \"plan_id\": 2, \"user_id\": 8, \"order_id\": 3, \"chargebee_invoice_id\": \"67\", \"chargebee_subscription_id\": \"16Bl6gUk8mHrD6KpU\"}','2025-05-03 13:28:32','2025-05-03 13:28:32'),(16,'customer-invoice-processed',8,'App\\Models\\Invoice',3,'Invoice processed successfully: 3','{\"amount\": 400, \"status\": \"paid\", \"paid_at\": \"2025-05-03 13:28:31\", \"user_id\": 8, \"invoice_id\": 3, \"chargebee_invoice_id\": \"67\"}','2025-05-03 13:28:40','2025-05-03 13:28:40'),(17,'customer-invoice-view',8,'App\\Models\\Invoice',2,'Viewed invoice: 66','{\"amount\": \"300.00\", \"status\": \"paid\", \"order_id\": 2, \"view_date\": \"2025-05-03 13:36:20\", \"invoice_id\": \"66\", \"ip_address\": \"119.73.100.244\", \"chargebee_invoice_id\": \"66\"}','2025-05-03 13:36:20','2025-05-03 13:36:20'),(18,'customer-order-created',12,'App\\Models\\Order',4,'Order created successfully: 4','{\"amount\": 350, \"status\": \"paid\", \"paid_at\": \"2025-05-04 12:07:21\", \"plan_id\": 3, \"user_id\": 12, \"order_id\": 4, \"chargebee_invoice_id\": \"68\", \"chargebee_subscription_id\": \"16Bl6gUkEI7bY9bdr\"}','2025-05-04 12:07:23','2025-05-04 12:07:23'),(19,'customer-subscription-created',12,'App\\Models\\Subscription',4,'Subscription created successfully: 4','{\"amount\": 350, \"status\": \"paid\", \"paid_at\": \"2025-05-04 12:07:21\", \"plan_id\": 3, \"user_id\": 12, \"order_id\": 4, \"chargebee_invoice_id\": \"68\", \"chargebee_subscription_id\": \"16Bl6gUkEI7bY9bdr\"}','2025-05-04 12:07:23','2025-05-04 12:07:23'),(20,'customer-invoice-processed',12,'App\\Models\\Invoice',4,'Invoice created successfully: 4','{\"amount\": 350, \"status\": \"paid\", \"paid_at\": \"2025-05-04 12:07:21\", \"plan_id\": 3, \"user_id\": 12, \"order_id\": 4, \"chargebee_invoice_id\": \"68\", \"chargebee_subscription_id\": \"16Bl6gUkEI7bY9bdr\"}','2025-05-04 12:07:23','2025-05-04 12:07:23'),(21,'customer-invoice-processed',12,'App\\Models\\Invoice',4,'Invoice processed successfully: 4','{\"amount\": 350, \"status\": \"paid\", \"paid_at\": \"2025-05-04 12:07:21\", \"user_id\": 12, \"invoice_id\": 4, \"chargebee_invoice_id\": \"68\"}','2025-05-04 12:07:31','2025-05-04 12:07:31'),(22,'customer-invoice-view',12,'App\\Models\\Invoice',4,'Viewed invoice: 68','{\"amount\": \"350.00\", \"status\": \"paid\", \"order_id\": 4, \"view_date\": \"2025-05-04 12:09:17\", \"invoice_id\": \"68\", \"ip_address\": \"182.183.8.156\", \"chargebee_invoice_id\": \"68\"}','2025-05-04 12:09:17','2025-05-04 12:09:17'),(23,'customer-invoice-download',12,'App\\Models\\Invoice',4,'Downloaded invoice: 68','{\"amount\": \"350.00\", \"status\": \"paid\", \"order_id\": 4, \"invoice_id\": \"68\", \"ip_address\": \"182.183.8.156\", \"download_date\": \"2025-05-04 12:09:22\", \"chargebee_invoice_id\": \"68\"}','2025-05-04 12:09:22','2025-05-04 12:09:22'),(24,'contractor-order-status-update',13,'App\\Models\\Order',4,'Order status updated : 4','{\"order_id\": 4, \"new_status\": \"Reject\", \"old_status\": \"pending\", \"updated_by\": 13}','2025-05-04 12:13:53','2025-05-04 12:13:53'),(25,'customer-order-update',12,'App\\Models\\Order',4,'Order updated: 4','{\"domains\": \"izGrowthHub.com, StartupNest.io, MarketPulse360.com, FinSavvyPro.com, GlobalTradeDesk.com, CapitalWise.net, ScaleStrategix.com, BizElevateNow.com, LedgerLogic.com, BrandCrafters.org,TechNexusLab.com, CodeHive.dev, CyberPulseTech.com, AIForgeX.com, DataCloudly.com, QuantumOS.io, SmartNetWorks.com, AppSprint.io, DevMasterZone.com, NextGenSoft.dev,VitalCoreHealth.com, FitFusionLife.com, MindBodyAnchor.com, PureWellLiving.com, HolistiqCare.com, ThriveClinic360.com, HealThriveHub.com, CalmWellCenter.com, PulseRxOnline.com, MedBridgeNow.com,LearnSphere.io, BrightMindAcademy.com, EduBridge360.com, SkillStackers.com, StudyNest.org, NextGenTutors.com, MathMastersZone.com, LangFluently.com, CodeCraftEdu.com, ThinkGrowSchool.com,ShopNovaOnline.com, TrendMart360.com, ClickCartDepot.com, SwiftShoppers.com, UrbanDealsHub.com, EcoBuySmart.com, LuxeGoodsBox.com, SnapAndShop.io, GadgetHiveStore.com, FastTrackShop.com,PixelWaveStudio.com, CreativoSpace.com, MediaForgeLab.com, SnapVerse.io, ArtNestGallery.com, VisionQuill.com, AudioInkStudio.com, FrameMuseMedia.com, ScriptedHive.com, FilmTrekStudio.com,ExploreNomadly.com, ChillNestRetreats.com, DailyZenGuide.com, WanderBloomTrips.com, LuxeTrailAdventures.com, ModernLivingVibe.com, UrbanNestLife.com, ZenLifestyle360.com, WildPathJournals.com, NomadFuelHub.com,EcoFutureNow.com, GreenHavenLife.com, ReuseRevive.com, ZeroWasteGen.com, EcoNestCollective.com, PlanetWiseHub.com, GreenSparkTech.com, SolarBrightly.com, EarthNovaNow.com, BioCycleWorks.com,SavorNestKitchen.com, BrewHiveCafe.com, TasteMingle.com, EpicureanRoots.com, SpoonCraftBites.com, WhiskFusion.com, FreshForkMarket.com, UrbanGroveEats.com, CraveNestCatering.com, ForkAndFarm.org,MindFuelDaily.com, LifeUpgradeLab.com, InnerDriveCoaching.com, GrowthMindedYou.com, ZenMasteryPath.com, ClarityStepsNow.com, GoalForgeStudio.com, SelfLift360.com, PowerWithinNet.com, TrueYouBlueprint.com\", \"plan_id\": \"3\", \"user_id\": \"12\", \"bison_url\": null, \"last_name\": \"schibbs\", \"first_name\": \"greg\", \"backup_codes\": \"1214352134\\r\\n321323123\\r\\n3313123\\r\\n32132\", \"total_inboxes\": 100, \"forwarding_url\": \"https://pmybals.pmyp.gov.pk/BankForm/newApplicantForm?CNIC=61101-0317746-7&CIssueDate=03-25-2022&Tier=Tier1\", \"other_platform\": null, \"platform_login\": \"app.projectinbox@gmail.com\", \"additional_info\": \"gyfgwyege hfheyf\", \"bison_workspace\": null, \"sequencer_login\": \"abc@gmail.com\", \"hosting_platform\": \"namecheap\", \"persona_password\": \"12345678\", \"prefix_variant_1\": \"hamza@\", \"prefix_variant_2\": \"umer@\", \"sending_platform\": \"prospi\", \"platform_password\": \"Admin123#\", \"inboxes_per_domain\": \"1\", \"master_inbox_email\": null, \"sequencer_password\": \"12345678\", \"profile_picture_link\": null, \"email_persona_password\": \"12345678\", \"email_persona_picture_link\": null}','2025-05-04 12:15:42','2025-05-04 12:15:42'),(26,'contractor-order-status-update',13,'App\\Models\\Order',4,'Order status updated : 4','{\"order_id\": 4, \"new_status\": \"In-Progress\", \"old_status\": \"In-approval\", \"updated_by\": 13}','2025-05-04 12:16:38','2025-05-04 12:16:38'),(27,'contractor-order-status-update',13,'App\\Models\\Order',4,'Order status updated : 4','{\"order_id\": 4, \"new_status\": \"Completed\", \"old_status\": \"In-Progress\", \"updated_by\": 13}','2025-05-04 12:19:44','2025-05-04 12:19:44'),(28,'customer-order-created',12,'App\\Models\\Order',5,'Order created successfully: 5','{\"amount\": 300, \"status\": \"paid\", \"paid_at\": \"2025-05-04 12:23:17\", \"plan_id\": 4, \"user_id\": 12, \"order_id\": 5, \"chargebee_invoice_id\": \"69\", \"chargebee_subscription_id\": \"169zeEUkEMM7a9VE2\"}','2025-05-04 12:23:19','2025-05-04 12:23:19'),(29,'customer-subscription-created',12,'App\\Models\\Subscription',5,'Subscription created successfully: 5','{\"amount\": 300, \"status\": \"paid\", \"paid_at\": \"2025-05-04 12:23:17\", \"plan_id\": 4, \"user_id\": 12, \"order_id\": 5, \"chargebee_invoice_id\": \"69\", \"chargebee_subscription_id\": \"169zeEUkEMM7a9VE2\"}','2025-05-04 12:23:19','2025-05-04 12:23:19'),(30,'customer-invoice-processed',12,'App\\Models\\Invoice',5,'Invoice created successfully: 5','{\"amount\": 300, \"status\": \"paid\", \"paid_at\": \"2025-05-04 12:23:17\", \"plan_id\": 4, \"user_id\": 12, \"order_id\": 5, \"chargebee_invoice_id\": \"69\", \"chargebee_subscription_id\": \"169zeEUkEMM7a9VE2\"}','2025-05-04 12:23:19','2025-05-04 12:23:19'),(31,'customer-invoice-processed',12,'App\\Models\\Invoice',5,'Invoice processed successfully: 5','{\"amount\": 300, \"status\": \"paid\", \"paid_at\": \"2025-05-04 12:23:17\", \"user_id\": 12, \"invoice_id\": 5, \"chargebee_invoice_id\": \"69\"}','2025-05-04 12:23:28','2025-05-04 12:23:28');
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2014_10_12_000000_create_users_table',1),(2,'2014_10_12_100000_create_password_reset_tokens_table',1),(3,'2019_08_19_000000_create_failed_jobs_table',1),(4,'2019_12_14_000001_create_personal_access_tokens_table',1),(5,'2025_04_05_114430_add_phone_to_users_table',1),(6,'2025_04_07_100846_add_status_to_users_table',1),(7,'2025_04_07_103958_create_roles_table',1),(8,'2025_04_15_060047_create_plans_table',1),(9,'2025_04_15_082937_add_billing_address_to_users_table',1),(10,'2025_04_16_043818_create_features_table',1),(11,'2025_04_16_043903_create_feature_plan_table',1),(12,'2025_04_16_044359_add_inbox_limits_to_plans_table',1),(13,'2025_04_16_074922_add_chargebee_plan_id_to_plans_table',1),(14,'2025_04_17_100413_create_orders_table',1),(15,'2025_04_17_100545_create_subscriptions_table',1),(16,'2025_04_17_101545_add_subscription_fields_to_users_table',1),(17,'2025_04_17_105434_create_invoices_table',1),(18,'2025_04_17_110956_add_meta_to_orders_table',1),(19,'2025_04_17_111120_add_meta_to_subscriptions_table',1),(20,'2025_04_17_114617_add_chargebee_columns_to_orders_table',1),(21,'2025_04_17_114955_add_chargebee_columns_to_subscriptions_table',1),(22,'2025_04_18_050822_add_plan_id_to_orders_table',1),(23,'2025_04_18_051810_add_status_manage_by_admin_to_orders_table',1),(24,'2025_04_18_052003_add_domain_forwarding_url_to_users_table',1),(25,'2025_04_18_091255_create_reorder_infos_table',1),(26,'2025_04_18_091931_add_order_id_to_reorder_infos_table',1),(27,'2025_04_19_051433_create_hosting_platforms_table',1),(28,'2025_04_21_070115_add_reason_to_subscriptions_table',1),(29,'2025_04_21_070639_add_cancellation_at_to_subscriptions_table',1),(30,'2025_04_21_083343_create_logs_table',1),(31,'2025_04_22_102306_create_jobs_table',1),(32,'2025_04_23_063753_add_other_platform_to_reorder_infos_table',1),(33,'2025_04_23_115038_create_order_emails_table',1),(34,'2025_04_28_000000_create_sending_platforms_table',1),(35,'2025_04_28_073519_add_tutorial_section_to_reorder_infos_table',1),(36,'2025_04_28_084816_add_fields_to_hosting_platforms_table',1),(37,'2025_04_29_065807_add_sending_platform_fields_to_reorder_infos_table',1),(38,'2025_04_30_061605_add_billing_dates_to_subscriptions_table',1),(39,'2025_05_01_104533_rename_roles_table',1),(40,'2025_05_01_110034_create_permission_tables',1),(41,'2025_05_02_065843_add_reason_to_orders',1),(42,'2025_05_02_094721_sidebar_navigations',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1),(2,'App\\Models\\User',10),(3,'App\\Models\\User',11),(4,'App\\Models\\User',14);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_emails`
--

DROP TABLE IF EXISTS `order_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_emails` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_emails_order_id_foreign` (`order_id`),
  KEY `order_emails_user_id_foreign` (`user_id`),
  CONSTRAINT `order_emails_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_emails_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_emails`
--

LOCK TABLES `order_emails` WRITE;
/*!40000 ALTER TABLE `order_emails` DISABLE KEYS */;
INSERT INTO `order_emails` VALUES (1,2,8,'e@c.com','contractor2@email.com','Admin123#',NULL,'2025-05-03 13:14:21','2025-05-03 13:14:21'),(3,4,12,'abc','abc@gmail.com','12345678',NULL,'2025-05-04 12:18:06','2025-05-04 12:18:06'),(4,4,12,'acd','acd@gmail.com','12345678',NULL,'2025-05-04 12:18:06','2025-05-04 12:18:06');
/*!40000 ALTER TABLE `order_emails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `plan_id` bigint unsigned DEFAULT NULL,
  `chargebee_invoice_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `status_manage_by_admin` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `meta` json DEFAULT NULL,
  `chargebee_subscription_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chargebee_customer_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orders_user_id_foreign` (`user_id`),
  KEY `orders_plan_id_foreign` (`plan_id`),
  CONSTRAINT `orders_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,8,NULL,'65',100.00,'paid','cancelled','\"{\\\"invoice\\\":{\\\"id\\\":\\\"65\\\",\\\"customer_id\\\":\\\"16Bl6gUk8eJHH6HRu\\\",\\\"subscription_id\\\":\\\"16Bl6gUk8dtKM6HHl\\\",\\\"recurring\\\":true,\\\"status\\\":\\\"paid\\\",\\\"price_type\\\":\\\"tax_exclusive\\\",\\\"date\\\":1746276995,\\\"due_date\\\":1746276995,\\\"net_term_days\\\":0,\\\"exchange_rate\\\":1,\\\"total\\\":10000,\\\"amount_paid\\\":10000,\\\"amount_adjusted\\\":0,\\\"write_off_amount\\\":0,\\\"credits_applied\\\":0,\\\"amount_due\\\":0,\\\"paid_at\\\":1746276996,\\\"updated_at\\\":1746276996,\\\"resource_version\\\":1746276996046,\\\"deleted\\\":false,\\\"object\\\":\\\"invoice\\\",\\\"first_invoice\\\":true,\\\"amount_to_collect\\\":0,\\\"round_off_amount\\\":0,\\\"new_sales_amount\\\":10000,\\\"has_advance_charges\\\":false,\\\"currency_code\\\":\\\"USD\\\",\\\"base_currency_code\\\":\\\"USD\\\",\\\"generated_at\\\":1746276995,\\\"is_gifted\\\":false,\\\"term_finalized\\\":true,\\\"channel\\\":\\\"web\\\",\\\"tax\\\":0,\\\"line_items\\\":[{\\\"id\\\":\\\"li_16Bl6gUk8eJKi6HRy\\\",\\\"date_from\\\":1746276995,\\\"date_to\\\":1748955395,\\\"unit_amount\\\":200,\\\"quantity\\\":50,\\\"amount\\\":10000,\\\"pricing_model\\\":\\\"per_unit\\\",\\\"is_taxed\\\":false,\\\"tax_amount\\\":0,\\\"object\\\":\\\"line_item\\\",\\\"subscription_id\\\":\\\"16Bl6gUk8dtKM6HHl\\\",\\\"customer_id\\\":\\\"16Bl6gUk8eJHH6HRu\\\",\\\"description\\\":\\\"unlimited Monthly Plan\\\",\\\"entity_type\\\":\\\"plan_item_price\\\",\\\"entity_id\\\":\\\"unlimited_1746275957-monthly\\\",\\\"tax_exempt_reason\\\":\\\"tax_not_configured\\\",\\\"discount_amount\\\":0,\\\"item_level_discount_amount\\\":0}],\\\"sub_total\\\":10000,\\\"linked_payments\\\":[{\\\"txn_id\\\":\\\"txn_16Bl6gUk8eJLs6HS2\\\",\\\"applied_amount\\\":10000,\\\"applied_at\\\":1746276996,\\\"txn_status\\\":\\\"success\\\",\\\"txn_date\\\":1746276996,\\\"txn_amount\\\":10000}],\\\"applied_credits\\\":[],\\\"adjustment_credit_notes\\\":[],\\\"issued_credit_notes\\\":[],\\\"linked_orders\\\":[],\\\"dunning_attempts\\\":[],\\\"billing_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"ggg\\\",\\\"line1\\\":\\\"Tt. Vasumweg\\\",\\\"line2\\\":\\\"Amsterdam-Noord\\\",\\\"city\\\":\\\"Amsterdam\\\",\\\"state\\\":\\\"Noord-Holland\\\",\\\"country\\\":\\\"NL\\\",\\\"zip\\\":\\\"1033\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"shipping_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"ggg\\\",\\\"line1\\\":\\\"Tt. Vasumweg\\\",\\\"line2\\\":\\\"Amsterdam-Noord\\\",\\\"city\\\":\\\"Amsterdam\\\",\\\"state\\\":\\\"Noord-Holland\\\",\\\"country\\\":\\\"NL\\\",\\\"zip\\\":\\\"1033\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"site_details_at_creation\\\":{\\\"timezone\\\":\\\"Asia\\\\/Dubai\\\"}},\\\"customer\\\":{\\\"id\\\":\\\"16Bl6gUk8eJHH6HRu\\\",\\\"first_name\\\":\\\"TEST\\\",\\\"email\\\":\\\"test19@email.com\\\",\\\"phone\\\":\\\"21322323223\\\",\\\"auto_collection\\\":\\\"on\\\",\\\"net_term_days\\\":0,\\\"allow_direct_debit\\\":false,\\\"created_at\\\":1746276995,\\\"created_from_ip\\\":\\\"119.73.100.244\\\",\\\"taxability\\\":\\\"taxable\\\",\\\"updated_at\\\":1746276995,\\\"pii_cleared\\\":\\\"active\\\",\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746276995745,\\\"deleted\\\":false,\\\"object\\\":\\\"customer\\\",\\\"billing_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"ggg\\\",\\\"line1\\\":\\\"Tt. Vasumweg\\\",\\\"line2\\\":\\\"Amsterdam-Noord\\\",\\\"city\\\":\\\"Amsterdam\\\",\\\"state\\\":\\\"Noord-Holland\\\",\\\"country\\\":\\\"NL\\\",\\\"zip\\\":\\\"1033\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"card_status\\\":\\\"valid\\\",\\\"promotional_credits\\\":0,\\\"refundable_credits\\\":0,\\\"excess_payments\\\":0,\\\"unbilled_charges\\\":0,\\\"preferred_currency_code\\\":\\\"USD\\\",\\\"mrr\\\":0,\\\"primary_payment_source_id\\\":\\\"pm_16Bl6gUk8eJHo6HRv\\\",\\\"payment_method\\\":{\\\"object\\\":\\\"payment_method\\\",\\\"type\\\":\\\"card\\\",\\\"reference_id\\\":\\\"tok_16Bl6gUk8eJ0o6HRh\\\",\\\"gateway\\\":\\\"chargebee\\\",\\\"gateway_account_id\\\":\\\"gw_16CcWOUiLgm8NHOh\\\",\\\"status\\\":\\\"valid\\\"}},\\\"subscription\\\":{\\\"id\\\":\\\"16Bl6gUk8dtKM6HHl\\\",\\\"billing_period\\\":1,\\\"billing_period_unit\\\":\\\"month\\\",\\\"customer_id\\\":\\\"16Bl6gUk8eJHH6HRu\\\",\\\"status\\\":\\\"active\\\",\\\"current_term_start\\\":1746276995,\\\"current_term_end\\\":1748955395,\\\"next_billing_at\\\":1748955395,\\\"created_at\\\":1746276995,\\\"started_at\\\":1746276995,\\\"activated_at\\\":1746276995,\\\"created_from_ip\\\":\\\"119.73.100.244\\\",\\\"updated_at\\\":1746276996,\\\"has_scheduled_changes\\\":false,\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746276996056,\\\"deleted\\\":false,\\\"object\\\":\\\"subscription\\\",\\\"currency_code\\\":\\\"USD\\\",\\\"subscription_items\\\":[{\\\"item_price_id\\\":\\\"unlimited_1746275957-monthly\\\",\\\"item_type\\\":\\\"plan\\\",\\\"quantity\\\":50,\\\"unit_price\\\":200,\\\"amount\\\":10000,\\\"free_quantity\\\":0,\\\"object\\\":\\\"subscription_item\\\"}],\\\"shipping_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"ggg\\\",\\\"line1\\\":\\\"Tt. Vasumweg\\\",\\\"line2\\\":\\\"Amsterdam-Noord\\\",\\\"city\\\":\\\"Amsterdam\\\",\\\"state\\\":\\\"Noord-Holland\\\",\\\"country\\\":\\\"NL\\\",\\\"zip\\\":\\\"1033\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"due_invoices_count\\\":0,\\\"mrr\\\":0,\\\"has_scheduled_advance_invoices\\\":false}}\"','16Bl6gUk8dtKM6HHl','16Bl6gUk8eJHH6HRu','USD','2025-05-03 12:56:36','2025-05-03 12:56:37','2025-05-03 13:01:06',NULL),(2,8,NULL,'66',300.00,'paid','Completed','\"{\\\"invoice\\\":{\\\"id\\\":\\\"66\\\",\\\"customer_id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"subscription_id\\\":\\\"169zeEUk8g2Qn69cA\\\",\\\"recurring\\\":true,\\\"status\\\":\\\"paid\\\",\\\"price_type\\\":\\\"tax_exclusive\\\",\\\"date\\\":1746277432,\\\"due_date\\\":1746277432,\\\"net_term_days\\\":0,\\\"exchange_rate\\\":1,\\\"total\\\":30000,\\\"amount_paid\\\":30000,\\\"amount_adjusted\\\":0,\\\"write_off_amount\\\":0,\\\"credits_applied\\\":0,\\\"amount_due\\\":0,\\\"paid_at\\\":1746277432,\\\"updated_at\\\":1746277432,\\\"resource_version\\\":1746277432853,\\\"deleted\\\":false,\\\"object\\\":\\\"invoice\\\",\\\"first_invoice\\\":true,\\\"amount_to_collect\\\":0,\\\"round_off_amount\\\":0,\\\"new_sales_amount\\\":30000,\\\"has_advance_charges\\\":false,\\\"currency_code\\\":\\\"USD\\\",\\\"base_currency_code\\\":\\\"USD\\\",\\\"generated_at\\\":1746277432,\\\"is_gifted\\\":false,\\\"term_finalized\\\":true,\\\"channel\\\":\\\"web\\\",\\\"tax\\\":0,\\\"line_items\\\":[{\\\"id\\\":\\\"li_169zeEUk8g8yT69fp\\\",\\\"date_from\\\":1746277432,\\\"date_to\\\":1748955832,\\\"unit_amount\\\":200,\\\"quantity\\\":150,\\\"amount\\\":30000,\\\"pricing_model\\\":\\\"per_unit\\\",\\\"is_taxed\\\":false,\\\"tax_amount\\\":0,\\\"object\\\":\\\"line_item\\\",\\\"subscription_id\\\":\\\"169zeEUk8g2Qn69cA\\\",\\\"customer_id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"description\\\":\\\"unlimited Monthly Plan\\\",\\\"entity_type\\\":\\\"plan_item_price\\\",\\\"entity_id\\\":\\\"unlimited_1746275957-monthly\\\",\\\"tax_exempt_reason\\\":\\\"tax_not_configured\\\",\\\"discount_amount\\\":0,\\\"item_level_discount_amount\\\":0}],\\\"sub_total\\\":30000,\\\"linked_payments\\\":[{\\\"txn_id\\\":\\\"txn_169zeEUk8g8zJ69fq\\\",\\\"applied_amount\\\":30000,\\\"applied_at\\\":1746277432,\\\"txn_status\\\":\\\"success\\\",\\\"txn_date\\\":1746277432,\\\"txn_amount\\\":30000}],\\\"applied_credits\\\":[],\\\"adjustment_credit_notes\\\":[],\\\"issued_credit_notes\\\":[],\\\"linked_orders\\\":[],\\\"dunning_attempts\\\":[],\\\"billing_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"shipping_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"site_details_at_creation\\\":{\\\"timezone\\\":\\\"Asia\\\\/Dubai\\\"}},\\\"customer\\\":{\\\"id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"first_name\\\":\\\"TEST\\\",\\\"email\\\":\\\"test19@email.com\\\",\\\"phone\\\":\\\"21322323223\\\",\\\"auto_collection\\\":\\\"on\\\",\\\"net_term_days\\\":0,\\\"allow_direct_debit\\\":false,\\\"created_at\\\":1746277432,\\\"created_from_ip\\\":\\\"119.73.100.244\\\",\\\"taxability\\\":\\\"taxable\\\",\\\"updated_at\\\":1746277432,\\\"pii_cleared\\\":\\\"active\\\",\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746277432632,\\\"deleted\\\":false,\\\"object\\\":\\\"customer\\\",\\\"billing_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"card_status\\\":\\\"valid\\\",\\\"promotional_credits\\\":0,\\\"refundable_credits\\\":0,\\\"excess_payments\\\":0,\\\"unbilled_charges\\\":0,\\\"preferred_currency_code\\\":\\\"USD\\\",\\\"mrr\\\":0,\\\"primary_payment_source_id\\\":\\\"pm_169zeEUk8g8wP69fn\\\",\\\"payment_method\\\":{\\\"object\\\":\\\"payment_method\\\",\\\"type\\\":\\\"card\\\",\\\"reference_id\\\":\\\"tok_AzyfgQUk8g8fn67ZH\\\",\\\"gateway\\\":\\\"chargebee\\\",\\\"gateway_account_id\\\":\\\"gw_16CcWOUiLgm8NHOh\\\",\\\"status\\\":\\\"valid\\\"}},\\\"subscription\\\":{\\\"id\\\":\\\"169zeEUk8g2Qn69cA\\\",\\\"billing_period\\\":1,\\\"billing_period_unit\\\":\\\"month\\\",\\\"customer_id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"status\\\":\\\"active\\\",\\\"current_term_start\\\":1746277432,\\\"current_term_end\\\":1748955832,\\\"next_billing_at\\\":1748955832,\\\"created_at\\\":1746277432,\\\"started_at\\\":1746277432,\\\"activated_at\\\":1746277432,\\\"created_from_ip\\\":\\\"119.73.100.244\\\",\\\"updated_at\\\":1746277432,\\\"has_scheduled_changes\\\":false,\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746277432859,\\\"deleted\\\":false,\\\"object\\\":\\\"subscription\\\",\\\"currency_code\\\":\\\"USD\\\",\\\"subscription_items\\\":[{\\\"item_price_id\\\":\\\"unlimited_1746275957-monthly\\\",\\\"item_type\\\":\\\"plan\\\",\\\"quantity\\\":150,\\\"unit_price\\\":200,\\\"amount\\\":30000,\\\"free_quantity\\\":0,\\\"object\\\":\\\"subscription_item\\\"}],\\\"shipping_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"due_invoices_count\\\":0,\\\"mrr\\\":0,\\\"has_scheduled_advance_invoices\\\":false}}\"','169zeEUk8g2Qn69cA','169zeEUk8g8w269fm','USD','2025-05-03 13:03:52','2025-05-03 13:03:53','2025-05-03 13:17:09',NULL),(3,8,NULL,'67',400.00,'paid','pending','\"{\\\"invoice\\\":{\\\"id\\\":\\\"67\\\",\\\"customer_id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"subscription_id\\\":\\\"16Bl6gUk8mHrD6KpU\\\",\\\"recurring\\\":true,\\\"status\\\":\\\"paid\\\",\\\"price_type\\\":\\\"tax_exclusive\\\",\\\"date\\\":1746278911,\\\"due_date\\\":1746278911,\\\"net_term_days\\\":0,\\\"exchange_rate\\\":1,\\\"total\\\":40000,\\\"amount_paid\\\":40000,\\\"amount_adjusted\\\":0,\\\"write_off_amount\\\":0,\\\"credits_applied\\\":0,\\\"amount_due\\\":0,\\\"paid_at\\\":1746278911,\\\"updated_at\\\":1746278911,\\\"resource_version\\\":1746278911193,\\\"deleted\\\":false,\\\"object\\\":\\\"invoice\\\",\\\"first_invoice\\\":true,\\\"amount_to_collect\\\":0,\\\"round_off_amount\\\":0,\\\"new_sales_amount\\\":40000,\\\"has_advance_charges\\\":false,\\\"currency_code\\\":\\\"USD\\\",\\\"base_currency_code\\\":\\\"USD\\\",\\\"generated_at\\\":1746278911,\\\"is_gifted\\\":false,\\\"term_finalized\\\":true,\\\"channel\\\":\\\"web\\\",\\\"tax\\\":0,\\\"line_items\\\":[{\\\"id\\\":\\\"li_169zeEUk8mLYf6CMe\\\",\\\"date_from\\\":1746278911,\\\"date_to\\\":1748957311,\\\"unit_amount\\\":200,\\\"quantity\\\":200,\\\"amount\\\":40000,\\\"pricing_model\\\":\\\"per_unit\\\",\\\"is_taxed\\\":false,\\\"tax_amount\\\":0,\\\"object\\\":\\\"line_item\\\",\\\"subscription_id\\\":\\\"16Bl6gUk8mHrD6KpU\\\",\\\"customer_id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"description\\\":\\\"unlimited Monthly Price\\\",\\\"entity_type\\\":\\\"plan_item_price\\\",\\\"entity_id\\\":\\\"unlimited_1746275957-monthly\\\",\\\"tax_exempt_reason\\\":\\\"tax_not_configured\\\",\\\"discount_amount\\\":0,\\\"item_level_discount_amount\\\":0}],\\\"sub_total\\\":40000,\\\"linked_payments\\\":[{\\\"txn_id\\\":\\\"txn_169zeEUk8mLZU6CMg\\\",\\\"applied_amount\\\":40000,\\\"applied_at\\\":1746278911,\\\"txn_status\\\":\\\"success\\\",\\\"txn_date\\\":1746278911,\\\"txn_amount\\\":40000}],\\\"applied_credits\\\":[],\\\"adjustment_credit_notes\\\":[],\\\"issued_credit_notes\\\":[],\\\"linked_orders\\\":[],\\\"dunning_attempts\\\":[],\\\"billing_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"shipping_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"site_details_at_creation\\\":{\\\"timezone\\\":\\\"Asia\\\\/Dubai\\\"}},\\\"customer\\\":{\\\"id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"first_name\\\":\\\"TEST\\\",\\\"email\\\":\\\"test19@email.com\\\",\\\"phone\\\":\\\"21322323223\\\",\\\"auto_collection\\\":\\\"on\\\",\\\"net_term_days\\\":0,\\\"allow_direct_debit\\\":false,\\\"created_at\\\":1746277432,\\\"created_from_ip\\\":\\\"119.73.100.244\\\",\\\"taxability\\\":\\\"taxable\\\",\\\"updated_at\\\":1746278910,\\\"pii_cleared\\\":\\\"active\\\",\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746278910960,\\\"deleted\\\":false,\\\"object\\\":\\\"customer\\\",\\\"billing_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"card_status\\\":\\\"valid\\\",\\\"promotional_credits\\\":0,\\\"refundable_credits\\\":0,\\\"excess_payments\\\":0,\\\"unbilled_charges\\\":0,\\\"preferred_currency_code\\\":\\\"USD\\\",\\\"mrr\\\":30000,\\\"primary_payment_source_id\\\":\\\"pm_169zeEUk8g8wP69fn\\\",\\\"payment_method\\\":{\\\"object\\\":\\\"payment_method\\\",\\\"type\\\":\\\"card\\\",\\\"reference_id\\\":\\\"tok_AzyfgQUk8g8fn67ZH\\\",\\\"gateway\\\":\\\"chargebee\\\",\\\"gateway_account_id\\\":\\\"gw_16CcWOUiLgm8NHOh\\\",\\\"status\\\":\\\"valid\\\"}},\\\"subscription\\\":{\\\"id\\\":\\\"16Bl6gUk8mHrD6KpU\\\",\\\"billing_period\\\":1,\\\"billing_period_unit\\\":\\\"month\\\",\\\"customer_id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"status\\\":\\\"active\\\",\\\"current_term_start\\\":1746278911,\\\"current_term_end\\\":1748957311,\\\"next_billing_at\\\":1748957311,\\\"created_at\\\":1746278911,\\\"started_at\\\":1746278911,\\\"activated_at\\\":1746278911,\\\"created_from_ip\\\":\\\"119.73.100.244\\\",\\\"updated_at\\\":1746278911,\\\"has_scheduled_changes\\\":false,\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746278911198,\\\"deleted\\\":false,\\\"object\\\":\\\"subscription\\\",\\\"currency_code\\\":\\\"USD\\\",\\\"subscription_items\\\":[{\\\"item_price_id\\\":\\\"unlimited_1746275957-monthly\\\",\\\"item_type\\\":\\\"plan\\\",\\\"quantity\\\":200,\\\"unit_price\\\":200,\\\"amount\\\":40000,\\\"free_quantity\\\":0,\\\"object\\\":\\\"subscription_item\\\"}],\\\"shipping_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"due_invoices_count\\\":0,\\\"mrr\\\":0,\\\"has_scheduled_advance_invoices\\\":false}}\"','16Bl6gUk8mHrD6KpU','169zeEUk8g8w269fm','USD','2025-05-03 13:28:31','2025-05-03 13:28:32','2025-05-03 13:28:32',NULL),(4,12,3,'68',350.00,'paid','Completed','\"{\\\"invoice\\\":{\\\"id\\\":\\\"68\\\",\\\"customer_id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"subscription_id\\\":\\\"16Bl6gUkEI7bY9bdr\\\",\\\"recurring\\\":true,\\\"status\\\":\\\"paid\\\",\\\"price_type\\\":\\\"tax_exclusive\\\",\\\"date\\\":1746360441,\\\"due_date\\\":1746360441,\\\"net_term_days\\\":0,\\\"exchange_rate\\\":1,\\\"total\\\":35000,\\\"amount_paid\\\":35000,\\\"amount_adjusted\\\":0,\\\"write_off_amount\\\":0,\\\"credits_applied\\\":0,\\\"amount_due\\\":0,\\\"paid_at\\\":1746360441,\\\"updated_at\\\":1746360441,\\\"resource_version\\\":1746360441853,\\\"deleted\\\":false,\\\"object\\\":\\\"invoice\\\",\\\"first_invoice\\\":true,\\\"amount_to_collect\\\":0,\\\"round_off_amount\\\":0,\\\"new_sales_amount\\\":35000,\\\"has_advance_charges\\\":false,\\\"currency_code\\\":\\\"USD\\\",\\\"base_currency_code\\\":\\\"USD\\\",\\\"generated_at\\\":1746360441,\\\"is_gifted\\\":false,\\\"term_finalized\\\":true,\\\"channel\\\":\\\"web\\\",\\\"tax\\\":0,\\\"line_items\\\":[{\\\"id\\\":\\\"li_AzyfgQUkEIROc9Rlu\\\",\\\"date_from\\\":1746360441,\\\"date_to\\\":1749038841,\\\"unit_amount\\\":350,\\\"quantity\\\":100,\\\"amount\\\":35000,\\\"pricing_model\\\":\\\"per_unit\\\",\\\"is_taxed\\\":false,\\\"tax_amount\\\":0,\\\"object\\\":\\\"line_item\\\",\\\"subscription_id\\\":\\\"16Bl6gUkEI7bY9bdr\\\",\\\"customer_id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"description\\\":\\\"start up plan Monthly Plan\\\",\\\"entity_type\\\":\\\"plan_item_price\\\",\\\"entity_id\\\":\\\"start_up_plan_1746359310-monthly\\\",\\\"tax_exempt_reason\\\":\\\"tax_not_configured\\\",\\\"discount_amount\\\":0,\\\"item_level_discount_amount\\\":0}],\\\"sub_total\\\":35000,\\\"linked_payments\\\":[{\\\"txn_id\\\":\\\"txn_AzyfgQUkEIRPu9Rlv\\\",\\\"applied_amount\\\":35000,\\\"applied_at\\\":1746360441,\\\"txn_status\\\":\\\"success\\\",\\\"txn_date\\\":1746360441,\\\"txn_amount\\\":35000}],\\\"applied_credits\\\":[],\\\"adjustment_credit_notes\\\":[],\\\"issued_credit_notes\\\":[],\\\"linked_orders\\\":[],\\\"dunning_attempts\\\":[],\\\"billing_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"shipping_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"site_details_at_creation\\\":{\\\"timezone\\\":\\\"Asia\\\\/Dubai\\\"}},\\\"customer\\\":{\\\"id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"first_name\\\":\\\"Greg\\\",\\\"email\\\":\\\"Greg@gmail.com\\\",\\\"phone\\\":\\\"03135351215\\\",\\\"auto_collection\\\":\\\"on\\\",\\\"net_term_days\\\":0,\\\"allow_direct_debit\\\":false,\\\"created_at\\\":1746360441,\\\"created_from_ip\\\":\\\"182.183.8.156\\\",\\\"taxability\\\":\\\"taxable\\\",\\\"updated_at\\\":1746360441,\\\"pii_cleared\\\":\\\"active\\\",\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746360441541,\\\"deleted\\\":false,\\\"object\\\":\\\"customer\\\",\\\"billing_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"card_status\\\":\\\"valid\\\",\\\"promotional_credits\\\":0,\\\"refundable_credits\\\":0,\\\"excess_payments\\\":0,\\\"unbilled_charges\\\":0,\\\"preferred_currency_code\\\":\\\"USD\\\",\\\"mrr\\\":0,\\\"primary_payment_source_id\\\":\\\"pm_AzyfgQUkEIRLj9Rlq\\\",\\\"payment_method\\\":{\\\"object\\\":\\\"payment_method\\\",\\\"type\\\":\\\"card\\\",\\\"reference_id\\\":\\\"tok_AzyfgQUkEIR4n9Rle\\\",\\\"gateway\\\":\\\"chargebee\\\",\\\"gateway_account_id\\\":\\\"gw_16CcWOUiLgm8NHOh\\\",\\\"status\\\":\\\"valid\\\"}},\\\"subscription\\\":{\\\"id\\\":\\\"16Bl6gUkEI7bY9bdr\\\",\\\"billing_period\\\":1,\\\"billing_period_unit\\\":\\\"month\\\",\\\"customer_id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"status\\\":\\\"active\\\",\\\"current_term_start\\\":1746360441,\\\"current_term_end\\\":1749038841,\\\"next_billing_at\\\":1749038841,\\\"created_at\\\":1746360441,\\\"started_at\\\":1746360441,\\\"activated_at\\\":1746360441,\\\"created_from_ip\\\":\\\"182.183.8.156\\\",\\\"updated_at\\\":1746360441,\\\"has_scheduled_changes\\\":false,\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746360441863,\\\"deleted\\\":false,\\\"object\\\":\\\"subscription\\\",\\\"currency_code\\\":\\\"USD\\\",\\\"subscription_items\\\":[{\\\"item_price_id\\\":\\\"start_up_plan_1746359310-monthly\\\",\\\"item_type\\\":\\\"plan\\\",\\\"quantity\\\":100,\\\"unit_price\\\":350,\\\"amount\\\":35000,\\\"free_quantity\\\":0,\\\"object\\\":\\\"subscription_item\\\"}],\\\"shipping_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"due_invoices_count\\\":0,\\\"mrr\\\":0,\\\"has_scheduled_advance_invoices\\\":false}}\"','16Bl6gUkEI7bY9bdr','AzyfgQUkEIRL99Rlp','USD','2025-05-04 12:07:21','2025-05-04 12:07:23','2025-05-04 12:19:44',NULL),(5,12,4,'69',300.00,'paid','pending','\"{\\\"invoice\\\":{\\\"id\\\":\\\"69\\\",\\\"customer_id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"subscription_id\\\":\\\"169zeEUkEMM7a9VE2\\\",\\\"recurring\\\":true,\\\"status\\\":\\\"paid\\\",\\\"price_type\\\":\\\"tax_exclusive\\\",\\\"date\\\":1746361397,\\\"due_date\\\":1746361397,\\\"net_term_days\\\":0,\\\"exchange_rate\\\":1,\\\"total\\\":30000,\\\"amount_paid\\\":30000,\\\"amount_adjusted\\\":0,\\\"write_off_amount\\\":0,\\\"credits_applied\\\":0,\\\"amount_due\\\":0,\\\"paid_at\\\":1746361397,\\\"updated_at\\\":1746361398,\\\"resource_version\\\":1746361398018,\\\"deleted\\\":false,\\\"object\\\":\\\"invoice\\\",\\\"first_invoice\\\":true,\\\"amount_to_collect\\\":0,\\\"round_off_amount\\\":0,\\\"new_sales_amount\\\":30000,\\\"has_advance_charges\\\":false,\\\"currency_code\\\":\\\"USD\\\",\\\"base_currency_code\\\":\\\"USD\\\",\\\"generated_at\\\":1746361397,\\\"is_gifted\\\":false,\\\"term_finalized\\\":true,\\\"channel\\\":\\\"web\\\",\\\"tax\\\":0,\\\"line_items\\\":[{\\\"id\\\":\\\"li_169zeEUkEMS9I9VGN\\\",\\\"date_from\\\":1746361397,\\\"date_to\\\":1749039797,\\\"unit_amount\\\":200,\\\"quantity\\\":150,\\\"amount\\\":30000,\\\"pricing_model\\\":\\\"per_unit\\\",\\\"is_taxed\\\":false,\\\"tax_amount\\\":0,\\\"object\\\":\\\"line_item\\\",\\\"subscription_id\\\":\\\"169zeEUkEMM7a9VE2\\\",\\\"customer_id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"description\\\":\\\"enterprise plan Monthly Plan\\\",\\\"entity_type\\\":\\\"plan_item_price\\\",\\\"entity_id\\\":\\\"enterprise_plan_1746359393-monthly\\\",\\\"tax_exempt_reason\\\":\\\"tax_not_configured\\\",\\\"discount_amount\\\":0,\\\"item_level_discount_amount\\\":0}],\\\"sub_total\\\":30000,\\\"linked_payments\\\":[{\\\"txn_id\\\":\\\"txn_169zeEUkEMSA99VGO\\\",\\\"applied_amount\\\":30000,\\\"applied_at\\\":1746361397,\\\"txn_status\\\":\\\"success\\\",\\\"txn_date\\\":1746361397,\\\"txn_amount\\\":30000}],\\\"applied_credits\\\":[],\\\"adjustment_credit_notes\\\":[],\\\"issued_credit_notes\\\":[],\\\"linked_orders\\\":[],\\\"dunning_attempts\\\":[],\\\"billing_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"shipping_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"site_details_at_creation\\\":{\\\"timezone\\\":\\\"Asia\\\\/Dubai\\\"}},\\\"customer\\\":{\\\"id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"first_name\\\":\\\"Greg\\\",\\\"email\\\":\\\"Greg@gmail.com\\\",\\\"phone\\\":\\\"03135351215\\\",\\\"auto_collection\\\":\\\"on\\\",\\\"net_term_days\\\":0,\\\"allow_direct_debit\\\":false,\\\"created_at\\\":1746360441,\\\"created_from_ip\\\":\\\"182.183.8.156\\\",\\\"taxability\\\":\\\"taxable\\\",\\\"updated_at\\\":1746361397,\\\"pii_cleared\\\":\\\"active\\\",\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746361397794,\\\"deleted\\\":false,\\\"object\\\":\\\"customer\\\",\\\"billing_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"card_status\\\":\\\"valid\\\",\\\"promotional_credits\\\":0,\\\"refundable_credits\\\":0,\\\"excess_payments\\\":0,\\\"unbilled_charges\\\":0,\\\"preferred_currency_code\\\":\\\"USD\\\",\\\"mrr\\\":35000,\\\"primary_payment_source_id\\\":\\\"pm_AzyfgQUkEIRLj9Rlq\\\",\\\"payment_method\\\":{\\\"object\\\":\\\"payment_method\\\",\\\"type\\\":\\\"card\\\",\\\"reference_id\\\":\\\"tok_AzyfgQUkEIR4n9Rle\\\",\\\"gateway\\\":\\\"chargebee\\\",\\\"gateway_account_id\\\":\\\"gw_16CcWOUiLgm8NHOh\\\",\\\"status\\\":\\\"valid\\\"}},\\\"subscription\\\":{\\\"id\\\":\\\"169zeEUkEMM7a9VE2\\\",\\\"billing_period\\\":1,\\\"billing_period_unit\\\":\\\"month\\\",\\\"customer_id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"status\\\":\\\"active\\\",\\\"current_term_start\\\":1746361397,\\\"current_term_end\\\":1749039797,\\\"next_billing_at\\\":1749039797,\\\"created_at\\\":1746361397,\\\"started_at\\\":1746361397,\\\"activated_at\\\":1746361397,\\\"created_from_ip\\\":\\\"182.183.8.156\\\",\\\"updated_at\\\":1746361398,\\\"has_scheduled_changes\\\":false,\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746361398024,\\\"deleted\\\":false,\\\"object\\\":\\\"subscription\\\",\\\"currency_code\\\":\\\"USD\\\",\\\"subscription_items\\\":[{\\\"item_price_id\\\":\\\"enterprise_plan_1746359393-monthly\\\",\\\"item_type\\\":\\\"plan\\\",\\\"quantity\\\":150,\\\"unit_price\\\":200,\\\"amount\\\":30000,\\\"free_quantity\\\":0,\\\"object\\\":\\\"subscription_item\\\"}],\\\"shipping_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"due_invoices_count\\\":0,\\\"mrr\\\":0,\\\"has_scheduled_advance_invoices\\\":false}}\"','169zeEUkEMM7a9VE2','AzyfgQUkEIRL99Rlp','USD','2025-05-04 12:23:17','2025-05-04 12:23:19','2025-05-04 12:23:19',NULL);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'Dashboard','web','2025-05-03 12:27:09','2025-05-03 12:27:09'),(2,'Admins','web','2025-05-03 12:27:09','2025-05-03 12:27:09'),(3,'Customer','web','2025-05-03 12:27:09','2025-05-03 12:27:09'),(4,'Subscriptions','web','2025-05-03 12:27:09','2025-05-03 12:27:09'),(5,'Contractors','web','2025-05-03 12:27:09','2025-05-03 12:27:09'),(6,'Invoices','web','2025-05-03 12:27:09','2025-05-03 12:27:09'),(7,'Orders','web','2025-05-03 12:27:09','2025-05-03 12:27:09'),(8,'Plans','web','2025-05-03 12:27:09','2025-05-03 12:27:09'),(9,'Roles','web','2025-05-03 12:27:09','2025-05-03 12:27:09'),(10,'Mod','web','2025-05-02 10:21:17','2025-05-02 10:21:17');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
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
-- Table structure for table `plans`
--

DROP TABLE IF EXISTS `plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chargebee_plan_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) NOT NULL,
  `duration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `min_inbox` int NOT NULL DEFAULT '1',
  `max_inbox` int NOT NULL DEFAULT '5',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plans`
--

LOCK TABLES `plans` WRITE;
/*!40000 ALTER TABLE `plans` DISABLE KEYS */;
INSERT INTO `plans` VALUES (3,'start up plan','start_up_plan_1746359310-monthly','this package is most selling',3.50,'monthly',1,100,1,'2025-05-04 11:48:31','2025-05-04 11:48:31'),(4,'enterprise plan','enterprise_plan_1746359393-monthly','this is unlimited pacakge',2.00,'monthly',101,0,1,'2025-05-04 11:49:54','2025-05-04 11:49:54');
/*!40000 ALTER TABLE `plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reorder_infos`
--

DROP TABLE IF EXISTS `reorder_infos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reorder_infos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `plan_id` bigint unsigned NOT NULL,
  `forwarding_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hosting_platform` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `other_platform` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bison_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bison_workspace` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `backup_codes` text COLLATE utf8mb4_unicode_ci,
  `platform_login` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `platform_password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `domains` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sending_platform` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sequencer_login` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sequencer_password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_inboxes` int NOT NULL,
  `inboxes_per_domain` int NOT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prefix_variant_1` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prefix_variant_2` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `persona_password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_picture_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_persona_password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_persona_picture_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `master_inbox_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `additional_info` text COLLATE utf8mb4_unicode_ci,
  `coupon_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `tutorial_section` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reorder_infos_user_id_foreign` (`user_id`),
  KEY `reorder_infos_plan_id_foreign` (`plan_id`),
  KEY `reorder_infos_order_id_foreign` (`order_id`),
  CONSTRAINT `reorder_infos_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reorder_infos_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reorder_infos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reorder_infos`
--

LOCK TABLES `reorder_infos` WRITE;
/*!40000 ALTER TABLE `reorder_infos` DISABLE KEYS */;
INSERT INTO `reorder_infos` VALUES (4,12,3,'https://pmybals.pmyp.gov.pk/BankForm/newApplicantForm?CNIC=61101-0317746-7&CIssueDate=03-25-2022&Tier=Tier1','namecheap',NULL,NULL,NULL,'1214352134\r\n321323123\r\n3313123\r\n32132','app.projectinbox@gmail.com','Admin123#','izGrowthHub.com, StartupNest.io, MarketPulse360.com, FinSavvyPro.com, GlobalTradeDesk.com, CapitalWise.net, ScaleStrategix.com, BizElevateNow.com, LedgerLogic.com, BrandCrafters.org,TechNexusLab.com, CodeHive.dev, CyberPulseTech.com, AIForgeX.com, DataCloudly.com, QuantumOS.io, SmartNetWorks.com, AppSprint.io, DevMasterZone.com, NextGenSoft.dev,VitalCoreHealth.com, FitFusionLife.com, MindBodyAnchor.com, PureWellLiving.com, HolistiqCare.com, ThriveClinic360.com, HealThriveHub.com, CalmWellCenter.com, PulseRxOnline.com, MedBridgeNow.com,LearnSphere.io, BrightMindAcademy.com, EduBridge360.com, SkillStackers.com, StudyNest.org, NextGenTutors.com, MathMastersZone.com, LangFluently.com, CodeCraftEdu.com, ThinkGrowSchool.com,ShopNovaOnline.com, TrendMart360.com, ClickCartDepot.com, SwiftShoppers.com, UrbanDealsHub.com, EcoBuySmart.com, LuxeGoodsBox.com, SnapAndShop.io, GadgetHiveStore.com, FastTrackShop.com,PixelWaveStudio.com, CreativoSpace.com, MediaForgeLab.com, SnapVerse.io, ArtNestGallery.com, VisionQuill.com, AudioInkStudio.com, FrameMuseMedia.com, ScriptedHive.com, FilmTrekStudio.com,ExploreNomadly.com, ChillNestRetreats.com, DailyZenGuide.com, WanderBloomTrips.com, LuxeTrailAdventures.com, ModernLivingVibe.com, UrbanNestLife.com, ZenLifestyle360.com, WildPathJournals.com, NomadFuelHub.com,EcoFutureNow.com, GreenHavenLife.com, ReuseRevive.com, ZeroWasteGen.com, EcoNestCollective.com, PlanetWiseHub.com, GreenSparkTech.com, SolarBrightly.com, EarthNovaNow.com, BioCycleWorks.com,SavorNestKitchen.com, BrewHiveCafe.com, TasteMingle.com, EpicureanRoots.com, SpoonCraftBites.com, WhiskFusion.com, FreshForkMarket.com, UrbanGroveEats.com, CraveNestCatering.com, ForkAndFarm.org,MindFuelDaily.com, LifeUpgradeLab.com, InnerDriveCoaching.com, GrowthMindedYou.com, ZenMasteryPath.com, ClarityStepsNow.com, GoalForgeStudio.com, SelfLift360.com, PowerWithinNet.com, TrueYouBlueprint.com','prospi','abc@gmail.com','12345678',100,1,'greg','schibbs','hamza@','umer@','12345678',NULL,'12345678',NULL,NULL,'gyfgwyege hfheyf',NULL,'2025-05-04 12:07:23','2025-05-04 12:15:42',4,NULL),(5,12,4,'https://pmybals.pmyp.gov.pk/BankForm/newApplicantForm?CNIC=61101-0317746-7&CIssueDate=03-25-2022&Tier=Tier1','namecheap',NULL,NULL,NULL,'1214352134\r\n321323123\r\n3313123\r\n32132','app.projectinbox@gmail.com','Admin123#','izGrowthHub.com, StartupNest.io, MarketPulse360.com, FinSavvyPro.com, GlobalTradeDesk.com, CapitalWise.net, ScaleStrategix.com, BizElevateNow.com, LedgerLogic.com, BrandCrafters.org,TechNexusLab.com, CodeHive.dev, CyberPulseTech.com, AIForgeX.com, DataCloudly.com, QuantumOS.io, SmartNetWorks.com, AppSprint.io, DevMasterZone.com, NextGenSoft.dev,VitalCoreHealth.com, FitFusionLife.com, MindBodyAnchor.com, PureWellLiving.com, HolistiqCare.com, ThriveClinic360.com, HealThriveHub.com, CalmWellCenter.com, PulseRxOnline.com, MedBridgeNow.com,LearnSphere.io, BrightMindAcademy.com, EduBridge360.com, SkillStackers.com, StudyNest.org, NextGenTutors.com, MathMastersZone.com, LangFluently.com, CodeCraftEdu.com, ThinkGrowSchool.com,ShopNovaOnline.com, TrendMart360.com, ClickCartDepot.com, SwiftShoppers.com, UrbanDealsHub.com, EcoBuySmart.com, LuxeGoodsBox.com, SnapAndShop.io, GadgetHiveStore.com, FastTrackShop.com,PixelWaveStudio.com, CreativoSpace.com, MediaForgeLab.com, SnapVerse.io, ArtNestGallery.com, VisionQuill.com, AudioInkStudio.com, FrameMuseMedia.com, ScriptedHive.com, FilmTrekStudio.com,ExploreNomadly.com, ChillNestRetreats.com, DailyZenGuide.com, WanderBloomTrips.com, LuxeTrailAdventures.com, ModernLivingVibe.com, UrbanNestLife.com, ZenLifestyle360.com, WildPathJournals.com, NomadFuelHub.com,EcoFutureNow.com, GreenHavenLife.com, ReuseRevive.com, ZeroWasteGen.com, EcoNestCollective.com, PlanetWiseHub.com, GreenSparkTech.com, SolarBrightly.com, EarthNovaNow.com, BioCycleWorks.com,SavorNestKitchen.com, BrewHiveCafe.com, TasteMingle.com, EpicureanRoots.com, SpoonCraftBites.com, WhiskFusion.com, FreshForkMarket.com, UrbanGroveEats.com, CraveNestCatering.com, ForkAndFarm.org,MindFuelDaily.com, LifeUpgradeLab.com, InnerDriveCoaching.com, GrowthMindedYou.com, ZenMasteryPath.com, ClarityStepsNow.com, GoalForgeStudio.com, SelfLift360.com, PowerWithinNet.com, TrueYouBlueprint.com, FutureProofHQ.com, IdeaOrbit.com, MindSprintLab.com, SynergyNest.com, CloudMetric.io, SkillFlare.net, ZenPathClinic.com, InsightCrate.com, GrowTide.com, ImpactNest.org,\r\nTradeRoute360.com, HustleForge.com, WorkNestStudio.com, SmartGenLabs.com, LearnNovaOnline.com, ElevateMindset.com, BrainFuelers.com, PulseTrackPro.com, HealthGrid360.com, RiseBrightLife.com,\r\nCodeNestSystems.com, LogicBotics.com, DataNestAI.com, NeuralVerseTech.com, VisionSparkAI.com, DevLaunchHub.com, SwiftWebWorks.com, CodeRipple.com, ByteHive.dev, SynthGrid.io,\r\nShopStreamNow.com, FastTrendMart.com, CozyCartSpot.com, DealNovaStore.com, EcoCartify.com, DailyClickMart.com, FreshGoodsDepot.com, StyleNestOutlet.com, TrendyCrate.com, ClickAndCrave.com,\r\nNomadGlide.com, TrueRootsTravel.com, WanderNestPro.com, ExploreSync.com, PeaceTrekker.com, LifeQuestTravel.com, ZenTrailJournals.com, BioVoyage.com, TravelBloomNow.com, RoamWiseTrips.com','prospi','abc@gmail.com','12345678',150,1,'greg','schibbs','hamza@','umer@','12345678',NULL,'12345678',NULL,NULL,'gyfgwyege hfheyf',NULL,'2025-05-04 12:23:19','2025-05-04 12:23:19',5,NULL);
/*!40000 ALTER TABLE `reorder_infos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(6,2),(7,2),(7,3),(8,3),(1,4),(2,4),(3,4),(4,4),(5,4),(6,4),(7,4),(8,4),(9,4),(10,4);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Super Admin','web',NULL,NULL),(2,'accountant','web','2025-05-03 13:39:53','2025-05-03 13:39:53'),(3,'assistant','web','2025-05-04 11:42:22','2025-05-04 11:42:22'),(4,'MOD','web','2025-05-04 12:27:18','2025-05-04 12:27:18');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sending_platforms`
--

DROP TABLE IF EXISTS `sending_platforms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sending_platforms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fields` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sending_platforms_value_unique` (`value`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sending_platforms`
--

LOCK TABLES `sending_platforms` WRITE;
/*!40000 ALTER TABLE `sending_platforms` DISABLE KEYS */;
INSERT INTO `sending_platforms` VALUES (1,'Instantly','instantly','{\"sequencer_login\": {\"type\": \"email\", \"label\": \"Sequencer Login\", \"required\": true}, \"sequencer_password\": {\"type\": \"password\", \"label\": \"Sequencer Password\", \"required\": true}}','2025-05-03 12:27:08','2025-05-03 12:27:08'),(2,'Prospi.ai','prospi','{\"sequencer_login\": {\"type\": \"email\", \"label\": \"Sequencer Login\", \"required\": true}, \"sequencer_password\": {\"type\": \"password\", \"label\": \"Sequencer Password\", \"required\": true}}','2025-05-03 12:27:08','2025-05-03 12:27:08'),(3,'Smartlead','smartlead','{\"sequencer_login\": {\"type\": \"email\", \"label\": \"Sequencer Login\", \"required\": true}, \"sequencer_password\": {\"type\": \"password\", \"label\": \"Sequencer Password\", \"required\": true}}','2025-05-03 12:27:08','2025-05-03 12:27:08'),(4,'Lemlist','lemlist','{\"sequencer_login\": {\"type\": \"email\", \"label\": \"Sequencer Login\", \"required\": true}, \"sequencer_password\": {\"type\": \"password\", \"label\": \"Sequencer Password\", \"required\": true}}','2025-05-03 12:27:08','2025-05-03 12:27:08'),(5,'Pipl.ai','pipl','{\"sequencer_login\": {\"type\": \"email\", \"label\": \"Sequencer Login\", \"required\": true}, \"sequencer_password\": {\"type\": \"password\", \"label\": \"Sequencer Password\", \"required\": true}}','2025-05-03 12:27:08','2025-05-03 12:27:08'),(6,'Reply.io','replyio','{\"sequencer_login\": {\"type\": \"email\", \"label\": \"Sequencer Login\", \"required\": true}, \"sequencer_password\": {\"type\": \"password\", \"label\": \"Sequencer Password\", \"required\": true}}','2025-05-03 12:27:08','2025-05-03 12:27:08'),(7,'Hothawk','hothawk','{\"sequencer_login\": {\"type\": \"email\", \"label\": \"Sequencer Login\", \"required\": true}, \"sequencer_password\": {\"type\": \"password\", \"label\": \"Sequencer Password\", \"required\": true}}','2025-05-03 12:27:08','2025-05-03 12:27:08'),(8,'Other','other','{\"sequencer_login\": {\"type\": \"email\", \"label\": \"Sequencer Login\", \"required\": true}, \"sequencer_password\": {\"type\": \"password\", \"label\": \"Sequencer Password\", \"required\": true}}','2025-05-03 12:27:08','2025-05-03 12:27:08'),(9,'Bison','bison','{\"bison_url\": {\"type\": \"url\", \"label\": \"Sending Platform - Your Unique Bison URL\", \"required\": true}, \"bison_workspace\": {\"type\": \"text\", \"label\": \"Sending Platform - Bison Workspace Name\", \"required\": true}, \"sequencer_login\": {\"type\": \"email\", \"label\": \"Sequencer Login\", \"required\": true}, \"sequencer_password\": {\"type\": \"password\", \"label\": \"Sequencer Password\", \"required\": true}}','2025-05-03 12:27:08','2025-05-03 12:27:08');
/*!40000 ALTER TABLE `sending_platforms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sidebar_navigations`
--

DROP TABLE IF EXISTS `sidebar_navigations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sidebar_navigations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `route` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sub_menu` json DEFAULT NULL,
  `order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `parent_id` int DEFAULT NULL,
  `permission` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sidebar_navigations`
--

LOCK TABLES `sidebar_navigations` WRITE;
/*!40000 ALTER TABLE `sidebar_navigations` DISABLE KEYS */;
INSERT INTO `sidebar_navigations` VALUES (1,'Dashboard','ti ti-home fs-5','admin.dashboard',NULL,0,1,NULL,'Dashboard','2025-05-03 12:27:09','2025-05-03 12:27:09'),(2,'Admins','ti ti-user fs-5','admin.index',NULL,0,1,NULL,'Admins','2025-05-03 12:27:09','2025-05-03 12:27:09'),(3,'Customer','ti ti-headphones fs-5','admin.customerList',NULL,0,1,NULL,'Customer','2025-05-03 12:27:09','2025-05-03 12:27:09'),(4,'Subscriptions','ti ti-currency-dollar fs-5','admin.subs.view',NULL,0,1,NULL,'Subscriptions','2025-05-03 12:27:09','2025-05-03 12:27:09'),(5,'Contractors','ti ti-contract fs-5','admin.contractorList',NULL,0,1,NULL,'Contractors','2025-05-03 12:27:09','2025-05-03 12:27:09'),(6,'Invoices','ti ti-file-invoice fs-5','admin.invoices.index',NULL,0,1,NULL,'Invoices','2025-05-03 12:27:09','2025-05-03 12:27:09'),(7,'Orders','ti ti-box fs-5','admin.orders',NULL,0,1,NULL,'Orders','2025-05-03 12:27:09','2025-05-03 12:27:09'),(8,'Plans','ti ti-devices-dollar fs-5','admin.pricing',NULL,0,1,NULL,'Plans','2025-05-03 12:27:09','2025-05-03 12:27:09'),(9,'Roles','ti ti-circles fs-5','admin.role.index',NULL,0,1,NULL,'Roles','2025-05-03 12:27:09','2025-05-03 12:27:09');
/*!40000 ALTER TABLE `sidebar_navigations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned NOT NULL,
  `chargebee_subscription_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plan_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `last_billing_date` datetime DEFAULT NULL,
  `next_billing_date` datetime DEFAULT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancellation_at` timestamp NULL DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `chargebee_customer_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chargebee_invoice_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscriptions_chargebee_subscription_id_unique` (`chargebee_subscription_id`),
  KEY `subscriptions_user_id_foreign` (`user_id`),
  KEY `subscriptions_order_id_foreign` (`order_id`),
  CONSTRAINT `subscriptions_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
INSERT INTO `subscriptions` VALUES (1,8,1,'16Bl6gUk8dtKM6HHl','2','cancelled','2025-05-03 12:56:36',NULL,'dsdsd','2025-05-03 13:01:06','\"{\\\"invoice\\\":{\\\"id\\\":\\\"65\\\",\\\"customer_id\\\":\\\"16Bl6gUk8eJHH6HRu\\\",\\\"subscription_id\\\":\\\"16Bl6gUk8dtKM6HHl\\\",\\\"recurring\\\":true,\\\"status\\\":\\\"paid\\\",\\\"price_type\\\":\\\"tax_exclusive\\\",\\\"date\\\":1746276995,\\\"due_date\\\":1746276995,\\\"net_term_days\\\":0,\\\"exchange_rate\\\":1,\\\"total\\\":10000,\\\"amount_paid\\\":10000,\\\"amount_adjusted\\\":0,\\\"write_off_amount\\\":0,\\\"credits_applied\\\":0,\\\"amount_due\\\":0,\\\"paid_at\\\":1746276996,\\\"updated_at\\\":1746276996,\\\"resource_version\\\":1746276996046,\\\"deleted\\\":false,\\\"object\\\":\\\"invoice\\\",\\\"first_invoice\\\":true,\\\"amount_to_collect\\\":0,\\\"round_off_amount\\\":0,\\\"new_sales_amount\\\":10000,\\\"has_advance_charges\\\":false,\\\"currency_code\\\":\\\"USD\\\",\\\"base_currency_code\\\":\\\"USD\\\",\\\"generated_at\\\":1746276995,\\\"is_gifted\\\":false,\\\"term_finalized\\\":true,\\\"channel\\\":\\\"web\\\",\\\"tax\\\":0,\\\"line_items\\\":[{\\\"id\\\":\\\"li_16Bl6gUk8eJKi6HRy\\\",\\\"date_from\\\":1746276995,\\\"date_to\\\":1748955395,\\\"unit_amount\\\":200,\\\"quantity\\\":50,\\\"amount\\\":10000,\\\"pricing_model\\\":\\\"per_unit\\\",\\\"is_taxed\\\":false,\\\"tax_amount\\\":0,\\\"object\\\":\\\"line_item\\\",\\\"subscription_id\\\":\\\"16Bl6gUk8dtKM6HHl\\\",\\\"customer_id\\\":\\\"16Bl6gUk8eJHH6HRu\\\",\\\"description\\\":\\\"unlimited Monthly Plan\\\",\\\"entity_type\\\":\\\"plan_item_price\\\",\\\"entity_id\\\":\\\"unlimited_1746275957-monthly\\\",\\\"tax_exempt_reason\\\":\\\"tax_not_configured\\\",\\\"discount_amount\\\":0,\\\"item_level_discount_amount\\\":0}],\\\"sub_total\\\":10000,\\\"linked_payments\\\":[{\\\"txn_id\\\":\\\"txn_16Bl6gUk8eJLs6HS2\\\",\\\"applied_amount\\\":10000,\\\"applied_at\\\":1746276996,\\\"txn_status\\\":\\\"success\\\",\\\"txn_date\\\":1746276996,\\\"txn_amount\\\":10000}],\\\"applied_credits\\\":[],\\\"adjustment_credit_notes\\\":[],\\\"issued_credit_notes\\\":[],\\\"linked_orders\\\":[],\\\"dunning_attempts\\\":[],\\\"billing_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"ggg\\\",\\\"line1\\\":\\\"Tt. Vasumweg\\\",\\\"line2\\\":\\\"Amsterdam-Noord\\\",\\\"city\\\":\\\"Amsterdam\\\",\\\"state\\\":\\\"Noord-Holland\\\",\\\"country\\\":\\\"NL\\\",\\\"zip\\\":\\\"1033\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"shipping_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"ggg\\\",\\\"line1\\\":\\\"Tt. Vasumweg\\\",\\\"line2\\\":\\\"Amsterdam-Noord\\\",\\\"city\\\":\\\"Amsterdam\\\",\\\"state\\\":\\\"Noord-Holland\\\",\\\"country\\\":\\\"NL\\\",\\\"zip\\\":\\\"1033\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"site_details_at_creation\\\":{\\\"timezone\\\":\\\"Asia\\\\/Dubai\\\"}},\\\"customer\\\":{\\\"id\\\":\\\"16Bl6gUk8eJHH6HRu\\\",\\\"first_name\\\":\\\"TEST\\\",\\\"email\\\":\\\"test19@email.com\\\",\\\"phone\\\":\\\"21322323223\\\",\\\"auto_collection\\\":\\\"on\\\",\\\"net_term_days\\\":0,\\\"allow_direct_debit\\\":false,\\\"created_at\\\":1746276995,\\\"created_from_ip\\\":\\\"119.73.100.244\\\",\\\"taxability\\\":\\\"taxable\\\",\\\"updated_at\\\":1746276995,\\\"pii_cleared\\\":\\\"active\\\",\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746276995745,\\\"deleted\\\":false,\\\"object\\\":\\\"customer\\\",\\\"billing_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"ggg\\\",\\\"line1\\\":\\\"Tt. Vasumweg\\\",\\\"line2\\\":\\\"Amsterdam-Noord\\\",\\\"city\\\":\\\"Amsterdam\\\",\\\"state\\\":\\\"Noord-Holland\\\",\\\"country\\\":\\\"NL\\\",\\\"zip\\\":\\\"1033\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"card_status\\\":\\\"valid\\\",\\\"promotional_credits\\\":0,\\\"refundable_credits\\\":0,\\\"excess_payments\\\":0,\\\"unbilled_charges\\\":0,\\\"preferred_currency_code\\\":\\\"USD\\\",\\\"mrr\\\":0,\\\"primary_payment_source_id\\\":\\\"pm_16Bl6gUk8eJHo6HRv\\\",\\\"payment_method\\\":{\\\"object\\\":\\\"payment_method\\\",\\\"type\\\":\\\"card\\\",\\\"reference_id\\\":\\\"tok_16Bl6gUk8eJ0o6HRh\\\",\\\"gateway\\\":\\\"chargebee\\\",\\\"gateway_account_id\\\":\\\"gw_16CcWOUiLgm8NHOh\\\",\\\"status\\\":\\\"valid\\\"}},\\\"subscription\\\":{\\\"id\\\":\\\"16Bl6gUk8dtKM6HHl\\\",\\\"billing_period\\\":1,\\\"billing_period_unit\\\":\\\"month\\\",\\\"customer_id\\\":\\\"16Bl6gUk8eJHH6HRu\\\",\\\"status\\\":\\\"active\\\",\\\"current_term_start\\\":1746276995,\\\"current_term_end\\\":1748955395,\\\"next_billing_at\\\":1748955395,\\\"created_at\\\":1746276995,\\\"started_at\\\":1746276995,\\\"activated_at\\\":1746276995,\\\"created_from_ip\\\":\\\"119.73.100.244\\\",\\\"updated_at\\\":1746276996,\\\"has_scheduled_changes\\\":false,\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746276996056,\\\"deleted\\\":false,\\\"object\\\":\\\"subscription\\\",\\\"currency_code\\\":\\\"USD\\\",\\\"subscription_items\\\":[{\\\"item_price_id\\\":\\\"unlimited_1746275957-monthly\\\",\\\"item_type\\\":\\\"plan\\\",\\\"quantity\\\":50,\\\"unit_price\\\":200,\\\"amount\\\":10000,\\\"free_quantity\\\":0,\\\"object\\\":\\\"subscription_item\\\"}],\\\"shipping_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"ggg\\\",\\\"line1\\\":\\\"Tt. Vasumweg\\\",\\\"line2\\\":\\\"Amsterdam-Noord\\\",\\\"city\\\":\\\"Amsterdam\\\",\\\"state\\\":\\\"Noord-Holland\\\",\\\"country\\\":\\\"NL\\\",\\\"zip\\\":\\\"1033\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"due_invoices_count\\\":0,\\\"mrr\\\":0,\\\"has_scheduled_advance_invoices\\\":false}}\"','16Bl6gUk8eJHH6HRu','65','2025-05-03 12:56:37','2025-06-02 12:56:37','2025-05-03 12:56:37','2025-05-03 13:01:06'),(2,8,2,'169zeEUk8g2Qn69cA','2','active','2025-05-03 13:03:52','2025-06-04 13:03:52',NULL,NULL,'\"{\\\"invoice\\\":{\\\"id\\\":\\\"66\\\",\\\"customer_id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"subscription_id\\\":\\\"169zeEUk8g2Qn69cA\\\",\\\"recurring\\\":true,\\\"status\\\":\\\"paid\\\",\\\"price_type\\\":\\\"tax_exclusive\\\",\\\"date\\\":1746277432,\\\"due_date\\\":1746277432,\\\"net_term_days\\\":0,\\\"exchange_rate\\\":1,\\\"total\\\":30000,\\\"amount_paid\\\":30000,\\\"amount_adjusted\\\":0,\\\"write_off_amount\\\":0,\\\"credits_applied\\\":0,\\\"amount_due\\\":0,\\\"paid_at\\\":1746277432,\\\"updated_at\\\":1746277432,\\\"resource_version\\\":1746277432853,\\\"deleted\\\":false,\\\"object\\\":\\\"invoice\\\",\\\"first_invoice\\\":true,\\\"amount_to_collect\\\":0,\\\"round_off_amount\\\":0,\\\"new_sales_amount\\\":30000,\\\"has_advance_charges\\\":false,\\\"currency_code\\\":\\\"USD\\\",\\\"base_currency_code\\\":\\\"USD\\\",\\\"generated_at\\\":1746277432,\\\"is_gifted\\\":false,\\\"term_finalized\\\":true,\\\"channel\\\":\\\"web\\\",\\\"tax\\\":0,\\\"line_items\\\":[{\\\"id\\\":\\\"li_169zeEUk8g8yT69fp\\\",\\\"date_from\\\":1746277432,\\\"date_to\\\":1748955832,\\\"unit_amount\\\":200,\\\"quantity\\\":150,\\\"amount\\\":30000,\\\"pricing_model\\\":\\\"per_unit\\\",\\\"is_taxed\\\":false,\\\"tax_amount\\\":0,\\\"object\\\":\\\"line_item\\\",\\\"subscription_id\\\":\\\"169zeEUk8g2Qn69cA\\\",\\\"customer_id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"description\\\":\\\"unlimited Monthly Plan\\\",\\\"entity_type\\\":\\\"plan_item_price\\\",\\\"entity_id\\\":\\\"unlimited_1746275957-monthly\\\",\\\"tax_exempt_reason\\\":\\\"tax_not_configured\\\",\\\"discount_amount\\\":0,\\\"item_level_discount_amount\\\":0}],\\\"sub_total\\\":30000,\\\"linked_payments\\\":[{\\\"txn_id\\\":\\\"txn_169zeEUk8g8zJ69fq\\\",\\\"applied_amount\\\":30000,\\\"applied_at\\\":1746277432,\\\"txn_status\\\":\\\"success\\\",\\\"txn_date\\\":1746277432,\\\"txn_amount\\\":30000}],\\\"applied_credits\\\":[],\\\"adjustment_credit_notes\\\":[],\\\"issued_credit_notes\\\":[],\\\"linked_orders\\\":[],\\\"dunning_attempts\\\":[],\\\"billing_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"shipping_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"site_details_at_creation\\\":{\\\"timezone\\\":\\\"Asia\\\\/Dubai\\\"}},\\\"customer\\\":{\\\"id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"first_name\\\":\\\"TEST\\\",\\\"email\\\":\\\"test19@email.com\\\",\\\"phone\\\":\\\"21322323223\\\",\\\"auto_collection\\\":\\\"on\\\",\\\"net_term_days\\\":0,\\\"allow_direct_debit\\\":false,\\\"created_at\\\":1746277432,\\\"created_from_ip\\\":\\\"119.73.100.244\\\",\\\"taxability\\\":\\\"taxable\\\",\\\"updated_at\\\":1746277432,\\\"pii_cleared\\\":\\\"active\\\",\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746277432632,\\\"deleted\\\":false,\\\"object\\\":\\\"customer\\\",\\\"billing_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"card_status\\\":\\\"valid\\\",\\\"promotional_credits\\\":0,\\\"refundable_credits\\\":0,\\\"excess_payments\\\":0,\\\"unbilled_charges\\\":0,\\\"preferred_currency_code\\\":\\\"USD\\\",\\\"mrr\\\":0,\\\"primary_payment_source_id\\\":\\\"pm_169zeEUk8g8wP69fn\\\",\\\"payment_method\\\":{\\\"object\\\":\\\"payment_method\\\",\\\"type\\\":\\\"card\\\",\\\"reference_id\\\":\\\"tok_AzyfgQUk8g8fn67ZH\\\",\\\"gateway\\\":\\\"chargebee\\\",\\\"gateway_account_id\\\":\\\"gw_16CcWOUiLgm8NHOh\\\",\\\"status\\\":\\\"valid\\\"}},\\\"subscription\\\":{\\\"id\\\":\\\"169zeEUk8g2Qn69cA\\\",\\\"billing_period\\\":1,\\\"billing_period_unit\\\":\\\"month\\\",\\\"customer_id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"status\\\":\\\"active\\\",\\\"current_term_start\\\":1746277432,\\\"current_term_end\\\":1748955832,\\\"next_billing_at\\\":1748955832,\\\"created_at\\\":1746277432,\\\"started_at\\\":1746277432,\\\"activated_at\\\":1746277432,\\\"created_from_ip\\\":\\\"119.73.100.244\\\",\\\"updated_at\\\":1746277432,\\\"has_scheduled_changes\\\":false,\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746277432859,\\\"deleted\\\":false,\\\"object\\\":\\\"subscription\\\",\\\"currency_code\\\":\\\"USD\\\",\\\"subscription_items\\\":[{\\\"item_price_id\\\":\\\"unlimited_1746275957-monthly\\\",\\\"item_type\\\":\\\"plan\\\",\\\"quantity\\\":150,\\\"unit_price\\\":200,\\\"amount\\\":30000,\\\"free_quantity\\\":0,\\\"object\\\":\\\"subscription_item\\\"}],\\\"shipping_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"due_invoices_count\\\":0,\\\"mrr\\\":0,\\\"has_scheduled_advance_invoices\\\":false}}\"','169zeEUk8g8w269fm','66','2025-05-03 13:03:53',NULL,'2025-05-03 13:03:53','2025-05-03 13:03:53'),(3,8,3,'16Bl6gUk8mHrD6KpU','2','active','2025-05-03 13:28:31','2025-06-04 13:28:31',NULL,NULL,'\"{\\\"invoice\\\":{\\\"id\\\":\\\"67\\\",\\\"customer_id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"subscription_id\\\":\\\"16Bl6gUk8mHrD6KpU\\\",\\\"recurring\\\":true,\\\"status\\\":\\\"paid\\\",\\\"price_type\\\":\\\"tax_exclusive\\\",\\\"date\\\":1746278911,\\\"due_date\\\":1746278911,\\\"net_term_days\\\":0,\\\"exchange_rate\\\":1,\\\"total\\\":40000,\\\"amount_paid\\\":40000,\\\"amount_adjusted\\\":0,\\\"write_off_amount\\\":0,\\\"credits_applied\\\":0,\\\"amount_due\\\":0,\\\"paid_at\\\":1746278911,\\\"updated_at\\\":1746278911,\\\"resource_version\\\":1746278911193,\\\"deleted\\\":false,\\\"object\\\":\\\"invoice\\\",\\\"first_invoice\\\":true,\\\"amount_to_collect\\\":0,\\\"round_off_amount\\\":0,\\\"new_sales_amount\\\":40000,\\\"has_advance_charges\\\":false,\\\"currency_code\\\":\\\"USD\\\",\\\"base_currency_code\\\":\\\"USD\\\",\\\"generated_at\\\":1746278911,\\\"is_gifted\\\":false,\\\"term_finalized\\\":true,\\\"channel\\\":\\\"web\\\",\\\"tax\\\":0,\\\"line_items\\\":[{\\\"id\\\":\\\"li_169zeEUk8mLYf6CMe\\\",\\\"date_from\\\":1746278911,\\\"date_to\\\":1748957311,\\\"unit_amount\\\":200,\\\"quantity\\\":200,\\\"amount\\\":40000,\\\"pricing_model\\\":\\\"per_unit\\\",\\\"is_taxed\\\":false,\\\"tax_amount\\\":0,\\\"object\\\":\\\"line_item\\\",\\\"subscription_id\\\":\\\"16Bl6gUk8mHrD6KpU\\\",\\\"customer_id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"description\\\":\\\"unlimited Monthly Price\\\",\\\"entity_type\\\":\\\"plan_item_price\\\",\\\"entity_id\\\":\\\"unlimited_1746275957-monthly\\\",\\\"tax_exempt_reason\\\":\\\"tax_not_configured\\\",\\\"discount_amount\\\":0,\\\"item_level_discount_amount\\\":0}],\\\"sub_total\\\":40000,\\\"linked_payments\\\":[{\\\"txn_id\\\":\\\"txn_169zeEUk8mLZU6CMg\\\",\\\"applied_amount\\\":40000,\\\"applied_at\\\":1746278911,\\\"txn_status\\\":\\\"success\\\",\\\"txn_date\\\":1746278911,\\\"txn_amount\\\":40000}],\\\"applied_credits\\\":[],\\\"adjustment_credit_notes\\\":[],\\\"issued_credit_notes\\\":[],\\\"linked_orders\\\":[],\\\"dunning_attempts\\\":[],\\\"billing_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"shipping_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"site_details_at_creation\\\":{\\\"timezone\\\":\\\"Asia\\\\/Dubai\\\"}},\\\"customer\\\":{\\\"id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"first_name\\\":\\\"TEST\\\",\\\"email\\\":\\\"test19@email.com\\\",\\\"phone\\\":\\\"21322323223\\\",\\\"auto_collection\\\":\\\"on\\\",\\\"net_term_days\\\":0,\\\"allow_direct_debit\\\":false,\\\"created_at\\\":1746277432,\\\"created_from_ip\\\":\\\"119.73.100.244\\\",\\\"taxability\\\":\\\"taxable\\\",\\\"updated_at\\\":1746278910,\\\"pii_cleared\\\":\\\"active\\\",\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746278910960,\\\"deleted\\\":false,\\\"object\\\":\\\"customer\\\",\\\"billing_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"card_status\\\":\\\"valid\\\",\\\"promotional_credits\\\":0,\\\"refundable_credits\\\":0,\\\"excess_payments\\\":0,\\\"unbilled_charges\\\":0,\\\"preferred_currency_code\\\":\\\"USD\\\",\\\"mrr\\\":30000,\\\"primary_payment_source_id\\\":\\\"pm_169zeEUk8g8wP69fn\\\",\\\"payment_method\\\":{\\\"object\\\":\\\"payment_method\\\",\\\"type\\\":\\\"card\\\",\\\"reference_id\\\":\\\"tok_AzyfgQUk8g8fn67ZH\\\",\\\"gateway\\\":\\\"chargebee\\\",\\\"gateway_account_id\\\":\\\"gw_16CcWOUiLgm8NHOh\\\",\\\"status\\\":\\\"valid\\\"}},\\\"subscription\\\":{\\\"id\\\":\\\"16Bl6gUk8mHrD6KpU\\\",\\\"billing_period\\\":1,\\\"billing_period_unit\\\":\\\"month\\\",\\\"customer_id\\\":\\\"169zeEUk8g8w269fm\\\",\\\"status\\\":\\\"active\\\",\\\"current_term_start\\\":1746278911,\\\"current_term_end\\\":1748957311,\\\"next_billing_at\\\":1748957311,\\\"created_at\\\":1746278911,\\\"started_at\\\":1746278911,\\\"activated_at\\\":1746278911,\\\"created_from_ip\\\":\\\"119.73.100.244\\\",\\\"updated_at\\\":1746278911,\\\"has_scheduled_changes\\\":false,\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746278911198,\\\"deleted\\\":false,\\\"object\\\":\\\"subscription\\\",\\\"currency_code\\\":\\\"USD\\\",\\\"subscription_items\\\":[{\\\"item_price_id\\\":\\\"unlimited_1746275957-monthly\\\",\\\"item_type\\\":\\\"plan\\\",\\\"quantity\\\":200,\\\"unit_price\\\":200,\\\"amount\\\":40000,\\\"free_quantity\\\":0,\\\"object\\\":\\\"subscription_item\\\"}],\\\"shipping_address\\\":{\\\"first_name\\\":\\\"TEST\\\",\\\"last_name\\\":\\\"sa\\\",\\\"line1\\\":\\\"Ww White Road\\\",\\\"city\\\":\\\"San Antonio\\\",\\\"state_code\\\":\\\"TX\\\",\\\"state\\\":\\\"Texas\\\",\\\"country\\\":\\\"US\\\",\\\"zip\\\":\\\"78233\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"due_invoices_count\\\":0,\\\"mrr\\\":0,\\\"has_scheduled_advance_invoices\\\":false}}\"','169zeEUk8g8w269fm','67','2025-05-03 13:28:32',NULL,'2025-05-03 13:28:32','2025-05-03 13:28:32'),(4,12,4,'16Bl6gUkEI7bY9bdr','3','active','2025-05-04 12:07:21','2025-06-05 12:07:21',NULL,NULL,'\"{\\\"invoice\\\":{\\\"id\\\":\\\"68\\\",\\\"customer_id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"subscription_id\\\":\\\"16Bl6gUkEI7bY9bdr\\\",\\\"recurring\\\":true,\\\"status\\\":\\\"paid\\\",\\\"price_type\\\":\\\"tax_exclusive\\\",\\\"date\\\":1746360441,\\\"due_date\\\":1746360441,\\\"net_term_days\\\":0,\\\"exchange_rate\\\":1,\\\"total\\\":35000,\\\"amount_paid\\\":35000,\\\"amount_adjusted\\\":0,\\\"write_off_amount\\\":0,\\\"credits_applied\\\":0,\\\"amount_due\\\":0,\\\"paid_at\\\":1746360441,\\\"updated_at\\\":1746360441,\\\"resource_version\\\":1746360441853,\\\"deleted\\\":false,\\\"object\\\":\\\"invoice\\\",\\\"first_invoice\\\":true,\\\"amount_to_collect\\\":0,\\\"round_off_amount\\\":0,\\\"new_sales_amount\\\":35000,\\\"has_advance_charges\\\":false,\\\"currency_code\\\":\\\"USD\\\",\\\"base_currency_code\\\":\\\"USD\\\",\\\"generated_at\\\":1746360441,\\\"is_gifted\\\":false,\\\"term_finalized\\\":true,\\\"channel\\\":\\\"web\\\",\\\"tax\\\":0,\\\"line_items\\\":[{\\\"id\\\":\\\"li_AzyfgQUkEIROc9Rlu\\\",\\\"date_from\\\":1746360441,\\\"date_to\\\":1749038841,\\\"unit_amount\\\":350,\\\"quantity\\\":100,\\\"amount\\\":35000,\\\"pricing_model\\\":\\\"per_unit\\\",\\\"is_taxed\\\":false,\\\"tax_amount\\\":0,\\\"object\\\":\\\"line_item\\\",\\\"subscription_id\\\":\\\"16Bl6gUkEI7bY9bdr\\\",\\\"customer_id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"description\\\":\\\"start up plan Monthly Plan\\\",\\\"entity_type\\\":\\\"plan_item_price\\\",\\\"entity_id\\\":\\\"start_up_plan_1746359310-monthly\\\",\\\"tax_exempt_reason\\\":\\\"tax_not_configured\\\",\\\"discount_amount\\\":0,\\\"item_level_discount_amount\\\":0}],\\\"sub_total\\\":35000,\\\"linked_payments\\\":[{\\\"txn_id\\\":\\\"txn_AzyfgQUkEIRPu9Rlv\\\",\\\"applied_amount\\\":35000,\\\"applied_at\\\":1746360441,\\\"txn_status\\\":\\\"success\\\",\\\"txn_date\\\":1746360441,\\\"txn_amount\\\":35000}],\\\"applied_credits\\\":[],\\\"adjustment_credit_notes\\\":[],\\\"issued_credit_notes\\\":[],\\\"linked_orders\\\":[],\\\"dunning_attempts\\\":[],\\\"billing_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"shipping_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"site_details_at_creation\\\":{\\\"timezone\\\":\\\"Asia\\\\/Dubai\\\"}},\\\"customer\\\":{\\\"id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"first_name\\\":\\\"Greg\\\",\\\"email\\\":\\\"Greg@gmail.com\\\",\\\"phone\\\":\\\"03135351215\\\",\\\"auto_collection\\\":\\\"on\\\",\\\"net_term_days\\\":0,\\\"allow_direct_debit\\\":false,\\\"created_at\\\":1746360441,\\\"created_from_ip\\\":\\\"182.183.8.156\\\",\\\"taxability\\\":\\\"taxable\\\",\\\"updated_at\\\":1746360441,\\\"pii_cleared\\\":\\\"active\\\",\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746360441541,\\\"deleted\\\":false,\\\"object\\\":\\\"customer\\\",\\\"billing_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"card_status\\\":\\\"valid\\\",\\\"promotional_credits\\\":0,\\\"refundable_credits\\\":0,\\\"excess_payments\\\":0,\\\"unbilled_charges\\\":0,\\\"preferred_currency_code\\\":\\\"USD\\\",\\\"mrr\\\":0,\\\"primary_payment_source_id\\\":\\\"pm_AzyfgQUkEIRLj9Rlq\\\",\\\"payment_method\\\":{\\\"object\\\":\\\"payment_method\\\",\\\"type\\\":\\\"card\\\",\\\"reference_id\\\":\\\"tok_AzyfgQUkEIR4n9Rle\\\",\\\"gateway\\\":\\\"chargebee\\\",\\\"gateway_account_id\\\":\\\"gw_16CcWOUiLgm8NHOh\\\",\\\"status\\\":\\\"valid\\\"}},\\\"subscription\\\":{\\\"id\\\":\\\"16Bl6gUkEI7bY9bdr\\\",\\\"billing_period\\\":1,\\\"billing_period_unit\\\":\\\"month\\\",\\\"customer_id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"status\\\":\\\"active\\\",\\\"current_term_start\\\":1746360441,\\\"current_term_end\\\":1749038841,\\\"next_billing_at\\\":1749038841,\\\"created_at\\\":1746360441,\\\"started_at\\\":1746360441,\\\"activated_at\\\":1746360441,\\\"created_from_ip\\\":\\\"182.183.8.156\\\",\\\"updated_at\\\":1746360441,\\\"has_scheduled_changes\\\":false,\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746360441863,\\\"deleted\\\":false,\\\"object\\\":\\\"subscription\\\",\\\"currency_code\\\":\\\"USD\\\",\\\"subscription_items\\\":[{\\\"item_price_id\\\":\\\"start_up_plan_1746359310-monthly\\\",\\\"item_type\\\":\\\"plan\\\",\\\"quantity\\\":100,\\\"unit_price\\\":350,\\\"amount\\\":35000,\\\"free_quantity\\\":0,\\\"object\\\":\\\"subscription_item\\\"}],\\\"shipping_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"due_invoices_count\\\":0,\\\"mrr\\\":0,\\\"has_scheduled_advance_invoices\\\":false}}\"','AzyfgQUkEIRL99Rlp','68','2025-05-04 12:07:23',NULL,'2025-05-04 12:07:23','2025-05-04 12:07:23'),(5,12,5,'169zeEUkEMM7a9VE2','4','active','2025-05-04 12:23:17','2025-06-05 12:23:17',NULL,NULL,'\"{\\\"invoice\\\":{\\\"id\\\":\\\"69\\\",\\\"customer_id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"subscription_id\\\":\\\"169zeEUkEMM7a9VE2\\\",\\\"recurring\\\":true,\\\"status\\\":\\\"paid\\\",\\\"price_type\\\":\\\"tax_exclusive\\\",\\\"date\\\":1746361397,\\\"due_date\\\":1746361397,\\\"net_term_days\\\":0,\\\"exchange_rate\\\":1,\\\"total\\\":30000,\\\"amount_paid\\\":30000,\\\"amount_adjusted\\\":0,\\\"write_off_amount\\\":0,\\\"credits_applied\\\":0,\\\"amount_due\\\":0,\\\"paid_at\\\":1746361397,\\\"updated_at\\\":1746361398,\\\"resource_version\\\":1746361398018,\\\"deleted\\\":false,\\\"object\\\":\\\"invoice\\\",\\\"first_invoice\\\":true,\\\"amount_to_collect\\\":0,\\\"round_off_amount\\\":0,\\\"new_sales_amount\\\":30000,\\\"has_advance_charges\\\":false,\\\"currency_code\\\":\\\"USD\\\",\\\"base_currency_code\\\":\\\"USD\\\",\\\"generated_at\\\":1746361397,\\\"is_gifted\\\":false,\\\"term_finalized\\\":true,\\\"channel\\\":\\\"web\\\",\\\"tax\\\":0,\\\"line_items\\\":[{\\\"id\\\":\\\"li_169zeEUkEMS9I9VGN\\\",\\\"date_from\\\":1746361397,\\\"date_to\\\":1749039797,\\\"unit_amount\\\":200,\\\"quantity\\\":150,\\\"amount\\\":30000,\\\"pricing_model\\\":\\\"per_unit\\\",\\\"is_taxed\\\":false,\\\"tax_amount\\\":0,\\\"object\\\":\\\"line_item\\\",\\\"subscription_id\\\":\\\"169zeEUkEMM7a9VE2\\\",\\\"customer_id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"description\\\":\\\"enterprise plan Monthly Plan\\\",\\\"entity_type\\\":\\\"plan_item_price\\\",\\\"entity_id\\\":\\\"enterprise_plan_1746359393-monthly\\\",\\\"tax_exempt_reason\\\":\\\"tax_not_configured\\\",\\\"discount_amount\\\":0,\\\"item_level_discount_amount\\\":0}],\\\"sub_total\\\":30000,\\\"linked_payments\\\":[{\\\"txn_id\\\":\\\"txn_169zeEUkEMSA99VGO\\\",\\\"applied_amount\\\":30000,\\\"applied_at\\\":1746361397,\\\"txn_status\\\":\\\"success\\\",\\\"txn_date\\\":1746361397,\\\"txn_amount\\\":30000}],\\\"applied_credits\\\":[],\\\"adjustment_credit_notes\\\":[],\\\"issued_credit_notes\\\":[],\\\"linked_orders\\\":[],\\\"dunning_attempts\\\":[],\\\"billing_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"shipping_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"site_details_at_creation\\\":{\\\"timezone\\\":\\\"Asia\\\\/Dubai\\\"}},\\\"customer\\\":{\\\"id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"first_name\\\":\\\"Greg\\\",\\\"email\\\":\\\"Greg@gmail.com\\\",\\\"phone\\\":\\\"03135351215\\\",\\\"auto_collection\\\":\\\"on\\\",\\\"net_term_days\\\":0,\\\"allow_direct_debit\\\":false,\\\"created_at\\\":1746360441,\\\"created_from_ip\\\":\\\"182.183.8.156\\\",\\\"taxability\\\":\\\"taxable\\\",\\\"updated_at\\\":1746361397,\\\"pii_cleared\\\":\\\"active\\\",\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746361397794,\\\"deleted\\\":false,\\\"object\\\":\\\"customer\\\",\\\"billing_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"billing_address\\\"},\\\"card_status\\\":\\\"valid\\\",\\\"promotional_credits\\\":0,\\\"refundable_credits\\\":0,\\\"excess_payments\\\":0,\\\"unbilled_charges\\\":0,\\\"preferred_currency_code\\\":\\\"USD\\\",\\\"mrr\\\":35000,\\\"primary_payment_source_id\\\":\\\"pm_AzyfgQUkEIRLj9Rlq\\\",\\\"payment_method\\\":{\\\"object\\\":\\\"payment_method\\\",\\\"type\\\":\\\"card\\\",\\\"reference_id\\\":\\\"tok_AzyfgQUkEIR4n9Rle\\\",\\\"gateway\\\":\\\"chargebee\\\",\\\"gateway_account_id\\\":\\\"gw_16CcWOUiLgm8NHOh\\\",\\\"status\\\":\\\"valid\\\"}},\\\"subscription\\\":{\\\"id\\\":\\\"169zeEUkEMM7a9VE2\\\",\\\"billing_period\\\":1,\\\"billing_period_unit\\\":\\\"month\\\",\\\"customer_id\\\":\\\"AzyfgQUkEIRL99Rlp\\\",\\\"status\\\":\\\"active\\\",\\\"current_term_start\\\":1746361397,\\\"current_term_end\\\":1749039797,\\\"next_billing_at\\\":1749039797,\\\"created_at\\\":1746361397,\\\"started_at\\\":1746361397,\\\"activated_at\\\":1746361397,\\\"created_from_ip\\\":\\\"182.183.8.156\\\",\\\"updated_at\\\":1746361398,\\\"has_scheduled_changes\\\":false,\\\"channel\\\":\\\"web\\\",\\\"resource_version\\\":1746361398024,\\\"deleted\\\":false,\\\"object\\\":\\\"subscription\\\",\\\"currency_code\\\":\\\"USD\\\",\\\"subscription_items\\\":[{\\\"item_price_id\\\":\\\"enterprise_plan_1746359393-monthly\\\",\\\"item_type\\\":\\\"plan\\\",\\\"quantity\\\":150,\\\"unit_price\\\":200,\\\"amount\\\":30000,\\\"free_quantity\\\":0,\\\"object\\\":\\\"subscription_item\\\"}],\\\"shipping_address\\\":{\\\"first_name\\\":\\\"Greg\\\",\\\"last_name\\\":\\\"schibbs\\\",\\\"line1\\\":\\\"yedyeye\\\",\\\"city\\\":\\\"islamabad\\\",\\\"state\\\":\\\"pakistan\\\",\\\"country\\\":\\\"PK\\\",\\\"validation_status\\\":\\\"not_validated\\\",\\\"object\\\":\\\"shipping_address\\\"},\\\"due_invoices_count\\\":0,\\\"mrr\\\":0,\\\"has_scheduled_advance_invoices\\\":false}}\"','AzyfgQUkEIRL99Rlp','69','2025-05-04 12:23:19',NULL,'2025-05-04 12:23:19','2025-05-04 12:23:19');
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `domain_forwarding_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_address` text COLLATE utf8mb4_unicode_ci,
  `role_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` smallint DEFAULT '1' COMMENT '0:inactive, 1:active',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subscription_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subscription_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plan_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_plan_id_foreign` (`plan_id`),
  CONSTRAINT `users_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Super Admin','super.admin@5dsolutions.ae',NULL,NULL,'1',NULL,'$2y$12$UEyXzV8tnoukYplR3GGWxuxAQSZ9DuCcHE7q35ywiMf6MZ3YqAvuC',1,'m3wOBn8ctMVv67WDTBPFcFyG2DKAm9i0NhAUZkBP1T9KCPuzwUietr7TbCt4','2025-05-03 12:27:08','2025-05-03 12:27:08',NULL,NULL,NULL,NULL),(2,'Customer User','customer@email.com',NULL,NULL,'3',NULL,'$2y$12$ASuN2Pc340r2CTxsbLkCmeSTS/N1G7e0kMGeO.jpKdYTtSg2hwvUm',1,NULL,'2025-05-03 12:27:08','2025-05-03 12:27:08',NULL,NULL,NULL,NULL),(3,'Contractor User','contractor@email.com',NULL,NULL,'4',NULL,'$2y$12$UmmAOOWyTxW74IfGAkBtC.jEKQYwzLPD3ouWcnQkjZ.620C1uhPzy',1,NULL,'2025-05-03 12:27:09','2025-05-03 12:27:09',NULL,NULL,NULL,NULL),(4,'Moderator','mod@email.com',NULL,NULL,'5',NULL,'$2y$12$igyACzv8.LsiIU73gU8Egu4vy2TwdvFrPHeHZhO7YRMSPNlw//pwe',1,NULL,'2025-05-03 12:27:09','2025-05-03 12:27:09',NULL,NULL,NULL,NULL),(5,'Sub Admin','sub-admin@email.com',NULL,NULL,'2',NULL,'$2y$12$P.NAv.loZMYdPaaRzzfl1utw2M1tPQLLcJuTxNlGM0Md2k4njmSbq',1,NULL,'2025-05-03 12:27:09','2025-05-03 12:27:09',NULL,NULL,NULL,NULL),(6,'farooq','hamzaashfaq@5dsolutions.ae',NULL,NULL,'3',NULL,'$2y$12$kb0LU9jXZrhNf3rv4HLW0uwRs7DRSQ7mrT49cc6bUcqce2liPVIui',1,NULL,'2025-05-03 12:42:00','2025-05-03 12:42:00','03135351215',NULL,NULL,NULL),(7,'farooq','hamzaashfaq1@5dsolutions.ae',NULL,NULL,'3',NULL,'$2y$12$2EKDYI59abXiM29P/SgmKeDTNffFCFD7NzAK3XTSOKJ7PUGWqPdH6',1,NULL,'2025-05-03 12:42:37','2025-05-03 12:42:37','03135351215',NULL,NULL,NULL),(8,'TEST','test19@email.com',NULL,NULL,'3',NULL,'$2y$12$MQLEj7qU.pHPt7sq4fbqNuGGvTwCv046tpiPj.VAxdH.tZJY/L.oG',1,NULL,'2025-05-03 12:50:56','2025-05-03 13:28:32','21322323223','16Bl6gUk8mHrD6KpU','active',NULL),(9,'contractor2','contractor2@email.com',NULL,NULL,'4',NULL,'$2y$12$YSFjAWO3cSuVw4BdFdtYUe2wbaUR7dM3EhfvFY5UnACu9IaQTbHB2',1,NULL,'2025-05-03 13:06:07','2025-05-03 13:06:07','21212121',NULL,NULL,NULL),(10,'farooqq','abcdefg@gmail.com',NULL,NULL,'1',NULL,'$2y$12$d8aWXzuYH1YcbeNEm7pYP.qKszhTBb0N/5Xn5EKs78qXW4YAFNyuu',1,NULL,'2025-05-03 13:40:48','2025-05-03 13:40:48',NULL,NULL,NULL,NULL),(11,'jack','jack88@gmail.com',NULL,NULL,'1',NULL,'$2y$12$uSS32U6C10NJDCopm76V6.Qc5Fx1mRX.UQl8rE5z7OpNzsNyRfuCy',0,NULL,'2025-05-04 11:43:05','2025-05-04 11:44:26',NULL,NULL,NULL,NULL),(12,'Greg','Greg@gmail.com',NULL,NULL,'3',NULL,'$2y$12$b0Rtim5G2X/p7vt0y70Fs.X/i2GT5mvQbdxPs77qjU5x2Kr8SAtti',1,NULL,'2025-05-04 11:54:30','2025-05-04 12:23:19','03135351215','169zeEUkEMM7a9VE2','active',4),(13,'ali','ali@gmail.com',NULL,NULL,'4',NULL,'$2y$12$oVITD68sZWYJJOWFONxlTeCl2iGXOvcrDsEpTyWEk.MARMIPofqCC',1,NULL,'2025-05-04 12:11:01','2025-05-04 12:11:01','03135351215',NULL,NULL,NULL),(14,'MOD','mod@gmail.com',NULL,NULL,'1',NULL,'$2y$12$3KPFCKaplDEy4cuFg/q0hen.NfsuStV0aulWIC/kotdXs/7xMGZsC',1,NULL,'2025-05-04 12:27:49','2025-05-04 12:27:49',NULL,NULL,NULL,NULL);
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

-- Dump completed on 2025-05-05  4:55:34
