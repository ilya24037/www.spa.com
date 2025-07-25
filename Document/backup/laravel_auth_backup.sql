-- MySQL dump 10.13  Distrib 8.4.5, for Win64 (x86_64)
--
-- Host: localhost    Database: laravel_auth
-- ------------------------------------------------------
-- Server version	8.4.5

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
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_id` bigint unsigned NOT NULL,
  `master_profile_id` bigint unsigned NOT NULL,
  `service_id` bigint unsigned NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duration_minutes` int NOT NULL,
  `location_type` enum('home','salon') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'home',
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Москва',
  `district` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metro` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_details` text COLLATE utf8mb4_unicode_ci,
  `service_price` decimal(10,2) NOT NULL,
  `travel_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','online','transfer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `payment_status` enum('pending','paid','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','confirmed','in_progress','completed','cancelled','no_show') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `client_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_comment` text COLLATE utf8mb4_unicode_ci,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancelled_by` bigint unsigned DEFAULT NULL,
  `reminder_sent` tinyint(1) NOT NULL DEFAULT '0',
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  `review_requested` tinyint(1) NOT NULL DEFAULT '0',
  `review_requested_at` timestamp NULL DEFAULT NULL,
  `extra_data` json DEFAULT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'website',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bookings_booking_number_unique` (`booking_number`),
  KEY `bookings_service_id_foreign` (`service_id`),
  KEY `bookings_cancelled_by_foreign` (`cancelled_by`),
  KEY `bookings_booking_number_index` (`booking_number`),
  KEY `bookings_client_id_status_index` (`client_id`,`status`),
  KEY `bookings_master_profile_id_status_index` (`master_profile_id`,`status`),
  KEY `bookings_booking_date_start_time_index` (`booking_date`,`start_time`),
  KEY `bookings_status_index` (`status`),
  KEY `bookings_payment_status_index` (`payment_status`),
  KEY `bookings_master_profile_id_booking_date_index` (`master_profile_id`,`booking_date`),
  CONSTRAINT `bookings_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`),
  CONSTRAINT `bookings_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `bookings_master_profile_id_foreign` FOREIGN KEY (`master_profile_id`) REFERENCES `master_profiles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `bookings_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
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
-- Table structure for table `favorites`
--

DROP TABLE IF EXISTS `favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `favorites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `master_profile_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `favorites_user_id_master_profile_id_unique` (`user_id`,`master_profile_id`),
  KEY `favorites_user_id_index` (`user_id`),
  KEY `favorites_master_profile_id_index` (`master_profile_id`),
  CONSTRAINT `favorites_master_profile_id_foreign` FOREIGN KEY (`master_profile_id`) REFERENCES `master_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `favorites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favorites`
--

