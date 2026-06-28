-- CoLive OS -- MariaDB Schema
-- SLV Group Sdn. Bhd.
-- Run in phpMyAdmin: CREATE DATABASE colive CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET NAMES utf8mb4;
SET time_zone = '+08:00';
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- PLATFORM LAYER
-- ============================================================

CREATE TABLE IF NOT EXISTS platform_admins (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(120) NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    last_login_at DATETIME     DEFAULT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS plans (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(60)   NOT NULL,
    max_units       SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    max_staff       TINYINT UNSIGNED  NOT NULL DEFAULT 5,
    price_monthly   DECIMAL(10,2) NOT NULL DEFAULT 0,
    price_annually  DECIMAL(10,2) NOT NULL DEFAULT 0,
    features        JSON          DEFAULT NULL,
    is_active       TINYINT(1)    NOT NULL DEFAULT 1,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS companies (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    phone           VARCHAR(30)  DEFAULT NULL,
    address         TEXT         DEFAULT NULL,
    ssm_reg         VARCHAR(50)  DEFAULT NULL,
    brand_color     VARCHAR(7)   DEFAULT NULL  COMMENT 'Hex e.g. #9333ea',
    logo_path       VARCHAR(500) DEFAULT NULL,
    plan_id         INT UNSIGNED DEFAULT NULL,
    status          ENUM('trial','active','suspended','cancelled') NOT NULL DEFAULT 'trial',
    trial_ends_at   DATETIME     DEFAULT NULL,
    sub_ends_at     DATETIME     DEFAULT NULL,
    invoice_prefix  VARCHAR(20)  NOT NULL DEFAULT 'INV',
    invoice_seq     INT UNSIGNED NOT NULL DEFAULT 0,
    billing_email   VARCHAR(255) DEFAULT NULL,
    settings        JSON         DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- OPERATOR LAYER
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id    INT UNSIGNED NOT NULL,
    name          VARCHAR(120) NOT NULL,
    email         VARCHAR(255) NOT NULL,
    phone         VARCHAR(30)  DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','manager','tenant_relations','finance','maintenance') NOT NULL DEFAULT 'admin',
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    last_login_at DATETIME     DEFAULT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_company_email (company_id, email),
    INDEX idx_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- INVENTORY
-- ============================================================

CREATE TABLE IF NOT EXISTS owners (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id     INT UNSIGNED NOT NULL,
    name           VARCHAR(120) NOT NULL,
    ic_number      VARCHAR(20)  DEFAULT NULL,
    email          VARCHAR(255) DEFAULT NULL,
    phone          VARCHAR(30)  DEFAULT NULL,
    bank_name      VARCHAR(100) DEFAULT NULL,
    bank_account   VARCHAR(50)  DEFAULT NULL,
    bank_holder    VARCHAR(120) DEFAULT NULL,
    notes          TEXT         DEFAULT NULL,
    is_active      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS buildings (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id  INT UNSIGNED NOT NULL,
    name        VARCHAR(120) NOT NULL,
    address     TEXT         DEFAULT NULL,
    city        VARCHAR(80)  DEFAULT NULL,
    state       VARCHAR(80)  DEFAULT NULL,
    postcode    VARCHAR(10)  DEFAULT NULL,
    notes       TEXT         DEFAULT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS units (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      INT UNSIGNED NOT NULL,
    building_id     INT UNSIGNED NOT NULL,
    owner_id        INT UNSIGNED DEFAULT NULL,
    unit_no         VARCHAR(30)  NOT NULL,
    floor           TINYINT UNSIGNED DEFAULT NULL,
    total_rooms     TINYINT UNSIGNED NOT NULL DEFAULT 1,
    master_rent     DECIMAL(10,2) NOT NULL DEFAULT 0   COMMENT 'Rent paid to owner',
    payout_day      TINYINT UNSIGNED NOT NULL DEFAULT 5  COMMENT 'Day of month owner is paid',
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    notes           TEXT         DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company  (company_id),
    INDEX idx_building (building_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rooms (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      INT UNSIGNED NOT NULL,
    unit_id         INT UNSIGNED NOT NULL,
    room_no         VARCHAR(20)  NOT NULL,
    room_type       ENUM('single','twin','master','studio','common') NOT NULL DEFAULT 'single',
    capacity        TINYINT UNSIGNED NOT NULL DEFAULT 1,
    base_rent       DECIMAL(10,2) NOT NULL DEFAULT 0,
    deposit_months  TINYINT UNSIGNED NOT NULL DEFAULT 0  COMMENT '0 = zero deposit USP',
    has_attached_bath TINYINT(1) NOT NULL DEFAULT 0,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    notes           TEXT         DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company (company_id),
    INDEX idx_unit    (unit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS beds (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    room_id    INT UNSIGNED NOT NULL,
    bed_label  VARCHAR(10)  NOT NULL  COMMENT 'e.g. A, B, 1, 2',
    is_active  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company (company_id),
    INDEX idx_room    (room_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- RESIDENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS residents (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      INT UNSIGNED NOT NULL,
    name            VARCHAR(120) NOT NULL,
    ic_number       VARCHAR(20)  DEFAULT NULL,
    nationality     VARCHAR(50)  NOT NULL DEFAULT 'Malaysian',
    email           VARCHAR(255) DEFAULT NULL,
    phone           VARCHAR(30)  DEFAULT NULL,
    emergency_name  VARCHAR(120) DEFAULT NULL,
    emergency_phone VARCHAR(30)  DEFAULT NULL,
    employer        VARCHAR(120) DEFAULT NULL,
    occupation      VARCHAR(80)  DEFAULT NULL,
    password_hash   VARCHAR(255) DEFAULT NULL  COMMENT 'For portal login',
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_company_email (company_id, email),
    INDEX idx_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- BOOKINGS & TENANCIES
-- ============================================================

CREATE TABLE IF NOT EXISTS bookings (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id    INT UNSIGNED NOT NULL,
    room_id       INT UNSIGNED NOT NULL,
    bed_id        INT UNSIGNED DEFAULT NULL,
    resident_id   INT UNSIGNED DEFAULT NULL  COMMENT 'Set after account created',
    name          VARCHAR(120) NOT NULL,
    email         VARCHAR(255) DEFAULT NULL,
    phone         VARCHAR(30)  DEFAULT NULL,
    move_in_date  DATE         NOT NULL,
    duration_months TINYINT UNSIGNED NOT NULL DEFAULT 1,
    quoted_rent   DECIMAL(10,2) NOT NULL DEFAULT 0,
    status        ENUM('pending','approved','rejected','converted','cancelled') NOT NULL DEFAULT 'pending',
    notes         TEXT         DEFAULT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company (company_id),
    INDEX idx_room    (room_id),
    INDEX idx_status  (company_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tenancies (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id    INT UNSIGNED NOT NULL,
    room_id       INT UNSIGNED NOT NULL,
    bed_id        INT UNSIGNED DEFAULT NULL,
    resident_id   INT UNSIGNED NOT NULL,
    booking_id    INT UNSIGNED DEFAULT NULL,
    start_date    DATE         NOT NULL,
    end_date      DATE         NOT NULL,
    monthly_rent  DECIMAL(10,2) NOT NULL,
    deposit       DECIMAL(10,2) NOT NULL DEFAULT 0,
    billing_day   TINYINT UNSIGNED NOT NULL DEFAULT 1,
    status        ENUM('active','expired','terminated','pending') NOT NULL DEFAULT 'pending',
    contract_path VARCHAR(500) DEFAULT NULL,
    notes         TEXT         DEFAULT NULL,
    created_by    INT UNSIGNED DEFAULT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company  (company_id),
    INDEX idx_room     (room_id),
    INDEX idx_resident (resident_id),
    INDEX idx_status   (company_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- BILLING
-- ============================================================

CREATE TABLE IF NOT EXISTS invoices (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      INT UNSIGNED NOT NULL,
    invoice_no      VARCHAR(30)  NOT NULL,
    tenancy_id      INT UNSIGNED NOT NULL,
    resident_id     INT UNSIGNED NOT NULL,
    period          CHAR(7)      NOT NULL  COMMENT 'YYYY-MM',
    due_date        DATE         NOT NULL,
    subtotal        DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax_amount      DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_amount    DECIMAL(10,2) NOT NULL DEFAULT 0,
    amount_paid     DECIMAL(10,2) NOT NULL DEFAULT 0,
    balance         DECIMAL(10,2) NOT NULL DEFAULT 0,
    status          ENUM('draft','issued','paid','partial','overdue','void') NOT NULL DEFAULT 'draft',
    issued_at       DATETIME     DEFAULT NULL,
    paid_at         DATETIME     DEFAULT NULL,
    notes           TEXT         DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_invoice_no (company_id, invoice_no),
    INDEX idx_company  (company_id),
    INDEX idx_tenancy  (tenancy_id),
    INDEX idx_resident (resident_id),
    INDEX idx_period   (company_id, period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoice_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id  INT UNSIGNED NOT NULL,
    invoice_id  INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity    DECIMAL(10,3) NOT NULL DEFAULT 1,
    unit_price  DECIMAL(10,2) NOT NULL,
    amount      DECIMAL(10,2) NOT NULL,
    item_type   ENUM('rent','utility','deposit','carpark','late_fee','adjustment','other') NOT NULL DEFAULT 'rent',
    INDEX idx_invoice (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      INT UNSIGNED NOT NULL,
    invoice_id      INT UNSIGNED NOT NULL,
    resident_id     INT UNSIGNED NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    payment_method  ENUM('fpx','duitnow','cash','bank_transfer','cheque','other') NOT NULL DEFAULT 'fpx',
    gateway_ref     VARCHAR(100) DEFAULT NULL,
    payment_date    DATE         NOT NULL,
    notes           TEXT         DEFAULT NULL,
    recorded_by     INT UNSIGNED DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company  (company_id),
    INDEX idx_invoice  (invoice_id),
    INDEX idx_resident (resident_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- UTILITIES
-- ============================================================

CREATE TABLE IF NOT EXISTS utility_rates (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id  INT UNSIGNED NOT NULL,
    utility_type ENUM('electric','water','gas','internet','other') NOT NULL DEFAULT 'electric',
    rate_per_unit DECIMAL(8,4) NOT NULL,
    unit_label  VARCHAR(20)  NOT NULL DEFAULT 'kWh',
    effective_from DATE       NOT NULL,
    notes       VARCHAR(255) DEFAULT NULL,
    INDEX idx_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS utility_readings (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id   INT UNSIGNED NOT NULL,
    room_id      INT UNSIGNED NOT NULL,
    utility_type ENUM('electric','water','gas') NOT NULL DEFAULT 'electric',
    period       CHAR(7)      NOT NULL  COMMENT 'YYYY-MM',
    reading_open DECIMAL(12,3) NOT NULL DEFAULT 0,
    reading_close DECIMAL(12,3) NOT NULL DEFAULT 0,
    units_used   DECIMAL(12,3) GENERATED ALWAYS AS (reading_close - reading_open) STORED,
    read_date    DATE         DEFAULT NULL,
    source       ENUM('manual','smart_meter') NOT NULL DEFAULT 'manual',
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_room_period_type (room_id, period, utility_type),
    INDEX idx_company (company_id),
    INDEX idx_room    (room_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- OWNER PAYOUTS
-- ============================================================

CREATE TABLE IF NOT EXISTS owner_payouts (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id    INT UNSIGNED NOT NULL,
    owner_id      INT UNSIGNED NOT NULL,
    unit_id       INT UNSIGNED NOT NULL,
    period        CHAR(7)      NOT NULL,
    gross_rent    DECIMAL(10,2) NOT NULL DEFAULT 0,
    deductions    DECIMAL(10,2) NOT NULL DEFAULT 0,
    net_payout    DECIMAL(10,2) NOT NULL DEFAULT 0,
    status        ENUM('draft','approved','paid') NOT NULL DEFAULT 'draft',
    paid_date     DATE         DEFAULT NULL,
    bank_ref      VARCHAR(100) DEFAULT NULL,
    notes         TEXT         DEFAULT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_unit_period (unit_id, period),
    INDEX idx_company (company_id),
    INDEX idx_owner   (owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- MAINTENANCE
-- ============================================================

CREATE TABLE IF NOT EXISTS maintenance_tickets (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id   INT UNSIGNED NOT NULL,
    room_id      INT UNSIGNED DEFAULT NULL,
    unit_id      INT UNSIGNED DEFAULT NULL,
    resident_id  INT UNSIGNED DEFAULT NULL,
    assigned_to  INT UNSIGNED DEFAULT NULL  COMMENT 'users.id',
    title        VARCHAR(255) NOT NULL,
    description  TEXT         DEFAULT NULL,
    category     ENUM('plumbing','electrical','aircon','furniture','lock','cleaning','pest','other') NOT NULL DEFAULT 'other',
    priority     ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    status       ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
    resolved_at  DATETIME     DEFAULT NULL,
    notes        TEXT         DEFAULT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company (company_id),
    INDEX idx_status  (company_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- IOT STUBS
-- ============================================================

CREATE TABLE IF NOT EXISTS smart_locks (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id  INT UNSIGNED NOT NULL,
    room_id     INT UNSIGNED NOT NULL,
    device_id   VARCHAR(100) NOT NULL,
    vendor      VARCHAR(60)  DEFAULT NULL,
    label       VARCHAR(100) DEFAULT NULL,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    last_sync   DATETIME     DEFAULT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lock_access_logs (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id   INT UNSIGNED NOT NULL,
    lock_id      INT UNSIGNED NOT NULL,
    triggered_by INT UNSIGNED DEFAULT NULL  COMMENT 'users.id for staff remote unlock',
    resident_id  INT UNSIGNED DEFAULT NULL,
    method       VARCHAR(30)  NOT NULL DEFAULT 'pin' COMMENT 'pin,card,remote,app',
    result       VARCHAR(20)  NOT NULL DEFAULT 'success' COMMENT 'success,failed',
    note         VARCHAR(200) DEFAULT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company (company_id),
    INDEX idx_lock    (lock_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- AI SUPPORT
-- ============================================================

CREATE TABLE IF NOT EXISTS support_messages (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id    INT UNSIGNED NOT NULL,
    resident_id   INT UNSIGNED DEFAULT NULL,
    parent_id     INT UNSIGNED DEFAULT NULL  COMMENT 'NULL = thread root',
    sender_type   ENUM('resident','staff','ai') NOT NULL DEFAULT 'resident',
    sender_id     INT UNSIGNED DEFAULT NULL  COMMENT 'users.id when sender_type=staff',
    body          TEXT         NOT NULL,
    status        ENUM('open','closed') NOT NULL DEFAULT 'open' COMMENT 'on root message only',
    is_read       TINYINT(1)   NOT NULL DEFAULT 0,
    is_escalated  TINYINT(1)   NOT NULL DEFAULT 0,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company   (company_id),
    INDEX idx_resident  (resident_id),
    INDEX idx_parent    (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- AUDIT LOG
-- ============================================================

CREATE TABLE IF NOT EXISTS audit_logs (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id   INT UNSIGNED NOT NULL DEFAULT 0,
    user_id      INT UNSIGNED NOT NULL DEFAULT 0,
    action       VARCHAR(80)  NOT NULL,
    target_table VARCHAR(60)  DEFAULT NULL,
    target_id    INT UNSIGNED DEFAULT NULL,
    old_val      JSON         DEFAULT NULL,
    new_val      JSON         DEFAULT NULL,
    ip           VARCHAR(45)  DEFAULT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company (company_id, created_at),
    INDEX idx_target  (target_table, target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED: default plans
-- ============================================================

INSERT IGNORE INTO plans (id, name, max_units, max_staff, price_monthly, price_annually) VALUES
(1, 'Starter',    10,  3,  199,  1990),
(2, 'Growth',     50,  10, 499,  4990),
(3, 'Enterprise', 999, 99, 999,  9990);
