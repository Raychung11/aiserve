-- AiServe ESG OS — Migration v3
-- Adds subscription and indicator collection tables
-- Run once via phpMyAdmin or CLI:
--   mysql -u <user> -p <dbname> < migrate_v3.sql

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- --------------------------------------------------------
-- Table: subscriptions
-- Tracks active plan per company (plan definitions live in config/plans.php)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`  INT(11) UNSIGNED NOT NULL,
  `plan_code`   VARCHAR(30) NOT NULL DEFAULT 'starter',
  `status`      ENUM('active','expired','cancelled','trial') NOT NULL DEFAULT 'active',
  `starts_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at`  DATETIME DEFAULT NULL,
  `invoice_ref` VARCHAR(100) DEFAULT NULL,
  `created_by`  INT(11) UNSIGNED DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company_status` (`company_id`, `status`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: company_collections
-- Tracks which indicator collection add-ons a company has purchased
-- (Collection definitions live in config/collections.php)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `company_collections` (
  `id`               INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`       INT(11) UNSIGNED NOT NULL,
  `collection_code`  VARCHAR(50) NOT NULL,
  `status`           ENUM('active','expired') NOT NULL DEFAULT 'active',
  `expires_at`       DATETIME DEFAULT NULL,
  `purchased_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `purchased_by`     INT(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_company_collection` (`company_id`, `collection_code`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`purchased_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