LOCK TABLES `favorites` WRITE;
/*!40000 ALTER TABLE `favorites` DISABLE KEYS */;
/*!40000 ALTER TABLE `favorites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
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
-- Table structure for table `massage_categories`
--

DROP TABLE IF EXISTS `massage_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `massage_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `services_count` int NOT NULL DEFAULT '0',
  `is_popular` tinyint(1) NOT NULL DEFAULT '0',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `meta_keywords` text COLLATE utf8mb4_unicode_ci,
  `properties` json DEFAULT NULL,
  `min_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `avg_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `massage_categories_slug_unique` (`slug`),
  KEY `massage_categories_slug_index` (`slug`),
  KEY `massage_categories_parent_id_index` (`parent_id`),
  KEY `massage_categories_sort_order_index` (`sort_order`),
  KEY `massage_categories_is_active_index` (`is_active`),
  KEY `massage_categories_is_popular_index` (`is_popular`),
  CONSTRAINT `massage_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `massage_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `massage_categories`
--

LOCK TABLES `massage_categories` WRITE;
/*!40000 ALTER TABLE `massage_categories` DISABLE KEYS */;
INSERT INTO `massage_categories` VALUES (1,'Классический массаж','klassicheskiy','Традиционный расслабляющий массаж всего тела','classic',NULL,NULL,0,1,0,0,1,NULL,NULL,NULL,NULL,2000.00,2000.00,'2025-06-16 12:01:35','2025-07-03 06:58:11'),(2,'Спортивный массаж','sportivnyy','Массаж для спортсменов и активных людей','sport',NULL,NULL,0,2,0,0,1,NULL,NULL,NULL,NULL,3000.00,3500.00,'2025-06-16 12:01:35','2025-06-16 12:01:35'),(3,'Тайский массаж','tayskiy','Традиционный тайский массаж с элементами растяжки','thai',NULL,NULL,0,4,0,0,1,NULL,NULL,NULL,NULL,2200.00,2800.00,'2025-06-16 12:01:35','2025-06-16 12:01:36'),(4,'Лечебный массаж','lechebnyy','Медицинский массаж для лечения и профилактики','medical',NULL,NULL,0,1,0,0,1,NULL,NULL,NULL,NULL,4500.00,4500.00,'2025-06-16 12:01:35','2025-06-16 12:01:36'),(5,'Антицеллюлитный','antitsellyulitnyy','Массаж для коррекции фигуры','anticellulite',NULL,NULL,0,3,0,0,1,NULL,NULL,NULL,NULL,3000.00,3233.33,'2025-06-16 12:01:35','2025-06-16 12:01:36'),(6,'Расслабляющий','rasslablyayushchiy','Нежный массаж для снятия стресса','relax',NULL,NULL,0,1,0,0,1,NULL,NULL,NULL,NULL,3500.00,3500.00,'2025-06-16 12:01:35','2025-06-16 12:01:36'),(7,'Массаж лица','massazh-litsa','Косметический массаж лица и шеи','face',NULL,NULL,0,0,0,0,1,NULL,NULL,NULL,NULL,0.00,0.00,'2025-06-16 12:01:35','2025-06-16 12:01:35'),(8,'Лимфодренажный','limfodrenazhnyy','Массаж для улучшения лимфотока','lymph',NULL,NULL,0,4,0,0,1,NULL,NULL,NULL,NULL,2000.00,2800.00,'2025-06-16 12:01:35','2025-06-16 12:01:36');
/*!40000 ALTER TABLE `massage_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_photos`
--

DROP TABLE IF EXISTS `master_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_photos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `master_profile_id` bigint unsigned NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Путь к файлу фото',
  `is_main` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Главное фото',
  `order` int NOT NULL DEFAULT '0' COMMENT 'Порядок сортировки',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `master_photos_master_profile_id_is_main_index` (`master_profile_id`,`is_main`),
  KEY `master_photos_order_index` (`order`),
  CONSTRAINT `master_photos_master_profile_id_foreign` FOREIGN KEY (`master_profile_id`) REFERENCES `master_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_photos`
--

LOCK TABLES `master_photos` WRITE;
/*!40000 ALTER TABLE `master_photos` DISABLE KEYS */;
/*!40000 ALTER TABLE `master_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_profiles`
--

DROP TABLE IF EXISTS `master_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `display_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telegram` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `show_contacts` tinyint(1) NOT NULL DEFAULT '0',
  `experience_years` int NOT NULL DEFAULT '0',
  `certificates` json DEFAULT NULL,
  `education` json DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Москва',
  `district` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metro_station` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `home_service` tinyint(1) NOT NULL DEFAULT '1',
  `salon_service` tinyint(1) NOT NULL DEFAULT '0',
  `salon_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `reviews_count` int NOT NULL DEFAULT '0',
  `completed_bookings` int NOT NULL DEFAULT '0',
  `views_count` int NOT NULL DEFAULT '0',
  `status` enum('draft','pending','active','blocked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `is_premium` tinyint(1) NOT NULL DEFAULT '0',
  `premium_until` timestamp NULL DEFAULT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_profiles_slug_unique` (`slug`),
  KEY `master_profiles_user_id_foreign` (`user_id`),
  KEY `master_profiles_slug_index` (`slug`),
  KEY `master_profiles_city_index` (`city`),
  KEY `master_profiles_district_index` (`district`),
  KEY `master_profiles_status_index` (`status`),
  KEY `master_profiles_rating_index` (`rating`),
  KEY `master_profiles_is_premium_index` (`is_premium`),
  KEY `master_profiles_latitude_longitude_index` (`latitude`,`longitude`),
  KEY `master_profiles_is_active_index` (`is_active`),
  CONSTRAINT `master_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_profiles`
--

LOCK TABLES `master_profiles` WRITE;
/*!40000 ALTER TABLE `master_profiles` DISABLE KEYS */;
INSERT INTO `master_profiles` VALUES (1,1,'Анна Петрова','anna-petrova','Сертифицированный массажист с 8-летним опытом. Специализируюсь на классическом и лечебном массаже.','https://i.pravatar.cc/300?img=1','+7 (999) 123-45-67','+7 (999) 123-45-67','@anna-petrova',1,8,'[\"cert1.jpg\", \"cert2.jpg\"]',NULL,'Москва','Центральный','Чистые пруды',1,0,NULL,55.76590000,37.64440000,4.80,1,127,283,1534,'active',1,1,NULL,'Анна Петрова • Спортивный массаж • Центральный, Москва','✓ Верифицированный массажист Анна Петрова. у метро Чистые пруды. Услуги: Спортивный массаж, Тайский массаж, Лимфодренажный. Опыт 8 лет. Рейтинг 4.80 ★★★★★ (1...','2025-06-16 12:01:35','2025-07-11 07:23:50'),(2,2,'Михаил Иванов','mixail-ivanov','Мастер спортивного и восстановительного массажа. Работаю с профессиональными спортсменами.','https://i.pravatar.cc/300?img=3','+7 (999) 234-56-78','+7 (999) 234-56-78','@mixail-ivanov',1,10,'[\"cert3.jpg\", \"cert4.jpg\", \"cert5.jpg\"]',NULL,'Москва','Северный','Водный стадион',1,0,'ул. Примерная, д. 40',55.83960000,37.48780000,4.90,1,89,406,4607,'active',1,0,NULL,'Михаил Иванов • Спортивный массаж • Северный, Москва','✓ Верифицированный массажист Михаил Иванов. у метро Водный стадион. Услуги: Спортивный массаж, Антицеллюлитный, Лимфодренажный. Опыт 10 лет. Рейтинг 4.90 ★★★...','2025-06-16 12:01:35','2025-07-03 10:12:46'),(3,3,'Елена Сидорова','elena-sidorova','Специалист по тайскому и релакс-массажу. Обучалась в Таиланде.','https://i.pravatar.cc/300?img=5','+7 (999) 345-67-89','+7 (999) 345-67-89','@elena-sidorova',1,5,'[\"cert6.jpg\"]',NULL,'Москва','Южный','Автозаводская',1,0,'ул. Примерная, д. 41',55.70820000,37.65740000,4.70,1,156,231,4576,'active',1,1,NULL,'Елена Сидорова • Тайский массаж • Южный, Москва','✓ Верифицированный массажист Елена Сидорова. у метро Автозаводская. Услуги: Тайский массаж, Лимфодренажный, Антицеллюлитный. Опыт 5 лет. Рейтинг 4.70 ★★★★★ (...','2025-06-16 12:01:36','2025-07-11 07:11:36'),(4,4,'Дмитрий Козлов','dmitrii-kozlov','Профессиональный массажист-реабилитолог. Помогаю восстановиться после травм.','https://i.pravatar.cc/300?img=8','+7 (999) 456-78-90','+7 (999) 456-78-90','@dmitrii-kozlov',1,15,'[\"cert7.jpg\", \"cert8.jpg\", \"cert9.jpg\", \"cert10.jpg\"]',NULL,'Москва','Западный','Кунцевская',1,1,'ул. Примерная, д. 15',55.73070000,37.44610000,4.95,1,234,369,2053,'active',1,1,NULL,'Дмитрий Козлов • Расслабляющий • Западный, Москва','✓ Верифицированный массажист Дмитрий Козлов. у метро Кунцевская. Услуги: Расслабляющий, Тайский массаж, Лечебный массаж. Опыт 15 лет. Рейтинг 4.95 ★★★★★ (234...','2025-06-16 12:01:36','2025-07-03 10:20:49'),(5,5,'Ольга Николаева','olga-nikolaeva','Мастер антицеллюлитного и лимфодренажного массажа. Индивидуальный подход к каждому клиенту.','https://i.pravatar.cc/300?img=9','+7 (999) 567-89-01','+7 (999) 567-89-01','@olga-nikolaeva',1,12,'[\"cert11.jpg\", \"cert12.jpg\"]',NULL,'Москва','Восточный','Партизанская',1,1,NULL,55.79440000,37.74950000,4.60,1,203,189,4453,'active',1,0,NULL,'Ольга Николаева • Тайский массаж • Восточный, Москва','✓ Верифицированный массажист Ольга Николаева. у метро Партизанская. Услуги: Тайский массаж, Антицеллюлитный, Лимфодренажный. Опыт 12 лет. Рейтинг 4.60 ★★★★★ ...','2025-06-16 12:01:36','2025-07-11 07:25:55'),(7,5,'Анна','anna',NULL,NULL,'+7 999 123-45-67','+7 999 123-45-67',NULL,0,5,NULL,NULL,'Москва',NULL,NULL,1,0,NULL,NULL,NULL,0.00,1,0,0,1,'active',0,0,NULL,'Анна • Классический массаж • Москва','Массажист Анна. в городе Москва. Услуги: Классический массаж. Опыт 5 лет. Цены от 2 000 ₽.','2025-07-03 06:58:11','2025-07-03 09:58:45'),(9,6,'Анна','anna-2',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Москва',NULL,NULL,1,0,NULL,NULL,NULL,0.00,1,0,0,0,'draft',0,0,NULL,'Анна • Массажист • Москва','Массажист Анна. в городе Москва.','2025-07-03 07:41:16','2025-07-03 07:55:02'),(10,6,'Анна','anna-3',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Москва',NULL,NULL,1,0,NULL,NULL,NULL,0.00,1,0,0,1,'active',0,0,NULL,'Анна • Массажист • Москва','Массажист Анна. в городе Москва.','2025-07-03 07:41:16','2025-07-11 06:58:20'),(11,7,'Анна','anna-4',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Москва',NULL,NULL,1,0,NULL,NULL,NULL,0.00,1,0,0,0,'draft',0,0,NULL,'Анна • Массажист • Москва','Массажист Анна. в городе Москва.','2025-07-03 07:41:16','2025-07-03 07:55:02'),(12,7,'Анна','anna-5',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Москва',NULL,NULL,1,0,NULL,NULL,NULL,0.00,1,0,0,0,'active',0,0,NULL,'Анна • Массажист • Москва','Массажист Анна. в городе Москва.','2025-07-03 07:41:16','2025-07-03 07:55:02'),(13,8,'Анна','anna-6',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Москва',NULL,NULL,1,0,NULL,NULL,NULL,0.00,1,0,0,0,'draft',0,0,NULL,'Анна • Массажист • Москва','Массажист Анна. в городе Москва.','2025-07-03 07:41:17','2025-07-03 07:55:02'),(14,8,'Анна','anna-7',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Москва',NULL,NULL,1,0,NULL,NULL,NULL,0.00,1,0,0,1,'active',0,0,NULL,'Анна • Массажист • Москва','Массажист Анна. в городе Москва.','2025-07-03 07:41:17','2025-07-03 10:12:53'),(15,9,'Мария Иванова','mariia-ivanova',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Москва',NULL,NULL,1,0,NULL,NULL,NULL,0.00,1,0,0,0,'draft',0,0,NULL,'Мария Иванова • Массажист • Москва','Массажист Мария Иванова. в городе Москва.','2025-07-03 07:41:17','2025-07-03 07:55:02'),(16,9,'Мария Иванова','mariia-ivanova-2',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Москва',NULL,NULL,1,0,NULL,NULL,NULL,0.00,1,0,0,0,'active',0,0,NULL,'Мария Иванова • Массажист • Москва','Массажист Мария Иванова. в городе Москва.','2025-07-03 07:41:17','2025-07-03 07:55:02'),(17,10,'Елена','elena',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Москва',NULL,NULL,1,0,NULL,NULL,NULL,0.00,1,0,0,0,'draft',0,0,NULL,'Елена • Массажист • Москва','Массажист Елена. в городе Москва.','2025-07-03 07:41:17','2025-07-03 07:55:02'),(18,10,'Елена','elena-2',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Москва',NULL,NULL,1,0,NULL,NULL,NULL,0.00,1,0,0,0,'active',0,0,NULL,'Елена • Массажист • Москва','Массажист Елена. в городе Москва.','2025-07-03 07:41:17','2025-07-03 07:55:02'),(19,11,'Ольга Петрова','olga-petrova',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Москва',NULL,NULL,1,0,NULL,NULL,NULL,0.00,1,0,0,0,'draft',0,0,NULL,'Ольга Петрова • Массажист • Москва','Массажист Ольга Петрова. в городе Москва.','2025-07-03 07:41:18','2025-07-03 07:55:02'),(20,11,'Ольга Петрова','olga-petrova-2',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Москва',NULL,NULL,1,0,NULL,NULL,NULL,0.00,1,0,0,0,'active',0,0,NULL,'Ольга Петрова • Массажист • Москва','Массажист Ольга Петрова. в городе Москва.','2025-07-03 07:41:18','2025-07-03 07:55:02'),(21,12,'Наталья','natalia',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Москва',NULL,NULL,1,0,NULL,NULL,NULL,0.00,1,0,0,0,'draft',0,0,NULL,'Наталья • Массажист • Москва','Массажист Наталья. в городе Москва.','2025-07-03 07:41:18','2025-07-03 07:55:02'),(22,12,'Наталья','natalia-2',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Москва',NULL,NULL,1,0,NULL,NULL,NULL,0.00,1,0,0,0,'active',0,0,NULL,'Наталья • Массажист • Москва','Массажист Наталья. в городе Москва.','2025-07-03 07:41:18','2025-07-03 07:55:02');
/*!40000 ALTER TABLE `master_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_subscriptions`
--

DROP TABLE IF EXISTS `master_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `master_profile_id` bigint unsigned NOT NULL,
  `payment_plan_id` bigint unsigned NOT NULL,
  `billing_period` enum('monthly','quarterly','yearly') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_trial` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('active','expired','cancelled','suspended','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `auto_renewal` tinyint(1) NOT NULL DEFAULT '1',
  `next_payment_date` timestamp NULL DEFAULT NULL,
  `failed_payment_attempts` int NOT NULL DEFAULT '0',
  `bookings_used` int NOT NULL DEFAULT '0',
  `boosts_used` int NOT NULL DEFAULT '0',
  `highlights_used` int NOT NULL DEFAULT '0',
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promo_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `master_subscriptions_payment_plan_id_foreign` (`payment_plan_id`),
  KEY `master_subscriptions_master_profile_id_status_index` (`master_profile_id`,`status`),
  KEY `master_subscriptions_status_end_date_index` (`status`,`end_date`),
  KEY `master_subscriptions_end_date_index` (`end_date`),
  KEY `master_subscriptions_next_payment_date_index` (`next_payment_date`),
  CONSTRAINT `master_subscriptions_master_profile_id_foreign` FOREIGN KEY (`master_profile_id`) REFERENCES `master_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `master_subscriptions_payment_plan_id_foreign` FOREIGN KEY (`payment_plan_id`) REFERENCES `payment_plans` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_subscriptions`
--

LOCK TABLES `master_subscriptions` WRITE;
/*!40000 ALTER TABLE `master_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `master_subscriptions` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000001_create_cache_table',1),(2,'0001_01_01_000002_create_jobs_table',1),(3,'2024_01_01_000000_create_users_table',1),(4,'2025_06_08_190102_create_personal_access_tokens_table',1),(5,'2025_06_11_211948_create_master_profiles_table',1),(6,'2025_06_11_212210_create_massage_categories_table',1),(7,'2025_06_11_212434_create_services_table',1),(8,'2025_06_11_213441_create_work_zones_table',1),(9,'2025_06_11_213631_create_schedules_table',1),(10,'2025_06_11_213749_create_schedule_exceptions_table',1),(11,'2025_06_11_213920_create_bookings_table',1),(12,'2025_06_11_214141_create_reviews_table',1),(13,'2025_06_11_214147_create_review_reactions_table',1),(14,'2025_06_11_214439_create_payment_plans_table',1),(15,'2025_06_11_214445_create_master_subscriptions_table',1),(16,'2025_06_12_202427_add_coordinates_to_master_profiles_table',1),(17,'2025_06_15_164446_add_is_active_to_master_profiles_table',1),(18,'2025_07_01_081421_create_favorites_table',2),(19,'2025_07_01_084127_create_master_photos_table',3);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
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
-- Table structure for table `payment_plans`
--

DROP TABLE IF EXISTS `payment_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` enum('free','basic','professional','premium') COLLATE utf8mb4_unicode_ci NOT NULL,
  `price_monthly` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price_quarterly` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price_yearly` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_quarterly` int NOT NULL DEFAULT '0',
  `discount_yearly` int NOT NULL DEFAULT '0',
  `bookings_limit` int NOT NULL DEFAULT '-1',
  `services_limit` int NOT NULL DEFAULT '-1',
  `photos_limit` int NOT NULL DEFAULT '5',
  `priority_in_search` int NOT NULL DEFAULT '0',
  `has_badge` tinyint(1) NOT NULL DEFAULT '0',
  `has_analytics` tinyint(1) NOT NULL DEFAULT '0',
  `has_promotion` tinyint(1) NOT NULL DEFAULT '0',
  `has_instant_booking` tinyint(1) NOT NULL DEFAULT '1',
  `has_calendar_sync` tinyint(1) NOT NULL DEFAULT '0',
  `has_sms_notifications` tinyint(1) NOT NULL DEFAULT '0',
  `has_priority_support` tinyint(1) NOT NULL DEFAULT '0',
  `has_custom_url` tinyint(1) NOT NULL DEFAULT '0',
  `has_remove_ads` tinyint(1) NOT NULL DEFAULT '0',
  `boost_days_monthly` int NOT NULL DEFAULT '0',
  `highlight_days_monthly` int NOT NULL DEFAULT '0',
  `platform_fee_percentage` decimal(5,2) NOT NULL DEFAULT '20.00',
  `min_platform_fee` decimal(8,2) NOT NULL DEFAULT '100.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_popular` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `trial_days` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_plans_slug_unique` (`slug`),
  KEY `payment_plans_slug_index` (`slug`),
  KEY `payment_plans_type_index` (`type`),
  KEY `payment_plans_is_active_sort_order_index` (`is_active`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_plans`
--

LOCK TABLES `payment_plans` WRITE;
/*!40000 ALTER TABLE `payment_plans` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_plans` ENABLE KEYS */;
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
-- Table structure for table `review_reactions`
--

