-- STRHub AI — Tax Compliance Migration
-- Tables: einvoices, einvoice_items, cp58_records, sst_settings
-- No FK constraints (Hostinger shared hosting)

CREATE TABLE IF NOT EXISTS sst_settings (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id           INT UNSIGNED NOT NULL UNIQUE,
  is_sst_registered   TINYINT(1)   NOT NULL DEFAULT 0,
  sst_number          VARCHAR(50)  DEFAULT NULL COMMENT 'SST registration no.',
  sst_rate            DECIMAL(5,2) NOT NULL DEFAULT 8.00,
  company_name        VARCHAR(255) DEFAULT NULL,
  company_address     TEXT         DEFAULT NULL,
  company_email       VARCHAR(255) DEFAULT NULL,
  company_phone       VARCHAR(30)  DEFAULT NULL,
  company_tin         VARCHAR(50)  DEFAULT NULL COMMENT 'Income Tax No. / TIN',
  bank_name           VARCHAR(100) DEFAULT NULL,
  bank_account        VARCHAR(50)  DEFAULT NULL,
  bank_holder         VARCHAR(255) DEFAULT NULL,
  invoice_prefix      VARCHAR(20)  DEFAULT 'INV',
  invoice_notes       TEXT         DEFAULT NULL,
  updated_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS einvoices (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id        INT UNSIGNED NOT NULL,
  invoice_no       VARCHAR(50)  NOT NULL,
  invoice_date     DATE         NOT NULL,
  due_date         DATE         DEFAULT NULL,
  invoice_type     ENUM('management_fee','rental','maintenance','commission','other') NOT NULL DEFAULT 'management_fee',
  billed_to_type   ENUM('owner','renter','other') NOT NULL DEFAULT 'owner',
  billed_to_id     INT UNSIGNED DEFAULT NULL,
  billed_to_name   VARCHAR(255) NOT NULL,
  billed_to_email  VARCHAR(255) DEFAULT NULL,
  billed_to_phone  VARCHAR(30)  DEFAULT NULL,
  billed_to_ic     VARCHAR(30)  DEFAULT NULL,
  billed_to_tin    VARCHAR(50)  DEFAULT NULL,
  billed_to_address TEXT        DEFAULT NULL,
  property_id      INT UNSIGNED DEFAULT NULL,
  subtotal         DECIMAL(10,2) NOT NULL DEFAULT 0,
  sst_rate         DECIMAL(5,2)  NOT NULL DEFAULT 0,
  sst_amount       DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_amount     DECIMAL(10,2) NOT NULL DEFAULT 0,
  status           ENUM('draft','issued','paid','cancelled','overdue') NOT NULL DEFAULT 'draft',
  payment_date     DATE         DEFAULT NULL,
  payment_method   VARCHAR(50)  DEFAULT NULL,
  notes            TEXT         DEFAULT NULL,
  myinvois_uuid    VARCHAR(100) DEFAULT NULL COMMENT 'LHDN MyInvois UUID after submission',
  myinvois_status  VARCHAR(50)  DEFAULT NULL,
  created_by       INT UNSIGNED DEFAULT NULL,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tenant        (tenant_id),
  INDEX idx_tenant_status (tenant_id, status),
  INDEX idx_invoice_no    (tenant_id, invoice_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS einvoice_items (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id  INT UNSIGNED NOT NULL,
  description VARCHAR(500) NOT NULL,
  quantity    DECIMAL(10,3) NOT NULL DEFAULT 1.000,
  unit_price  DECIMAL(10,2) NOT NULL,
  amount      DECIMAL(10,2) NOT NULL,
  INDEX idx_invoice (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cp58_records (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id        INT UNSIGNED NOT NULL,
  year             SMALLINT UNSIGNED NOT NULL,
  agent_id         INT UNSIGNED NOT NULL,
  agent_name       VARCHAR(255) NOT NULL,
  agent_ic         VARCHAR(30)  DEFAULT NULL,
  agent_tin        VARCHAR(50)  DEFAULT NULL,
  agent_address    TEXT         DEFAULT NULL,
  jan              DECIMAL(10,2) NOT NULL DEFAULT 0,
  feb              DECIMAL(10,2) NOT NULL DEFAULT 0,
  mar              DECIMAL(10,2) NOT NULL DEFAULT 0,
  apr              DECIMAL(10,2) NOT NULL DEFAULT 0,
  may_amt          DECIMAL(10,2) NOT NULL DEFAULT 0,
  jun              DECIMAL(10,2) NOT NULL DEFAULT 0,
  jul              DECIMAL(10,2) NOT NULL DEFAULT 0,
  aug              DECIMAL(10,2) NOT NULL DEFAULT 0,
  sep              DECIMAL(10,2) NOT NULL DEFAULT 0,
  oct              DECIMAL(10,2) NOT NULL DEFAULT 0,
  nov              DECIMAL(10,2) NOT NULL DEFAULT 0,
  dec_amt          DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_commission DECIMAL(10,2) NOT NULL DEFAULT 0,
  status           ENUM('draft','issued') NOT NULL DEFAULT 'draft',
  issued_date      DATE         DEFAULT NULL,
  notes            TEXT         DEFAULT NULL,
  created_by       INT UNSIGNED DEFAULT NULL,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_agent_year (tenant_id, agent_id, year),
  INDEX idx_tenant_year (tenant_id, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
