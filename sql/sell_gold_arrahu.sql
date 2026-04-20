-- ============================================================
-- Sell Gold (We Buy Gold) + Ar Rahnu features
-- ============================================================

-- Admin sets daily "we buy" price (separate from market sell price)
CREATE TABLE IF NOT EXISTS gold_sell_prices (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    price_per_g     DECIMAL(10,4)   NOT NULL,
    status          ENUM('active','superseded') NOT NULL DEFAULT 'active',
    notes           VARCHAR(300)    NULL DEFAULT NULL,
    created_by      INT UNSIGNED    NOT NULL,
    effective_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_gsp_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User gold sell requests
CREATE TABLE IF NOT EXISTS gold_sell_requests (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id                 INT UNSIGNED    NOT NULL,
    sell_price_id           INT UNSIGNED    NOT NULL,
    points_amount           DECIMAL(18,6)   NOT NULL,
    grams_amount            DECIMAL(18,6)   NOT NULL,
    price_per_g_snapshot    DECIMAL(10,4)   NOT NULL,
    rm_payout               DECIMAL(12,2)   NOT NULL,
    bank_name               VARCHAR(100)    NOT NULL DEFAULT '',
    bank_account            VARCHAR(50)     NOT NULL DEFAULT '',
    bank_account_name       VARCHAR(200)    NOT NULL DEFAULT '',
    status                  ENUM('pending','approved','paid','rejected','cancelled') NOT NULL DEFAULT 'pending',
    rejection_reason        TEXT            NULL DEFAULT NULL,
    admin_notes             TEXT            NULL DEFAULT NULL,
    ledger_entry_id         INT UNSIGNED    NULL DEFAULT NULL,
    reviewed_by             INT UNSIGNED    NULL DEFAULT NULL,
    reviewed_at             DATETIME        NULL DEFAULT NULL,
    paid_at                 DATETIME        NULL DEFAULT NULL,
    payment_reference       VARCHAR(255)    NULL DEFAULT NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_gsr_user   (user_id),
    INDEX idx_gsr_status (status),
    CONSTRAINT fk_gsr_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ar Rahnu (Islamic Gold Pawn) applications – in collaboration with Kasih Ar Rahnu
CREATE TABLE IF NOT EXISTS ar_rahnu_applications (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id                 INT UNSIGNED    NOT NULL,
    gold_grams              DECIMAL(18,6)   NOT NULL,
    gold_points             DECIMAL(18,6)   NOT NULL,
    gold_purity             ENUM('916','999','9999') NOT NULL DEFAULT '999',
    market_value_snapshot   DECIMAL(12,2)   NOT NULL,
    financing_requested     DECIMAL(12,2)   NOT NULL,
    financing_approved      DECIMAL(12,2)   NULL DEFAULT NULL,
    monthly_ujrah_rate      DECIMAL(6,4)    NULL DEFAULT NULL,
    tenure_months           TINYINT UNSIGNED NULL DEFAULT 6,
    total_ujrah             DECIMAL(12,2)   NULL DEFAULT NULL,
    bank_name               VARCHAR(100)    NOT NULL DEFAULT '',
    bank_account            VARCHAR(50)     NOT NULL DEFAULT '',
    bank_account_name       VARCHAR(200)    NOT NULL DEFAULT '',
    user_notes              TEXT            NULL DEFAULT NULL,
    status                  ENUM('pending','approved','active','redeemed','defaulted','rejected','cancelled') NOT NULL DEFAULT 'pending',
    rejection_reason        TEXT            NULL DEFAULT NULL,
    admin_notes             TEXT            NULL DEFAULT NULL,
    reviewed_by             INT UNSIGNED    NULL DEFAULT NULL,
    reviewed_at             DATETIME        NULL DEFAULT NULL,
    disbursed_at            DATETIME        NULL DEFAULT NULL,
    maturity_date           DATE            NULL DEFAULT NULL,
    redeemed_at             DATETIME        NULL DEFAULT NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_ara_user   (user_id),
    INDEX idx_ara_status (status),
    CONSTRAINT fk_ara_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