DROP TABLE IF EXISTS `review_reactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_reactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `review_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `type` enum('helpful','not_helpful') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `review_reactions_review_id_user_id_unique` (`review_id`,`user_id`),
  KEY `review_reactions_user_id_foreign` (`user_id`),
  KEY `review_reactions_review_id_type_index` (`review_id`,`type`),
  CONSTRAINT `review_reactions_review_id_foreign` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `review_reactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `review_reactions`
--

LOCK TABLES `review_reactions` WRITE;
/*!40000 ALTER TABLE `review_reactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `review_reactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `client_id` bigint unsigned NOT NULL,
  `master_profile_id` bigint unsigned NOT NULL,
  `service_id` bigint unsigned NOT NULL,
  `rating_overall` tinyint unsigned NOT NULL,
  `rating_quality` tinyint unsigned NOT NULL,
  `rating_punctuality` tinyint unsigned NOT NULL,
  `rating_communication` tinyint unsigned NOT NULL,
  `rating_price_quality` tinyint unsigned NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `pros` text COLLATE utf8mb4_unicode_ci,
  `cons` text COLLATE utf8mb4_unicode_ci,
  `would_recommend` tinyint(1) NOT NULL DEFAULT '1',
  `would_book_again` tinyint(1) NOT NULL DEFAULT '1',
  `status` enum('pending','approved','rejected','hidden') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `rejection_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `moderated_at` timestamp NULL DEFAULT NULL,
  `moderated_by` bigint unsigned DEFAULT NULL,
  `master_response` text COLLATE utf8mb4_unicode_ci,
  `master_responded_at` timestamp NULL DEFAULT NULL,
  `helpful_count` int NOT NULL DEFAULT '0',
  `not_helpful_count` int NOT NULL DEFAULT '0',
  `is_verified_booking` tinyint(1) NOT NULL DEFAULT '1',
  `is_anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `has_photos` tinyint(1) NOT NULL DEFAULT '0',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reviews_booking_id_unique` (`booking_id`),
  UNIQUE KEY `reviews_slug_unique` (`slug`),
  KEY `reviews_client_id_foreign` (`client_id`),
  KEY `reviews_moderated_by_foreign` (`moderated_by`),
  KEY `reviews_master_profile_id_status_rating_overall_index` (`master_profile_id`,`status`,`rating_overall`),
  KEY `reviews_service_id_status_index` (`service_id`,`status`),
  KEY `reviews_status_index` (`status`),
  KEY `reviews_rating_overall_index` (`rating_overall`),
  KEY `reviews_is_featured_index` (`is_featured`),
  KEY `reviews_created_at_index` (`created_at`),
  CONSTRAINT `reviews_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_master_profile_id_foreign` FOREIGN KEY (`master_profile_id`) REFERENCES `master_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_moderated_by_foreign` FOREIGN KEY (`moderated_by`) REFERENCES `users` (`id`),
  CONSTRAINT `reviews_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedule_exceptions`
