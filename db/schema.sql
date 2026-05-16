-- Adcellent ESG OS Database Schema
-- Compatible with MySQL 8.0+ / MariaDB 10.4+
-- Hostinger deployment ready

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+08:00"; -- Malaysia Time (MYT)

-- --------------------------------------------------------
-- Table: users
-- Supports Admin, Consultant, SME Owner roles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(150) NOT NULL,
  `email`        VARCHAR(200) NOT NULL UNIQUE,
  `password`     VARCHAR(255) NOT NULL,
  `role`         ENUM('admin','consultant','sme_owner') NOT NULL DEFAULT 'sme_owner',
  `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: companies
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `companies` (
  `id`               INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(200) NOT NULL,
  `registration_no`  VARCHAR(50) DEFAULT NULL,
  `industry`         ENUM('Manufacturing','Services','Trading','Construction','Other') NOT NULL,
  `revenue_tier`     ENUM('below_10M','10M_to_50M','above_50M') NOT NULL,
  `employee_count`   INT(11) NOT NULL DEFAULT 0,
  `framework`        VARCHAR(50) NOT NULL DEFAULT 'BURSA_SEDG',
  `reporting_year`   YEAR NOT NULL DEFAULT '2024',
  `created_by`       INT(11) UNSIGNED NOT NULL,
  `is_pre_ipo`       TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: user_companies (multi-tenant: consultants manage multiple companies)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_companies` (
  `id`           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      INT(11) UNSIGNED NOT NULL,
  `company_id`   INT(11) UNSIGNED NOT NULL,
  `role`         ENUM('owner','editor','viewer') NOT NULL DEFAULT 'editor',
  `assigned_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_company` (`user_id`, `company_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: esg_data
-- Stores all ESG data entries per company, indicator, and period
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `esg_data` (
  `id`            INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`    INT(11) UNSIGNED NOT NULL,
  `indicator_id`  VARCHAR(30) NOT NULL,
  `framework`     VARCHAR(50) NOT NULL DEFAULT 'BURSA_SEDG',
  `category`      ENUM('ENVIRONMENT','SOCIAL','GOVERNANCE') NOT NULL,
  `value`         TEXT DEFAULT NULL,
  `unit`          VARCHAR(50) DEFAULT NULL,
  `notes`         TEXT DEFAULT NULL,
  `data_source`   VARCHAR(200) DEFAULT NULL,
  `period`        VARCHAR(10) NOT NULL DEFAULT '2024',
  `verified`      TINYINT(1) NOT NULL DEFAULT 0,
  `entered_by`    INT(11) UNSIGNED DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entry` (`company_id`, `indicator_id`, `period`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`entered_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: gap_analyses
-- Cached gap analysis results
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gap_analyses` (
  `id`                    INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`            INT(11) UNSIGNED NOT NULL,
  `framework`             VARCHAR(50) NOT NULL DEFAULT 'BURSA_SEDG',
  `period`                VARCHAR(10) NOT NULL DEFAULT '2024',
  `total_indicators`      INT(11) NOT NULL DEFAULT 0,
  `completed_indicators`  INT(11) NOT NULL DEFAULT 0,
  `score`                 DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `env_score`             DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `social_score`          DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `gov_score`             DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `analysis_json`         LONGTEXT DEFAULT NULL,
  `generated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: reports
-- Generated ESG reports
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reports` (
  `id`            INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`    INT(11) UNSIGNED NOT NULL,
  `title`         VARCHAR(300) NOT NULL,
  `framework`     VARCHAR(50) NOT NULL DEFAULT 'BURSA_SEDG',
  `period`        VARCHAR(10) NOT NULL DEFAULT '2024',
  `content_html`  LONGTEXT DEFAULT NULL,
  `score`         DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `generated_by`  INT(11) UNSIGNED DEFAULT NULL,
  `generated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: activity_log
-- Audit trail for compliance
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT(11) UNSIGNED DEFAULT NULL,
  `company_id`  INT(11) UNSIGNED DEFAULT NULL,
  `action`      VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address`  VARCHAR(45) DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
