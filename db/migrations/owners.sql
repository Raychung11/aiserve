-- STRHub AI — Owner Module Migration
-- Run via phpMyAdmin on Hostinger after uploading

-- 1. Add owner role + owner_id FK to users
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS role ENUM('admin','owner') NOT NULL DEFAULT 'admin' AFTER email,
  ADD COLUMN IF NOT EXISTS owner_id INT NULL DEFAULT NULL AFTER role;

-- 2. Owners master table
CREATE TABLE IF NOT EXISTS owners (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id    INT NOT NULL,
  name         VARCHAR(255) NOT NULL,
  email        VARCHAR(255) DEFAULT NULL,
  phone        VARCHAR(30)  DEFAULT NULL,
  ic_number    VARCHAR(30)  DEFAULT NULL COMMENT 'IC / Passport number',
  bank_name    VARCHAR(100) DEFAULT NULL,
  bank_account VARCHAR(50)  DEFAULT NULL,
  bank_holder  VARCHAR(255) DEFAULT NULL,
  notes        TEXT,
  is_active    TINYINT(1) NOT NULL DEFAULT 1,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Add owner_id FK to properties (keeps owner_name/phone as legacy fallback)
ALTER TABLE properties
  ADD COLUMN IF NOT EXISTS owner_id INT NULL DEFAULT NULL AFTER owner_phone,
  ADD INDEX IF NOT EXISTS idx_prop_owner (owner_id);

-- 4. Owner documents table
CREATE TABLE IF NOT EXISTS owner_documents (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id   INT NOT NULL,
  property_id INT NOT NULL,
  title       VARCHAR(255) NOT NULL,
  description VARCHAR(500) DEFAULT NULL,
  file_path   VARCHAR(500) DEFAULT NULL COMMENT 'relative: uploads/documents/filename',
  url         VARCHAR(1000) DEFAULT NULL COMMENT 'external URL (Google Drive, Dropbox, etc.)',
  mime_type   VARCHAR(100) DEFAULT NULL,
  file_size   INT DEFAULT NULL COMMENT 'bytes',
  uploaded_by INT DEFAULT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id)   REFERENCES tenants(id)    ON DELETE CASCADE,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  INDEX idx_property (tenant_id, property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. (Optional) Backfill owners from existing free-text property fields
-- Review for duplicates before running on production
-- INSERT INTO owners (tenant_id, name, phone, created_at, updated_at)
-- SELECT DISTINCT tenant_id, COALESCE(NULLIF(TRIM(owner_name),''), 'Unknown Owner'), owner_phone, NOW(), NOW()
-- FROM properties WHERE deleted_at IS NULL AND owner_name IS NOT NULL AND TRIM(owner_name) <> '';
--
-- UPDATE properties p
-- JOIN owners o ON o.tenant_id = p.tenant_id AND TRIM(o.name) = TRIM(p.owner_name)
-- SET p.owner_id = o.id WHERE p.owner_id IS NULL;
