-- STRHub AI — Owner Module Migration (no FK constraints, safe for Hostinger)
-- Run via phpMyAdmin

-- 1. Add role + owner_id to users table
ALTER TABLE str_users
  ADD COLUMN IF NOT EXISTS role ENUM('admin','owner') NOT NULL DEFAULT 'admin' AFTER email,
  ADD COLUMN IF NOT EXISTS owner_id INT UNSIGNED NULL DEFAULT NULL AFTER role;

-- 2. Owners master table
CREATE TABLE IF NOT EXISTS str_owners (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id    INT UNSIGNED NOT NULL,
  name         VARCHAR(255) NOT NULL,
  email        VARCHAR(255) DEFAULT NULL,
  phone        VARCHAR(30)  DEFAULT NULL,
  ic_number    VARCHAR(30)  DEFAULT NULL,
  bank_name    VARCHAR(100) DEFAULT NULL,
  bank_account VARCHAR(50)  DEFAULT NULL,
  bank_holder  VARCHAR(255) DEFAULT NULL,
  notes        TEXT,
  is_active    TINYINT(1) NOT NULL DEFAULT 1,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Add owner_id to properties
ALTER TABLE properties
  ADD COLUMN IF NOT EXISTS owner_id INT UNSIGNED NULL DEFAULT NULL AFTER owner_phone,
  ADD INDEX IF NOT EXISTS idx_prop_owner (owner_id);

-- 4. Owner documents table
CREATE TABLE IF NOT EXISTS owner_documents (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id   INT UNSIGNED NOT NULL,
  property_id INT UNSIGNED NULL DEFAULT NULL,
  renter_id   INT UNSIGNED NULL DEFAULT NULL,
  title       VARCHAR(255) NOT NULL,
  description VARCHAR(500) DEFAULT NULL,
  file_path   VARCHAR(500) DEFAULT NULL,
  url         VARCHAR(1000) DEFAULT NULL,
  mime_type   VARCHAR(100) DEFAULT NULL,
  file_size   INT UNSIGNED DEFAULT NULL,
  uploaded_by INT UNSIGNED DEFAULT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_property (tenant_id, property_id),
  INDEX idx_renter   (tenant_id, renter_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
