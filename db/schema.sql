-- STRHub AI — MySQL Schema
-- Run once on your Hostinger MySQL database
-- Create database first: CREATE DATABASE strhub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET NAMES utf8mb4;
SET time_zone = '+08:00';

-- -------------------------------------------------------
-- 1. tenants — one row per company/agency
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS tenants (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                 VARCHAR(255) NOT NULL,
    email                VARCHAR(255) NOT NULL UNIQUE,
    phone                VARCHAR(20),
    company_reg          VARCHAR(50),
    plan                 ENUM('starter','growth','enterprise') NOT NULL DEFAULT 'starter',
    status               ENUM('trial','active','suspended','cancelled') NOT NULL DEFAULT 'trial',
    max_properties       SMALLINT UNSIGNED NOT NULL DEFAULT 5,
    trial_ends_at        DATETIME,
    subscription_ends_at DATETIME,
    settings             JSON,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 2. users — admins and agents, always tied to a tenant
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS str_users (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id        INT UNSIGNED NOT NULL,
    name             VARCHAR(255) NOT NULL,
    email            VARCHAR(255) NOT NULL UNIQUE,
    phone            VARCHAR(20),
    password_hash    VARCHAR(255) NOT NULL,
    role             ENUM('super_admin','admin','agent','viewer') NOT NULL DEFAULT 'admin',
    agent_code       VARCHAR(20) UNIQUE,
    commission_tier  TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT '5, 7, or 10 percent',
    commission_wallet DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active        TINYINT(1) NOT NULL DEFAULT 1,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 3. properties
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS properties (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         INT UNSIGNED NOT NULL,
    name              VARCHAR(255) NOT NULL,
    address           TEXT NOT NULL,
    city              VARCHAR(100) NOT NULL,
    state             VARCHAR(100) NOT NULL,
    postcode          VARCHAR(10)  NOT NULL,
    property_type     ENUM('condo','serviced_apartment','landed','commercial','soho','sofo') NOT NULL DEFAULT 'condo',
    strata_building   VARCHAR(255),
    is_strata         TINYINT(1) NOT NULL DEFAULT 1,
    bedrooms          TINYINT UNSIGNED NOT NULL DEFAULT 1,
    bathrooms         TINYINT UNSIGNED NOT NULL DEFAULT 1,
    area_sqft         DECIMAL(8,2),
    compliance_status ENUM('green','amber','red') NOT NULL DEFAULT 'amber',
    compliance_notes  TEXT,
    strategy_mode     ENUM('STR','MID_TERM','SUBLET','CORPORATE') NOT NULL DEFAULT 'STR',
    listing_status    ENUM('active','inactive','pending','maintenance') NOT NULL DEFAULT 'pending',
    agent_id          INT UNSIGNED,
    owner_name        VARCHAR(255),
    owner_phone       VARCHAR(20),
    owner_email       VARCHAR(255),
    monthly_target    DECIMAL(10,2) NOT NULL DEFAULT 0,
    airbnb_url        VARCHAR(500),
    booking_url       VARCHAR(500),
    notes             TEXT,
    deleted_at        DATETIME,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (agent_id)  REFERENCES str_users(id) ON DELETE SET NULL,
    INDEX idx_tenant   (tenant_id),
    INDEX idx_compliance (tenant_id, compliance_status),
    INDEX idx_strategy   (tenant_id, strategy_mode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 4. investments — per property capital tracking
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS investments (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         INT UNSIGNED NOT NULL,
    property_id       INT UNSIGNED NOT NULL UNIQUE,
    purchase_price    DECIMAL(12,2) NOT NULL DEFAULT 0,
    renovation_cost   DECIMAL(10,2) NOT NULL DEFAULT 0,
    setup_cost        DECIMAL(10,2) NOT NULL DEFAULT 0,
    furnishing_cost   DECIMAL(10,2) NOT NULL DEFAULT 0,
    deposit_paid      DECIMAL(10,2) NOT NULL DEFAULT 0,
    legal_fees        DECIMAL(10,2) NOT NULL DEFAULT 0,
    stamp_duty        DECIMAL(10,2) NOT NULL DEFAULT 0,
    other_costs       DECIMAL(10,2) NOT NULL DEFAULT 0,
    investment_date   DATE,
    notes             TEXT,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id)   REFERENCES tenants(id)    ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 5. revenue_entries — monthly P&L
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS revenue_entries (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id    INT UNSIGNED NOT NULL,
    property_id  INT UNSIGNED NOT NULL,
    period       CHAR(7) NOT NULL COMMENT 'YYYY-MM',
    type         ENUM('income','expense') NOT NULL,
    category     ENUM('rental','cleaning','utilities','maintenance','platform_fee','commission','insurance','assessment','management_fee','other') NOT NULL,
    amount       DECIMAL(10,2) NOT NULL,
    description  VARCHAR(255),
    payment_date DATE,
    payment_ref  VARCHAR(100),
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id)   REFERENCES tenants(id)    ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    INDEX idx_period (tenant_id, property_id, period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 6. tenancies — lease records
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS str_tenancies (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           INT UNSIGNED NOT NULL,
    property_id         INT UNSIGNED NOT NULL,
    tenant_name         VARCHAR(255) NOT NULL,
    tenant_phone        VARCHAR(20),
    tenant_email        VARCHAR(255),
    tenant_ic           VARCHAR(20),
    tenant_company      VARCHAR(255),
    start_date          DATE NOT NULL,
    end_date            DATE NOT NULL,
    monthly_rent        DECIMAL(10,2) NOT NULL,
    deposit             DECIMAL(10,2) NOT NULL DEFAULT 0,
    deposit_paid        TINYINT(1) NOT NULL DEFAULT 0,
    type                ENUM('STR','MID_TERM','SUBLET','CORPORATE') NOT NULL DEFAULT 'MID_TERM',
    status              ENUM('active','expired','terminated','pending') NOT NULL DEFAULT 'pending',
    renewal_notified_at DATETIME,
    agent_id            INT UNSIGNED,
    notes               TEXT,
    deleted_at          DATETIME,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id)   REFERENCES tenants(id)    ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (agent_id)    REFERENCES str_users(id)       ON DELETE SET NULL,
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_end_date      (tenant_id, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 7. subscriptions — Billplz billing records
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS subscriptions (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id             INT UNSIGNED NOT NULL,
    plan                  ENUM('starter','growth','enterprise') NOT NULL,
    status                ENUM('active','pending','expired','cancelled') NOT NULL DEFAULT 'pending',
    amount                DECIMAL(10,2) NOT NULL,
    billing_cycle         ENUM('monthly','annually') NOT NULL DEFAULT 'monthly',
    billplz_bill_id       VARCHAR(100),
    billplz_collection_id VARCHAR(100),
    billplz_url           VARCHAR(500),
    starts_at             DATETIME,
    ends_at               DATETIME,
    paid_at               DATETIME,
    cancelled_at          DATETIME,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant_status (tenant_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 8. commission_logs — auto-generated per rental income
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS commission_logs (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         INT UNSIGNED NOT NULL,
    agent_id          INT UNSIGNED NOT NULL,
    property_id       INT UNSIGNED NOT NULL,
    revenue_entry_id  INT UNSIGNED,
    revenue_amount    DECIMAL(10,2) NOT NULL,
    commission_rate   DECIMAL(5,2)  NOT NULL,
    commission_amount DECIMAL(10,2) NOT NULL,
    platform_net      DECIMAL(10,2) NOT NULL,
    status            ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
    paid_at           DATETIME,
    reference         VARCHAR(100),
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id)   REFERENCES tenants(id)    ON DELETE CASCADE,
    FOREIGN KEY (agent_id)    REFERENCES str_users(id)       ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    INDEX idx_agent  (agent_id, status),
    INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 9. activity_logs — audit trail
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   INT UNSIGNED,
    user_id     INT UNSIGNED,
    action      VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address  VARCHAR(45),
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
