-- MM2H 管家 Platform — Database Schema
-- Version: 1.0.0
-- Created: 2026-04-24

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

-- ─────────────────────────────────────────────
-- ROLES
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `label` VARCHAR(100) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `roles` (`name`, `label`) VALUES
  ('super_admin',      'Super Admin'),
  ('admin',            'Admin Staff'),
  ('member',           'MM2H Applicant / Member'),
  ('agent',            'Licensed MM2H Agent'),
  ('property_partner', 'Property Partner'),
  ('bank_partner',     'Bank Partner'),
  ('biz_partner',      'Business Partner'),
  ('affiliate',        'Affiliate / Referrer');

-- ─────────────────────────────────────────────
-- USERS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30),
  `role` VARCHAR(50) NOT NULL DEFAULT 'member',
  `referral_code` VARCHAR(20) UNIQUE,
  `referred_by` INT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `email_verified` TINYINT(1) DEFAULT 0,
  `last_login` DATETIME,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`referred_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default super admin (password: Admin@1234 — change immediately)
INSERT INTO `users` (`email`, `password`, `full_name`, `role`, `referral_code`, `status`)
VALUES (
  'admin@mm2h.com',
  '$2y$12$yQQFoEV2k.X5JWjP.CrpuujLyQTfkpDm4p7I.vp5xGQIqnx5yXX9m',
  'MM2H Admin',
  'super_admin',
  'ADMIN001',
  'active'
);

-- ─────────────────────────────────────────────
-- MEMBER PROFILES
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `member_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `nationality` VARCHAR(100),
  `date_of_birth` DATE,
  `gender` VARCHAR(10),
  `passport_number` VARCHAR(50),
  `passport_expiry` DATE,
  `country_of_origin` VARCHAR(100),
  `current_residence` VARCHAR(100),
  `purpose` VARCHAR(50) COMMENT 'retirement|business|family|investment|property|education',
  `estimated_investment` VARCHAR(50),
  `preferred_location` VARCHAR(100),
  `property_interest` TINYINT(1) DEFAULT 0,
  `banking_support` TINYINT(1) DEFAULT 0,
  `business_networking` TINYINT(1) DEFAULT 0,
  `family_members` INT DEFAULT 0,
  `monthly_income` VARCHAR(50),
  `liquid_assets` VARCHAR(50),
  `fd_readiness` TINYINT(1) DEFAULT 0,
  `subscription_plan` VARCHAR(30) DEFAULT 'free',
  `onboarding_completed` TINYINT(1) DEFAULT 0,
  `notes` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- MM2H CASES
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `mm2h_cases` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `case_number` VARCHAR(30) UNIQUE,
  `applicant_id` INT NOT NULL,
  `assigned_agent_id` INT,
  `current_status` VARCHAR(50) NOT NULL DEFAULT 'new_lead',
  `notes` TEXT,
  `next_action` VARCHAR(255),
  `due_date` DATE,
  `priority` VARCHAR(10) DEFAULT 'normal',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`applicant_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_agent_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- CASE NOTES
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `case_notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `case_id` INT NOT NULL,
  `author_id` INT NOT NULL,
  `note` TEXT NOT NULL,
  `is_internal` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`case_id`) REFERENCES `mm2h_cases`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- DOCUMENT CHECKLISTS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `document_checklists` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name_en` VARCHAR(200) NOT NULL,
  `name_zh_hant` VARCHAR(200),
  `name_zh_hans` VARCHAR(200),
  `description` TEXT,
  `required` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  `status` VARCHAR(20) DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `document_checklists` (`name_en`, `name_zh_hant`, `name_zh_hans`, `required`, `sort_order`) VALUES
  ('Passport Copy (All Pages)', '護照副本（全頁）', '护照副本（全页）', 1, 1),
  ('Passport Photo (White Background)', '護照照片（白底）', '护照照片（白底）', 1, 2),
  ('Proof of Income', '收入證明', '收入证明', 1, 3),
  ('Bank Statement (3-6 months)', '銀行對帳單（3-6個月）', '银行对账单（3-6个月）', 1, 4),
  ('Resume / Business Profile', '個人履歷 / 商業資料', '个人履历 / 商业资料', 1, 5),
  ('Marriage Certificate (if applicable)', '結婚證書（如適用）', '结婚证书（如适用）', 0, 6),
  ('Birth Certificate (for dependants)', '出生證明（附屬人）', '出生证明（附属人）', 0, 7),
  ('Medical Report', '體檢報告', '体检报告', 1, 8),
  ('Property Documents (if applicable)', '物業文件（如適用）', '物业文件（如适用）', 0, 9),
  ('Tax Returns / Tax Clearance', '稅務申報 / 稅務清關', '税务申报 / 税务清关', 1, 10),
  ('Police Clearance Certificate', '警方無犯罪記錄證明', '警方无犯罪记录证明', 1, 11),
  ('Other Supporting Documents', '其他支持文件', '其他支持文件', 0, 12);

-- ─────────────────────────────────────────────
-- DOCUMENTS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `case_id` INT,
  `checklist_id` INT,
  `file_name` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255),
  `file_type` VARCHAR(50),
  `file_size` INT,
  `file_path` VARCHAR(500) NOT NULL,
  `status` VARCHAR(30) DEFAULT 'pending' COMMENT 'pending|verified|rejected|resubmit',
  `reviewed_by` INT,
  `review_notes` TEXT,
  `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`case_id`) REFERENCES `mm2h_cases`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`checklist_id`) REFERENCES `document_checklists`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- PROPERTIES
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `properties` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `property_name` VARCHAR(200) NOT NULL,
  `location` VARCHAR(200),
  `state` VARCHAR(100),
  `type` VARCHAR(50) COMMENT 'condo|landed|commercial|serviced_apartment',
  `price` DECIMAL(15,2),
  `price_currency` VARCHAR(10) DEFAULT 'MYR',
  `developer` VARCHAR(150),
  `agent_name` VARCHAR(150),
  `agent_contact` VARCHAR(50),
  `mm2h_suitability` TINYINT(1) DEFAULT 1,
  `rental_roi_estimate` DECIMAL(5,2),
  `investment_notes` TEXT,
  `image` VARCHAR(500),
  `status` VARCHAR(20) DEFAULT 'available',
  `featured` TINYINT(1) DEFAULT 0,
  `partner_id` INT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`partner_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- PROPERTY RECOMMENDATIONS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `property_recommendations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `member_id` INT NOT NULL,
  `property_id` INT NOT NULL,
  `assigned_by` INT,
  `notes` TEXT,
  `viewed` TINYINT(1) DEFAULT 0,
  `interested` TINYINT(1),
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`member_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- BANKS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `banks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bank_name` VARCHAR(100) NOT NULL,
  `logo` VARCHAR(500),
  `contact_person` VARCHAR(150),
  `contact_email` VARCHAR(191),
  `contact_phone` VARCHAR(30),
  `fd_min_amount` DECIMAL(15,2),
  `fd_currency` VARCHAR(10) DEFAULT 'MYR',
  `notes` TEXT,
  `status` VARCHAR(20) DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `banks` (`bank_name`, `notes`, `fd_min_amount`) VALUES
  ('Maybank', 'Malaysia\'s largest bank. Full MM2H support.', 500000.00),
  ('CIMB Bank', 'Regional bank with strong MM2H experience.', 500000.00),
  ('Public Bank', 'Conservative and trusted. Good for FD.', 500000.00),
  ('Hong Leong Bank', 'Digital-forward bank with MM2H desk.', 500000.00),
  ('RHB Bank', 'Strong SME and investment banking.', 500000.00);

-- ─────────────────────────────────────────────
-- BANK SUPPORT CASES
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `bank_support_cases` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `member_id` INT NOT NULL,
  `bank_id` INT NOT NULL,
  `contact_person` VARCHAR(150),
  `account_opening_status` VARCHAR(30) DEFAULT 'not_started',
  `fixed_deposit_status` VARCHAR(30) DEFAULT 'not_started',
  `fd_amount` DECIMAL(15,2),
  `notes` TEXT,
  `assigned_by` INT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`member_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`bank_id`) REFERENCES `banks`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- BUSINESS INTERESTS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `business_interests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `member_id` INT NOT NULL,
  `sector` VARCHAR(100),
  `description` TEXT,
  `investment_range` VARCHAR(50),
  `status` VARCHAR(20) DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`member_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- BUSINESS MATCHES
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `business_matches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `interest_id` INT NOT NULL,
  `partner_id` INT NOT NULL,
  `matched_by` INT,
  `notes` TEXT,
  `status` VARCHAR(20) DEFAULT 'proposed',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`interest_id`) REFERENCES `business_interests`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`partner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`matched_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- PARTNERS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `partners` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `company_name` VARCHAR(200),
  `sector` VARCHAR(100),
  `website` VARCHAR(300),
  `description` TEXT,
  `commission_rate` DECIMAL(5,2) DEFAULT 20.00,
  `license_number` VARCHAR(100),
  `verified` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- REFERRALS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `referrals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `referrer_id` INT NOT NULL,
  `referred_user_id` INT NOT NULL,
  `lead_status` VARCHAR(30) DEFAULT 'new',
  `deal_status` VARCHAR(30) DEFAULT 'pending',
  `commission_rate` DECIMAL(5,2) DEFAULT 20.00,
  `commission_amount` DECIMAL(12,2) DEFAULT 0,
  `commission_status` VARCHAR(20) DEFAULT 'pending' COMMENT 'pending|approved|paid|rejected',
  `notes` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`referrer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`referred_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- COMMISSIONS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `commissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `partner_id` INT NOT NULL,
  `referral_id` INT,
  `amount` DECIMAL(12,2) NOT NULL,
  `currency` VARCHAR(10) DEFAULT 'MYR',
  `type` VARCHAR(50) DEFAULT 'referral',
  `status` VARCHAR(20) DEFAULT 'pending',
  `paid_at` DATETIME,
  `notes` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`partner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`referral_id`) REFERENCES `referrals`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- SUBSCRIPTIONS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `plan` VARCHAR(30) NOT NULL DEFAULT 'free' COMMENT 'free|premium|concierge',
  `amount` DECIMAL(10,2) DEFAULT 0,
  `currency` VARCHAR(10) DEFAULT 'MYR',
  `starts_at` DATETIME,
  `expires_at` DATETIME,
  `status` VARCHAR(20) DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- PAYMENTS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `subscription_id` INT,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(10) DEFAULT 'MYR',
  `method` VARCHAR(50),
  `reference` VARCHAR(200),
  `status` VARCHAR(20) DEFAULT 'pending',
  `paid_at` DATETIME,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- SETTINGS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `description` VARCHAR(255),
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
  ('site_name', 'MM2H 管家', 'Platform name'),
  ('site_email', 'info@mm2h.com', 'Contact email'),
  ('site_phone', '+60 3-XXXX XXXX', 'Contact phone'),
  ('default_commission', '20', 'Default commission rate (%)'),
  ('premium_price', '99', 'Monthly premium plan price (MYR)'),
  ('enable_ai', '0', 'Enable AI onboarding (1=yes)'),
  ('openai_key', '', 'OpenAI API key'),
  ('n8n_webhook', '', 'n8n webhook URL'),
  ('maintenance_mode', '0', 'Maintenance mode (1=yes)');

-- ─────────────────────────────────────────────
-- ACTIVITY LOGS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT,
  `action` VARCHAR(200) NOT NULL,
  `target_type` VARCHAR(50),
  `target_id` INT,
  `ip_address` VARCHAR(45),
  `user_agent` VARCHAR(500),
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET foreign_key_checks = 1;
