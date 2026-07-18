-- ============================================================
-- KASIH GOLD EASY — Gold Deposit & Interest Scheme
-- Run after schema.sql
-- ============================================================

-- Physical gold deposit records
CREATE TABLE IF NOT EXISTS gold_deposits (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id                 INT UNSIGNED    NOT NULL,
    deposit_ref             VARCHAR(20)     NOT NULL,
    gold_grams              DECIMAL(18,6)   NOT NULL,
    gold_purity             ENUM('916','999','9999') NOT NULL DEFAULT '999',
    gold_type               ENUM('jongkong','syiling','barang_kemas') NOT NULL DEFAULT 'jongkong',
    price_per_g_snapshot    DECIMAL(12,4)   NOT NULL DEFAULT 0,
    market_value_snapshot   DECIMAL(12,2)   NOT NULL DEFAULT 0,
    interest_rate_snapshot  DECIMAL(6,4)    NOT NULL DEFAULT 0,
    payout_method           ENUM('wallet','bank') NOT NULL DEFAULT 'wallet',
    bank_name               VARCHAR(100)    NOT NULL DEFAULT '',
    bank_account            VARCHAR(50)     NOT NULL DEFAULT '',
    bank_account_name       VARCHAR(200)    NOT NULL DEFAULT '',
    user_notes              TEXT            NULL DEFAULT NULL,
    admin_notes             TEXT            NULL DEFAULT NULL,
    status                  ENUM('pending','active','pawned','redeemed','cancelled') NOT NULL DEFAULT 'pending',
    verified_by             INT UNSIGNED    NULL DEFAULT NULL,
    verified_at             DATETIME        NULL DEFAULT NULL,
    redeemed_at             DATETIME        NULL DEFAULT NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_dep_ref   (deposit_ref),
    INDEX idx_dep_user      (user_id),
    INDEX idx_dep_status    (status),
    CONSTRAINT fk_dep_user  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Interest / return payment log per deposit per period
CREATE TABLE IF NOT EXISTS gold_deposit_interest (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    deposit_id      INT UNSIGNED    NOT NULL,
    period_label    VARCHAR(20)     NOT NULL,
    period_type     ENUM('monthly','annually') NOT NULL DEFAULT 'monthly',
    grams_deposited DECIMAL(18,6)   NOT NULL,
    interest_rate   DECIMAL(6,4)    NOT NULL,
    interest_grams  DECIMAL(18,6)   NULL DEFAULT NULL,
    interest_rm     DECIMAL(12,2)   NULL DEFAULT NULL,
    price_per_g     DECIMAL(12,4)   NULL DEFAULT NULL,
    payout_method   ENUM('wallet','bank') NOT NULL,
    payout_status   ENUM('pending','paid') NOT NULL DEFAULT 'pending',
    ledger_entry_id INT UNSIGNED    NULL DEFAULT NULL,
    paid_at         DATETIME        NULL DEFAULT NULL,
    admin_notes     TEXT            NULL DEFAULT NULL,
    created_by      INT UNSIGNED    NULL DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_gdi_deposit (deposit_id),
    CONSTRAINT fk_gdi_dep FOREIGN KEY (deposit_id) REFERENCES gold_deposits(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link ar_rahnu applications to a deposit record
ALTER TABLE ar_rahnu_applications
    ADD COLUMN IF NOT EXISTS deposit_id INT UNSIGNED NULL DEFAULT NULL AFTER user_id,
    ADD COLUMN IF NOT EXISTS collateral_type ENUM('digital','physical') NOT NULL DEFAULT 'digital' AFTER deposit_id;

-- Default rate setting (seed)
INSERT IGNORE INTO platform_settings (setting_key, setting_value, updated_by, updated_at)
VALUES ('deposit_monthly_rate', '0.50', 1, NOW()),
       ('deposit_min_grams',    '10.00', 1, NOW());
