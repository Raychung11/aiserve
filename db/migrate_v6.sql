-- Adcellent ESG OS — Migration v6
-- Layer 1: Departments, Notifications, Action Plans, Comments, Monthly KPI Snapshots
-- Run via phpMyAdmin after migrate_v5.sql

-- ── 1. Departments ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `departments` (
  `id`          INT          NOT NULL AUTO_INCREMENT,
  `company_id`  INT          NOT NULL,
  `name`        VARCHAR(100) NOT NULL,
  `type`        ENUM('hr_admin','production','hse','energy','procurement','logistics','finance','it','custom')
                             NOT NULL DEFAULT 'custom',
  `description` VARCHAR(300) DEFAULT NULL,
  `color`       VARCHAR(7)   NOT NULL DEFAULT '#64748b',
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `sort_order`  TINYINT      NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dept_company` (`company_id`),
  CONSTRAINT `fk_dept_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Department ↔ User assignments ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `department_users` (
  `id`            INT       NOT NULL AUTO_INCREMENT,
  `department_id` INT       NOT NULL,
  `user_id`       INT       NOT NULL,
  `role`          ENUM('head','member') NOT NULL DEFAULT 'member',
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dept_user` (`department_id`, `user_id`),
  CONSTRAINT `fk_du_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_du_user` FOREIGN KEY (`user_id`)       REFERENCES `users` (`id`)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Notifications ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `user_id`    INT          NOT NULL,
  `company_id` INT          DEFAULT NULL,
  `type`       VARCHAR(60)  NOT NULL DEFAULT 'system',
  `title`      VARCHAR(200) NOT NULL,
  `message`    TEXT         NOT NULL,
  `link`       VARCHAR(500) DEFAULT NULL,
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user`    (`user_id`, `is_read`),
  KEY `idx_notif_company` (`company_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. Action Plans ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `action_plans` (
  `id`             INT          NOT NULL AUTO_INCREMENT,
  `company_id`     INT          NOT NULL,
  `indicator_id`   VARCHAR(50)  DEFAULT NULL,
  `department_id`  INT          DEFAULT NULL,
  `created_by`     INT          NOT NULL,
  `assigned_to`    INT          DEFAULT NULL,
  `title`          VARCHAR(300) NOT NULL,
  `description`    TEXT         DEFAULT NULL,
  `recommendation` TEXT         DEFAULT NULL,
  `priority`       ENUM('critical','high','medium','low') NOT NULL DEFAULT 'medium',
  `status`         ENUM('open','in_progress','completed','deferred') NOT NULL DEFAULT 'open',
  `due_date`       DATE         DEFAULT NULL,
  `completed_at`   TIMESTAMP    NULL DEFAULT NULL,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ap_company`  (`company_id`),
  KEY `idx_ap_assigned` (`assigned_to`),
  KEY `idx_ap_status`   (`status`),
  CONSTRAINT `fk_ap_company`  FOREIGN KEY (`company_id`)    REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ap_created`  FOREIGN KEY (`created_by`)    REFERENCES `users` (`id`),
  CONSTRAINT `fk_ap_assigned` FOREIGN KEY (`assigned_to`)   REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ap_dept`     FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. Indicator Comments ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `indicator_comments` (
  `id`           INT       NOT NULL AUTO_INCREMENT,
  `company_id`   INT       NOT NULL,
  `indicator_id` VARCHAR(50) NOT NULL,
  `user_id`      INT       NOT NULL,
  `comment`      TEXT      NOT NULL,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ic_company_ind` (`company_id`, `indicator_id`),
  CONSTRAINT `fk_ic_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ic_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 6. Monthly KPI Snapshots ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `monthly_kpi_snapshots` (
  `id`              INT           NOT NULL AUTO_INCREMENT,
  `company_id`      INT           NOT NULL,
  `year`            SMALLINT      NOT NULL,
  `month`           TINYINT       NOT NULL,
  `e_score`         DECIMAL(5,2)  NOT NULL DEFAULT 0,
  `s_score`         DECIMAL(5,2)  NOT NULL DEFAULT 0,
  `g_score`         DECIMAL(5,2)  NOT NULL DEFAULT 0,
  `overall_score`   DECIMAL(5,2)  NOT NULL DEFAULT 0,
  `carbon_scope1`   DECIMAL(12,4) NOT NULL DEFAULT 0,
  `carbon_scope2`   DECIMAL(12,4) NOT NULL DEFAULT 0,
  `carbon_scope3`   DECIMAL(12,4) NOT NULL DEFAULT 0,
  `data_completion` DECIMAL(5,2)  NOT NULL DEFAULT 0,
  `indicators_filled` SMALLINT   NOT NULL DEFAULT 0,
  `indicators_total`  SMALLINT   NOT NULL DEFAULT 0,
  `snapshot_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kpi_company_period` (`company_id`, `year`, `month`),
  CONSTRAINT `fk_kpi_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 7. Add Bursa sector + reporting scope + report level to companies ─────────
ALTER TABLE `companies`
  ADD COLUMN IF NOT EXISTS `bursa_sector`     VARCHAR(100) DEFAULT NULL       AFTER `industry`,
  ADD COLUMN IF NOT EXISTS `reporting_scope`  ENUM('hq','factory','group')    NOT NULL DEFAULT 'hq' AFTER `bursa_sector`,
  ADD COLUMN IF NOT EXISTS `report_level`     VARCHAR(50)  DEFAULT NULL       AFTER `reporting_scope`;
