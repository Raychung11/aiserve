-- STRHub AI — Tenant Management Migration (no FK constraints, safe for Hostinger)
-- Run via phpMyAdmin

-- 1. Renter profiles (separate from SaaS `tenants` table)
CREATE TABLE IF NOT EXISTS renter_profiles (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id          INT UNSIGNED NOT NULL,
  name               VARCHAR(255) NOT NULL,
  ic_number          VARCHAR(30)  DEFAULT NULL,
  date_of_birth      DATE         DEFAULT NULL,
  nationality        VARCHAR(50)  DEFAULT 'Malaysian',
  email              VARCHAR(255) DEFAULT NULL,
  phone              VARCHAR(30)  DEFAULT NULL,
  whatsapp           VARCHAR(30)  DEFAULT NULL,
  emergency_name     VARCHAR(255) DEFAULT NULL,
  emergency_phone    VARCHAR(30)  DEFAULT NULL,
  emergency_relation VARCHAR(50)  DEFAULT NULL,
  employer_name      VARCHAR(255) DEFAULT NULL,
  job_title          VARCHAR(100) DEFAULT NULL,
  monthly_income     DECIMAL(10,2) DEFAULT NULL,
  employment_type    ENUM('employed','self_employed','student','retired','other') DEFAULT 'employed',
  previous_address   TEXT         DEFAULT NULL,
  notes              TEXT         DEFAULT NULL,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Link tenancies to renter profiles
ALTER TABLE str_tenancies
  ADD COLUMN IF NOT EXISTS renter_id INT UNSIGNED NULL DEFAULT NULL AFTER property_id,
  ADD INDEX IF NOT EXISTS idx_renter (renter_id);

-- 3. Monthly rent payment ledger
CREATE TABLE IF NOT EXISTS rent_payments (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id      INT UNSIGNED NOT NULL,
  tenancy_id     INT UNSIGNED NOT NULL,
  period         CHAR(7) NOT NULL COMMENT 'YYYY-MM',
  amount_due     DECIMAL(10,2) NOT NULL,
  amount_paid    DECIMAL(10,2) NOT NULL DEFAULT 0,
  payment_date   DATE          DEFAULT NULL,
  payment_method ENUM('bank_transfer','cash','cheque','duitnow','online','other') DEFAULT 'bank_transfer',
  receipt_no     VARCHAR(100)  DEFAULT NULL,
  status         ENUM('pending','paid','partial','overdue') NOT NULL DEFAULT 'pending',
  notes          TEXT          DEFAULT NULL,
  recorded_by    INT UNSIGNED  DEFAULT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_tenancy_period (tenancy_id, period),
  INDEX idx_tenant_status (tenant_id, status),
  INDEX idx_tenancy       (tenancy_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