--

DROP TABLE IF EXISTS `schedule_exceptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedule_exceptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `master_profile_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `type` enum('holiday','vacation','sick_leave','day_off','busy','special') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `is_working` tinyint(1) NOT NULL DEFAULT '0',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `schedule_exceptions_master_profile_id_date_index` (`master_profile_id`,`date`),
  KEY `schedule_exceptions_master_profile_id_type_index` (`master_profile_id`,`type`),
  KEY `schedule_exceptions_date_index` (`date`),
  CONSTRAINT `schedule_exceptions_master_profile_id_foreign` FOREIGN KEY (`master_profile_id`) REFERENCES `master_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedule_exceptions`
--

LOCK TABLES `schedule_exceptions` WRITE;
/*!40000 ALTER TABLE `schedule_exceptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `schedule_exceptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedules`
--

DROP TABLE IF EXISTS `schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `master_profile_id` bigint unsigned NOT NULL,
  `day_of_week` tinyint NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_working_day` tinyint(1) NOT NULL DEFAULT '1',
  `break_start` time DEFAULT NULL,
  `break_end` time DEFAULT NULL,
  `is_flexible` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `slot_duration` int NOT NULL DEFAULT '60',
  `slots_available` int NOT NULL DEFAULT '8',
  `buffer_time` int NOT NULL DEFAULT '15',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `schedules_master_profile_id_day_of_week_unique` (`master_profile_id`,`day_of_week`),
  KEY `schedules_master_profile_id_is_working_day_index` (`master_profile_id`,`is_working_day`),
  KEY `schedules_day_of_week_index` (`day_of_week`),
  CONSTRAINT `schedules_master_profile_id_foreign` FOREIGN KEY (`master_profile_id`) REFERENCES `master_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedules`
--

LOCK TABLES `schedules` WRITE;
/*!40000 ALTER TABLE `schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `master_profile_id` bigint unsigned NOT NULL,
  `massage_category_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `duration_minutes` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `price_home` decimal(10,2) DEFAULT NULL,
  `price_sale` decimal(10,2) DEFAULT NULL,
  `sale_percentage` int NOT NULL DEFAULT '0',
  `sale_until` timestamp NULL DEFAULT NULL,
  `is_complex` tinyint(1) NOT NULL DEFAULT '0',
  `included_services` json DEFAULT NULL,
  `contraindications` json DEFAULT NULL,
  `preparation` json DEFAULT NULL,
  `bookings_count` int NOT NULL DEFAULT '0',
  `rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `views_count` int NOT NULL DEFAULT '0',
  `status` enum('active','inactive','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_new` tinyint(1) NOT NULL DEFAULT '1',
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `instant_booking` tinyint(1) NOT NULL DEFAULT '1',
  `advance_booking_hours` int NOT NULL DEFAULT '2',
  `cancellation_hours` int NOT NULL DEFAULT '24',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `services_slug_unique` (`slug`),
  KEY `services_master_profile_id_status_index` (`master_profile_id`,`status`),
  KEY `services_massage_category_id_index` (`massage_category_id`),
  KEY `services_price_index` (`price`),
  KEY `services_rating_index` (`rating`),
  KEY `services_bookings_count_index` (`bookings_count`),
  KEY `services_is_featured_index` (`is_featured`),
  KEY `services_slug_index` (`slug`),
  CONSTRAINT `services_massage_category_id_foreign` FOREIGN KEY (`massage_category_id`) REFERENCES `massage_categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `services_master_profile_id_foreign` FOREIGN KEY (`master_profile_id`) REFERENCES `master_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,1,3,'Тайский массаж','Профессиональный тайский массаж с использованием современных техник и натуральных масел.',60,2500.00,3000.00,NULL,0,NULL,0,NULL,NULL,NULL,32,4.90,183,'active',1,1,'taiskii-massaz-anna-petrova-aafbyK',NULL,NULL,1,2,24,'2025-06-16 12:01:35','2025-06-16 12:01:35'),(2,1,2,'Спортивный массаж','Профессиональный спортивный массаж с использованием современных техник и натуральных масел.',60,3000.00,3500.00,NULL,0,NULL,0,NULL,NULL,NULL,33,4.70,738,'active',0,1,'sportivnyi-massaz-anna-petrova-OtPMHI',NULL,NULL,1,2,24,'2025-06-16 12:01:35','2025-06-16 12:01:35'),(3,1,8,'Лимфодренажный','Профессиональный лимфодренажный с использованием современных техник и натуральных масел.',120,3500.00,4000.00,NULL,0,NULL,0,NULL,NULL,NULL,32,5.00,638,'active',0,1,'limfodrenaznyi-anna-petrova-p8VVuM',NULL,NULL,1,2,24,'2025-06-16 12:01:35','2025-06-16 12:01:35'),(4,2,8,'Лимфодренажный','Профессиональный лимфодренажный с использованием современных техник и натуральных масел.',90,3000.00,3500.00,NULL,0,NULL,0,NULL,NULL,NULL,39,4.90,675,'active',1,1,'limfodrenaznyi-mixail-ivanov-3iOgiJ',NULL,NULL,1,2,24,'2025-06-16 12:01:35','2025-06-16 12:01:35'),(5,2,5,'Антицеллюлитный','Профессиональный антицеллюлитный с использованием современных техник и натуральных масел.',60,3500.00,4000.00,NULL,0,NULL,0,NULL,NULL,NULL,65,5.00,705,'active',0,1,'anticelliulitnyi-mixail-ivanov-Skoh5f',NULL,NULL,1,2,24,'2025-06-16 12:01:35','2025-06-16 12:01:35'),(6,2,2,'Спортивный массаж','Профессиональный спортивный массаж с использованием современных техник и натуральных масел.',60,4000.00,4500.00,NULL,0,NULL,0,NULL,NULL,NULL,96,4.80,236,'active',0,1,'sportivnyi-massaz-mixail-ivanov-0LHlsi',NULL,NULL,1,2,24,'2025-06-16 12:01:35','2025-06-16 12:01:35'),(7,3,8,'Лимфодренажный','Профессиональный лимфодренажный с использованием современных техник и натуральных масел.',60,2000.00,2500.00,NULL,0,NULL,0,NULL,NULL,NULL,34,5.00,174,'active',1,1,'limfodrenaznyi-elena-sidorova-OOKWa8',NULL,NULL,1,2,24,'2025-06-16 12:01:36','2025-06-16 12:01:36'),(8,3,3,'Тайский массаж','Профессиональный тайский массаж с использованием современных техник и натуральных масел.',90,2500.00,3000.00,NULL,0,NULL,0,NULL,NULL,NULL,46,4.50,172,'active',0,1,'taiskii-massaz-elena-sidorova-oWwX3A',NULL,NULL,1,2,24,'2025-06-16 12:01:36','2025-06-16 12:01:36'),(9,3,5,'Антицеллюлитный','Профессиональный антицеллюлитный с использованием современных техник и натуральных масел.',90,3000.00,3500.00,NULL,0,NULL,0,NULL,NULL,NULL,30,5.00,541,'active',0,1,'anticelliulitnyi-elena-sidorova-ed7oA4',NULL,NULL,1,2,24,'2025-06-16 12:01:36','2025-06-16 12:01:36'),(10,4,6,'Расслабляющий','Профессиональный расслабляющий с использованием современных техник и натуральных масел.',120,3500.00,4000.00,NULL,0,NULL,0,NULL,NULL,NULL,99,4.50,822,'active',1,1,'rasslabliaiushhii-dmitrii-kozlov-bRZ4wi',NULL,NULL,1,2,24,'2025-06-16 12:01:36','2025-06-16 12:01:36'),(11,4,3,'Тайский массаж','Профессиональный тайский массаж с использованием современных техник и натуральных масел.',60,4000.00,4500.00,NULL,0,NULL,0,NULL,NULL,NULL,77,4.60,400,'active',0,1,'taiskii-massaz-dmitrii-kozlov-v9huLe',NULL,NULL,1,2,24,'2025-06-16 12:01:36','2025-06-16 12:01:36'),(12,4,4,'Лечебный массаж','Профессиональный лечебный массаж с использованием современных техник и натуральных масел.',60,4500.00,5000.00,NULL,0,NULL,0,NULL,NULL,NULL,66,4.60,861,'active',0,1,'lecebnyi-massaz-dmitrii-kozlov-T5t1rd',NULL,NULL,1,2,24,'2025-06-16 12:01:36','2025-06-16 12:01:36'),(13,5,3,'Тайский массаж','Профессиональный тайский массаж с использованием современных техник и натуральных масел.',90,2200.00,2700.00,NULL,0,NULL,0,NULL,NULL,NULL,77,5.00,925,'active',1,1,'taiskii-massaz-olga-nikolaeva-Ce2DWM',NULL,NULL,1,2,24,'2025-06-16 12:01:36','2025-06-16 12:01:36'),(14,5,8,'Лимфодренажный','Профессиональный лимфодренажный с использованием современных техник и натуральных масел.',120,2700.00,3200.00,NULL,0,NULL,0,NULL,NULL,NULL,49,4.80,544,'active',0,1,'limfodrenaznyi-olga-nikolaeva-eTyvYX',NULL,NULL,1,2,24,'2025-06-16 12:01:36','2025-06-16 12:01:36'),(15,5,5,'Антицеллюлитный','Профессиональный антицеллюлитный с использованием современных техник и натуральных масел.',90,3200.00,3700.00,NULL,0,NULL,0,NULL,NULL,NULL,76,5.00,744,'active',0,1,'anticelliulitnyi-olga-nikolaeva-ICqsph',NULL,NULL,1,2,24,'2025-06-16 12:01:36','2025-06-16 12:01:36'),(16,7,1,'Классический массаж',NULL,60,2000.00,NULL,NULL,0,NULL,0,NULL,NULL,NULL,0,0.00,0,'active',0,1,'klassiceskii-massaz-anna-df6c',NULL,NULL,1,2,24,'2025-07-03 06:58:11','2025-07-03 06:58:11');
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('v8KezU2gxKyhNKtQRrHBLXTZuw2arrxtnTaDBZZx',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 YaBrowser/25.6.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSENMenlsZk5lekRaRG1nYVdMVmZiUEp1dWFoQmVuTXJIMXY4TnFXYyI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NTtzOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czoyMToiaHR0cDovLzEyNy4wLjAuMTo4MDAwIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1752238893);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
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
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('client','master','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'client',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_phone_unique` (`phone`),
  KEY `users_role_index` (`role`),
  KEY `users_is_active_index` (`is_active`),
  KEY `users_email_verified_at_index` (`email_verified_at`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Анна Петрова','anna@spa.test',NULL,'2025-06-16 12:01:35','$2y$12$TF1ylAUEcDYcRVxhhV4UNueOuxMAR8P/124fE9ZwV7p.SOKUnKIH6','master',1,NULL,'UzpoEpVGk4tBvlnHTFlmLeSlUB0ci52uuzc7qqsxuiUmk7ZjZM8ftcZsUSuo','2025-06-16 12:01:35','2025-06-16 12:01:35'),(2,'Михаил Иванов','mikhail@spa.test',NULL,'2025-06-16 12:01:35','$2y$12$hLinMo7kY00849Qm3LrVoey8Jy34TgC/NKsdsLsueyGVmhfD5lbuq','master',1,NULL,NULL,'2025-06-16 12:01:35','2025-06-16 12:01:35'),(3,'Елена Сидорова','elena@spa.test',NULL,'2025-06-16 12:01:36','$2y$12$CYRQi4sPjO09HNGEals2pe7I4dBmAzGgdx4jQS4lz5Z14GcN.X3Oe','master',1,NULL,NULL,'2025-06-16 12:01:36','2025-06-16 12:01:36'),(4,'Дмитрий Козлов','dmitry@spa.test',NULL,'2025-06-16 12:01:36','$2y$12$PkvGtqgTx2BAUgZxZTujMuG9hQn.1vIrRPmadWKh0kRzzlaRQdT4S','master',1,NULL,NULL,'2025-06-16 12:01:36','2025-06-16 12:01:36'),(5,'Ольга Николаева','olga@spa.test',NULL,'2025-06-16 12:01:36','$2y$12$dYq5X0pc41WH33mueS2q4uiorZdPJyeWg4jyXuV.RMrLW.gjqbfsG','master',1,NULL,'7f2nYcFXorxaekG92Ivs6RgbMrtYWo8UHHFU9flFRztywkeG5mBP2HQZNuM6','2025-06-16 12:01:36','2025-06-16 12:01:36'),(6,'Анна','anna.test1@example.com',NULL,NULL,'$2y$12$1Pywpudrc2XSHNC/BeifyuQw3/mTlWZOLOkhQpEALcvVMdX2YC6fe','master',1,NULL,NULL,'2025-07-03 07:41:16','2025-07-03 07:41:16'),(7,'Анна','anna.test2@example.com',NULL,NULL,'$2y$12$wx3EjhwiL5xbvijCFDvFbu4qLjCZJyy/tLBT1aAatzcOjOSro4Anm','master',1,NULL,NULL,'2025-07-03 07:41:16','2025-07-03 07:41:16'),(8,'Анна','anna.test3@example.com',NULL,NULL,'$2y$12$PAxS01bOlhIGlQK.P0rfuusaspH/9m.TF5C7vO775LG2wVsOH5PTG','master',1,NULL,NULL,'2025-07-03 07:41:17','2025-07-03 07:41:17'),(9,'Мария Иванова','mariia-ivanova@example.com',NULL,NULL,'$2y$12$QmKeTu9BiuQMda6uIvBuWuHm4L71S1lxfhgIfY3N22XkHSe23hhMi','master',1,NULL,NULL,'2025-07-03 07:41:17','2025-07-03 07:41:17'),(10,'Елена','elena@example.com',NULL,NULL,'$2y$12$QWQwqPPzyPiRg4pP5cf3/OND5213YLB8TNAVGC7OUxDcDnQKxJU2m','master',1,NULL,NULL,'2025-07-03 07:41:17','2025-07-03 07:41:17'),(11,'Ольга Петрова','olga-petrova@example.com',NULL,NULL,'$2y$12$duxTDgXLeTRY/ZUuuFVJtugPHRVTQOipffrpT7NORsV18AQ9ciz7G','master',1,NULL,NULL,'2025-07-03 07:41:17','2025-07-03 07:41:17'),(12,'Наталья','natalia@example.com',NULL,NULL,'$2y$12$fuAeq7X80SqEtlfVxZMaMO5.6wdBcvjTjKz283aB.jK/eSKgvJoTi','master',1,NULL,NULL,'2025-07-03 07:41:18','2025-07-03 07:41:18');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_zones`
--

DROP TABLE IF EXISTS `work_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_zones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `master_profile_id` bigint unsigned NOT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Москва',
  `district` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metro_station` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extra_charge` decimal(8,2) NOT NULL DEFAULT '0.00',
  `extra_charge_type` enum('fixed','percentage') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed',
  `min_order_amount` int NOT NULL DEFAULT '0',
  `max_distance_km` int DEFAULT NULL,
  `work_from` time NOT NULL DEFAULT '09:00:00',
  `work_to` time NOT NULL DEFAULT '21:00:00',
  `work_days` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `priority` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `work_zones_master_profile_id_is_active_index` (`master_profile_id`,`is_active`),
  KEY `work_zones_city_district_index` (`city`,`district`),
  KEY `work_zones_metro_station_index` (`metro_station`),
  CONSTRAINT `work_zones_master_profile_id_foreign` FOREIGN KEY (`master_profile_id`) REFERENCES `master_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_zones`
--

LOCK TABLES `work_zones` WRITE;
/*!40000 ALTER TABLE `work_zones` DISABLE KEYS */;
/*!40000 ALTER TABLE `work_zones` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-07-11 18:18:33
