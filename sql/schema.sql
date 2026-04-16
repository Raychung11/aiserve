-- =============================================================================
-- schema.sql for KASIH GOLD EASY
-- Engine  : MySQL / MariaDB
-- Charset : utf8mb4 / utf8mb4_unicode_ci
-- DECIMAL used for all money, points, and grams columns
-- Indexes on all foreign-key columns
-- Generated: 2026-04-16
-- =============================================================================

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS ai_conversations;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS user_campaigns;
DROP TABLE IF EXISTS campaigns;
DROP TABLE IF EXISTS merchant_payout_requests;
DROP TABLE IF EXISTS marketplace_order_items;
DROP TABLE IF EXISTS marketplace_orders;
DROP TABLE IF EXISTS marketplace_products;
DROP TABLE IF EXISTS marketplace_categories;
DROP TABLE IF EXISTS referral_settings;
DROP TABLE IF EXISTS referral_commissions;
DROP TABLE IF EXISTS referrals;
DROP TABLE IF EXISTS payment_transactions;
DROP TABLE IF EXISTS gold_purchases;
DROP TABLE IF EXISTS wallet_transfers;
DROP TABLE IF EXISTS wallet_ledger;
DROP TABLE IF EXISTS wallets;
DROP TABLE IF EXISTS gold_prices;
DROP TABLE IF EXISTS merchants;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS=1;

-- =============================================================================
-- 1. users
-- =============================================================================
CREATE TABLE users (
    id                  INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    full_name           VARCHAR(150)   NOT NULL DEFAULT '',
    email               VARCHAR(150)   NOT NULL,
    phone               VARCHAR(30)    NULL DEFAULT NULL,
    password_hash       VARCHAR(255)   NOT NULL DEFAULT '',
    role                ENUM('super_admin','merchant','user') NOT NULL DEFAULT 'user',
    status              ENUM('active','pending','suspended','rejected') NOT NULL DEFAULT 'pending',
    referral_code       VARCHAR(20)    NULL DEFAULT NULL,
    referred_by_user_id INT UNSIGNED   NULL DEFAULT NULL,
    email_verified_at   TIMESTAMP      NULL DEFAULT NULL,
    phone_verified_at   TIMESTAMP      NULL DEFAULT NULL,
    kyc_verified_at     TIMESTAMP      NULL DEFAULT NULL,
    profile_photo       VARCHAR(255)   NULL DEFAULT NULL,
    created_at          TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email         (email),
    UNIQUE KEY uq_users_referral_code (referral_code),
    INDEX      idx_users_role         (role),
    INDEX      idx_users_status       (status),
    INDEX      idx_users_referred_by  (referred_by_user_id),

    CONSTRAINT fk_users_referred_by
        FOREIGN KEY (referred_by_user_id)
        REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 2. merchants
-- =============================================================================
CREATE TABLE merchants (
    id                  INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    user_id             INT UNSIGNED   NOT NULL,
    company_name        VARCHAR(200)   NOT NULL DEFAULT '',
    contact_name        VARCHAR(150)   NOT NULL DEFAULT '',
    registration_no     VARCHAR(100)   NULL DEFAULT NULL,
    business_address    TEXT           NULL DEFAULT NULL,
    logo                VARCHAR(255)   NULL DEFAULT NULL,
    banner              VARCHAR(255)   NULL DEFAULT NULL,
    status              ENUM('pending','active','suspended','rejected') NOT NULL DEFAULT 'pending',
    payout_bank         VARCHAR(100)   NULL DEFAULT NULL,
    payout_account_no   VARCHAR(50)    NULL DEFAULT NULL,
    payout_account_name VARCHAR(150)   NULL DEFAULT NULL,
    verification_notes  TEXT           NULL DEFAULT NULL,
    approved_by         INT UNSIGNED   NULL DEFAULT NULL,
    approved_at         TIMESTAMP      NULL DEFAULT NULL,
    created_at          TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_merchants_user_id   (user_id),
    INDEX      idx_merchants_approved_by (approved_by),
    INDEX      idx_merchants_status    (status),

    CONSTRAINT fk_merchants_user_id
        FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_merchants_approved_by
        FOREIGN KEY (approved_by)
        REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 3. gold_prices
-- =============================================================================
CREATE TABLE gold_prices (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    price_per_g  DECIMAL(10,4) NOT NULL,
    effective_at TIMESTAMP     NOT NULL,
    status       ENUM('active','superseded','scheduled') NOT NULL DEFAULT 'active',
    notes        TEXT          NULL DEFAULT NULL,
    created_by   INT UNSIGNED  NOT NULL,
    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_gold_prices_status       (status),
    INDEX idx_gold_prices_effective_at (effective_at),
    INDEX idx_gold_prices_created_by   (created_by),

    CONSTRAINT fk_gold_prices_created_by
        FOREIGN KEY (created_by)
        REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 4. wallets
-- =============================================================================
CREATE TABLE wallets (
    id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id        INT UNSIGNED  NOT NULL,
    points_balance DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    grams_balance  DECIMAL(18,6) NOT NULL DEFAULT 0.000000,
    created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_wallets_user_id (user_id),

    CONSTRAINT fk_wallets_user_id
        FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 5. wallet_ledger
-- =============================================================================
CREATE TABLE wallet_ledger (
    id                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    wallet_id            INT UNSIGNED  NOT NULL,
    user_id              INT UNSIGNED  NOT NULL,
    direction            ENUM('credit','debit') NOT NULL,
    points               DECIMAL(18,4) NOT NULL,
    grams                DECIMAL(18,6) NOT NULL,
    rm_reference_value   DECIMAL(12,2) NULL DEFAULT NULL,
    price_per_g_snapshot DECIMAL(10,4) NOT NULL,
    source_type          ENUM(
                             'buy_credit',
                             'transfer_in',
                             'transfer_out',
                             'referral_bonus',
                             'marketplace_spend',
                             'marketplace_receive',
                             'admin_adjustment',
                             'payout_debit',
                             'refund_credit',
                             'campaign_bonus',
                             'zakat_deduction'
                         ) NOT NULL,
    source_id            INT UNSIGNED  NULL DEFAULT NULL,
    description          VARCHAR(500)  NULL DEFAULT NULL,
    status               ENUM('pending','completed','reversed') NOT NULL DEFAULT 'completed',
    created_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_wallet_ledger_wallet_id   (wallet_id),
    INDEX idx_wallet_ledger_user_id     (user_id),
    INDEX idx_wallet_ledger_source_type (source_type),
    INDEX idx_wallet_ledger_created_at  (created_at),

    CONSTRAINT fk_wallet_ledger_wallet_id
        FOREIGN KEY (wallet_id)
        REFERENCES wallets (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_wallet_ledger_user_id
        FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 6. wallet_transfers
-- =============================================================================
CREATE TABLE wallet_transfers (
    id                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    sender_user_id       INT UNSIGNED  NOT NULL,
    receiver_user_id     INT UNSIGNED  NOT NULL,
    points               DECIMAL(18,4) NULL DEFAULT NULL,
    grams                DECIMAL(18,6) NULL DEFAULT NULL,
    price_per_g_snapshot DECIMAL(10,4) NULL DEFAULT NULL,
    rm_reference_value   DECIMAL(12,2) NULL DEFAULT NULL,
    transfer_status      ENUM('pending','completed','failed','reversed') NOT NULL DEFAULT 'pending',
    note                 VARCHAR(500)  NULL DEFAULT NULL,
    sender_ledger_id     INT UNSIGNED  NULL DEFAULT NULL,
    receiver_ledger_id   INT UNSIGNED  NULL DEFAULT NULL,
    created_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at         TIMESTAMP     NULL DEFAULT NULL,

    PRIMARY KEY (id),
    INDEX idx_wallet_transfers_sender   (sender_user_id),
    INDEX idx_wallet_transfers_receiver (receiver_user_id),

    CONSTRAINT fk_wallet_transfers_sender
        FOREIGN KEY (sender_user_id)
        REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_wallet_transfers_receiver
        FOREIGN KEY (receiver_user_id)
        REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 7. gold_purchases
-- =============================================================================
CREATE TABLE gold_purchases (
    id                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id              INT UNSIGNED  NOT NULL,
    rm_amount            DECIMAL(12,2) NOT NULL,
    price_per_g_snapshot DECIMAL(10,4) NOT NULL,
    points_credited      DECIMAL(18,4) NOT NULL DEFAULT 0,
    grams_credited       DECIMAL(18,6) NOT NULL DEFAULT 0,
    payment_status       ENUM('pending','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
    purchase_status      ENUM('pending','processing','credited','failed') NOT NULL DEFAULT 'pending',
    payment_reference    VARCHAR(200)  NULL DEFAULT NULL,
    payment_gateway      VARCHAR(50)   NOT NULL DEFAULT 'manual',
    ledger_entry_id      INT UNSIGNED  NULL DEFAULT NULL,
    created_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    paid_at              TIMESTAMP     NULL DEFAULT NULL,

    PRIMARY KEY (id),
    INDEX idx_gold_purchases_user_id        (user_id),
    INDEX idx_gold_purchases_payment_status (payment_status),

    CONSTRAINT fk_gold_purchases_user_id
        FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 8. payment_transactions
-- =============================================================================
CREATE TABLE payment_transactions (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    purchase_id      INT UNSIGNED  NOT NULL,
    gateway          VARCHAR(50)   NOT NULL DEFAULT '',
    amount           DECIMAL(12,2) NOT NULL,
    currency         VARCHAR(10)   NOT NULL DEFAULT 'MYR',
    reference        VARCHAR(200)  NOT NULL DEFAULT '',
    status           ENUM('pending','success','failed','cancelled') NOT NULL DEFAULT 'pending',
    gateway_response JSON          NULL DEFAULT NULL,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_payment_transactions_purchase_id (purchase_id),
    INDEX idx_payment_transactions_status      (status),

    CONSTRAINT fk_payment_transactions_purchase_id
        FOREIGN KEY (purchase_id)
        REFERENCES gold_purchases (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 9. referrals
-- =============================================================================
CREATE TABLE referrals (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    referrer_user_id INT UNSIGNED NOT NULL,
    referred_user_id INT UNSIGNED NOT NULL,
    level            TINYINT      NOT NULL DEFAULT 1,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_referrals_referred_user_id (referred_user_id),
    INDEX      idx_referrals_referrer_user_id (referrer_user_id),

    CONSTRAINT fk_referrals_referrer
        FOREIGN KEY (referrer_user_id)
        REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_referrals_referred
        FOREIGN KEY (referred_user_id)
        REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 10. referral_commissions
-- =============================================================================
CREATE TABLE referral_commissions (
    id                      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    referral_id             INT UNSIGNED  NOT NULL,
    beneficiary_user_id     INT UNSIGNED  NOT NULL,
    source_user_id          INT UNSIGNED  NOT NULL,
    source_transaction_type VARCHAR(50)   NOT NULL DEFAULT '',
    source_transaction_id   INT UNSIGNED  NULL DEFAULT NULL,
    level                   TINYINT       NULL DEFAULT NULL,
    rate_percent            DECIMAL(5,2)  NULL DEFAULT NULL,
    points_earned           DECIMAL(18,4) NULL DEFAULT NULL,
    grams_earned            DECIMAL(18,6) NULL DEFAULT NULL,
    rm_value                DECIMAL(12,2) NULL DEFAULT NULL,
    status                  ENUM('pending','credited','cancelled') NOT NULL DEFAULT 'pending',
    ledger_entry_id         INT UNSIGNED  NULL DEFAULT NULL,
    created_at              TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_referral_commissions_referral_id         (referral_id),
    INDEX idx_referral_commissions_beneficiary_user_id (beneficiary_user_id),
    INDEX idx_referral_commissions_source_user_id      (source_user_id),

    CONSTRAINT fk_referral_commissions_referral
        FOREIGN KEY (referral_id)
        REFERENCES referrals (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_referral_commissions_beneficiary
        FOREIGN KEY (beneficiary_user_id)
        REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_referral_commissions_source
        FOREIGN KEY (source_user_id)
        REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 11. referral_settings
-- =============================================================================
CREATE TABLE referral_settings (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    level        TINYINT      NOT NULL,
    rate_percent DECIMAL(5,2) NOT NULL,
    is_active    TINYINT(1)   NOT NULL DEFAULT 1,
    updated_by   INT UNSIGNED NULL DEFAULT NULL,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_referral_settings_level    (level),
    INDEX      idx_referral_settings_updated_by (updated_by),

    CONSTRAINT fk_referral_settings_updated_by
        FOREIGN KEY (updated_by)
        REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 12. marketplace_categories
-- =============================================================================
CREATE TABLE marketplace_categories (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL DEFAULT '',
    slug        VARCHAR(120) NOT NULL,
    description TEXT         NULL DEFAULT NULL,
    icon        VARCHAR(100) NULL DEFAULT NULL,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order  INT          NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_marketplace_categories_slug      (slug),
    INDEX      idx_marketplace_categories_active   (is_active),
    INDEX      idx_marketplace_categories_sort     (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 13. marketplace_products
-- =============================================================================
CREATE TABLE marketplace_products (
    id                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    merchant_id        INT UNSIGNED  NOT NULL,
    category_id        INT UNSIGNED  NOT NULL,
    title              VARCHAR(300)  NOT NULL DEFAULT '',
    slug               VARCHAR(350)  NOT NULL,
    description        TEXT          NULL DEFAULT NULL,
    points_price       DECIMAL(18,4) NOT NULL,
    rm_reference_value DECIMAL(12,2) NOT NULL,
    stock_qty          INT           NULL DEFAULT NULL,
    status             ENUM('draft','active','sold_out','suspended') NOT NULL DEFAULT 'draft',
    image              VARCHAR(255)  NULL DEFAULT NULL,
    is_featured        TINYINT(1)    NOT NULL DEFAULT 0,
    campaign_tag       VARCHAR(100)  NULL DEFAULT NULL,
    deleted_at         TIMESTAMP     NULL DEFAULT NULL,
    created_at         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_marketplace_products_slug       (slug),
    INDEX      idx_marketplace_products_merchant  (merchant_id),
    INDEX      idx_marketplace_products_category  (category_id),
    INDEX      idx_marketplace_products_status    (status),
    INDEX      idx_marketplace_products_featured  (is_featured),

    CONSTRAINT fk_marketplace_products_merchant
        FOREIGN KEY (merchant_id)
        REFERENCES merchants (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_marketplace_products_category
        FOREIGN KEY (category_id)
        REFERENCES marketplace_categories (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 14. marketplace_orders
-- =============================================================================
CREATE TABLE marketplace_orders (
    id                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    buyer_user_id      INT UNSIGNED  NOT NULL,
    merchant_id        INT UNSIGNED  NOT NULL,
    status             ENUM(
                           'pending',
                           'paid_by_points',
                           'merchant_processing',
                           'completed',
                           'cancelled',
                           'refunded'
                       ) NOT NULL DEFAULT 'pending',
    total_points       DECIMAL(18,4) NOT NULL,
    total_rm_value     DECIMAL(12,2) NOT NULL,
    note               TEXT          NULL DEFAULT NULL,
    buyer_ledger_id    INT UNSIGNED  NULL DEFAULT NULL,
    merchant_ledger_id INT UNSIGNED  NULL DEFAULT NULL,
    created_at         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_marketplace_orders_buyer    (buyer_user_id),
    INDEX idx_marketplace_orders_merchant (merchant_id),
    INDEX idx_marketplace_orders_status   (status),

    CONSTRAINT fk_marketplace_orders_buyer
        FOREIGN KEY (buyer_user_id)
        REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_marketplace_orders_merchant
        FOREIGN KEY (merchant_id)
        REFERENCES merchants (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 15. marketplace_order_items
-- =============================================================================
CREATE TABLE marketplace_order_items (
    id                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    order_id           INT UNSIGNED  NOT NULL,
    product_id         INT UNSIGNED  NOT NULL,
    qty                INT           NOT NULL DEFAULT 1,
    points_price       DECIMAL(18,4) NOT NULL,
    rm_reference_value DECIMAL(12,2) NOT NULL,
    product_title      VARCHAR(300)  NOT NULL DEFAULT '',
    created_at         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_marketplace_order_items_order   (order_id),
    INDEX idx_marketplace_order_items_product (product_id),

    CONSTRAINT fk_marketplace_order_items_order
        FOREIGN KEY (order_id)
        REFERENCES marketplace_orders (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_marketplace_order_items_product
        FOREIGN KEY (product_id)
        REFERENCES marketplace_products (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 16. merchant_payout_requests
-- =============================================================================
CREATE TABLE merchant_payout_requests (
    id                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    merchant_id          INT UNSIGNED  NOT NULL,
    points_amount        DECIMAL(18,4) NOT NULL,
    rm_equivalent        DECIMAL(12,2) NOT NULL,
    price_per_g_snapshot DECIMAL(10,4) NOT NULL,
    status               ENUM('pending','approved','paid','rejected') NOT NULL DEFAULT 'pending',
    payout_method        VARCHAR(100)  NOT NULL DEFAULT '',
    note                 TEXT          NULL DEFAULT NULL,
    admin_note           TEXT          NULL DEFAULT NULL,
    requested_at         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at         TIMESTAMP     NULL DEFAULT NULL,
    processed_by         INT UNSIGNED  NULL DEFAULT NULL,

    PRIMARY KEY (id),
    INDEX idx_merchant_payout_requests_merchant  (merchant_id),
    INDEX idx_merchant_payout_requests_status    (status),
    INDEX idx_merchant_payout_requests_processed (processed_by),

    CONSTRAINT fk_merchant_payout_requests_merchant
        FOREIGN KEY (merchant_id)
        REFERENCES merchants (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_merchant_payout_requests_processed_by
        FOREIGN KEY (processed_by)
        REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 17. campaigns
-- =============================================================================
CREATE TABLE campaigns (
    id                             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    title                          VARCHAR(200)  NOT NULL DEFAULT '',
    slug                           VARCHAR(220)  NOT NULL,
    description                    TEXT          NULL DEFAULT NULL,
    type                           ENUM(
                                       'funeral_savings',
                                       'family_savings',
                                       'child_savings',
                                       'senior_care',
                                       'koperasi',
                                       'merchant_promo',
                                       'general'
                                   ) NOT NULL DEFAULT 'general',
    target_points                  DECIMAL(18,4) NULL DEFAULT NULL,
    target_rm_estimate             DECIMAL(12,2) NULL DEFAULT NULL,
    target_date                    DATE          NULL DEFAULT NULL,
    monthly_suggested_contribution DECIMAL(18,4) NULL DEFAULT NULL,
    banner                         VARCHAR(255)  NULL DEFAULT NULL,
    cta_text                       VARCHAR(200)  NULL DEFAULT NULL,
    is_active                      TINYINT(1)    NOT NULL DEFAULT 1,
    created_by                     INT UNSIGNED  NULL DEFAULT NULL,
    created_at                     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_campaigns_slug        (slug),
    INDEX      idx_campaigns_type       (type),
    INDEX      idx_campaigns_is_active  (is_active),
    INDEX      idx_campaigns_created_by (created_by),

    CONSTRAINT fk_campaigns_created_by
        FOREIGN KEY (created_by)
        REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 18. user_campaigns
-- =============================================================================
CREATE TABLE user_campaigns (
    id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id        INT UNSIGNED  NOT NULL,
    campaign_id    INT UNSIGNED  NOT NULL,
    current_points DECIMAL(18,4) NOT NULL DEFAULT 0,
    joined_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uk_user_campaign              (user_id, campaign_id),
    INDEX      idx_user_campaigns_user       (user_id),
    INDEX      idx_user_campaigns_campaign   (campaign_id),

    CONSTRAINT fk_user_campaigns_user
        FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_user_campaigns_campaign
        FOREIGN KEY (campaign_id)
        REFERENCES campaigns (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 19. settings
-- =============================================================================
CREATE TABLE settings (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key   VARCHAR(100) NOT NULL,
    setting_value TEXT         NULL DEFAULT NULL,
    description   VARCHAR(300) NULL DEFAULT NULL,
    is_public     TINYINT(1)   NOT NULL DEFAULT 0,
    updated_by    INT UNSIGNED NULL DEFAULT NULL,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_settings_key         (setting_key),
    INDEX      idx_settings_updated_by (updated_by),

    CONSTRAINT fk_settings_updated_by
        FOREIGN KEY (updated_by)
        REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 20. ai_conversations
-- =============================================================================
CREATE TABLE ai_conversations (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED NULL DEFAULT NULL,
    session_token   VARCHAR(100) NOT NULL DEFAULT '',
    actor_role      ENUM('super_admin','merchant','user','guest') NOT NULL DEFAULT 'guest',
    message_role    ENUM('user','assistant','system') NOT NULL,
    message         TEXT         NOT NULL,
    action_detected VARCHAR(100) NULL DEFAULT NULL,
    context_json    JSON         NULL DEFAULT NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_ai_conversations_session (session_token),
    INDEX idx_ai_conversations_user    (user_id),
    INDEX idx_ai_conversations_created (created_at),

    CONSTRAINT fk_ai_conversations_user_id
        FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 21. audit_logs
-- Note: actor_user_id has NO foreign key constraint intentionally,
--       so that deleted-user records are preserved in the audit trail.
-- =============================================================================
CREATE TABLE audit_logs (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_user_id  INT UNSIGNED    NULL DEFAULT NULL,
    actor_role     VARCHAR(30)     NULL DEFAULT NULL,
    action_type    VARCHAR(100)    NOT NULL DEFAULT '',
    target_type    VARCHAR(100)    NULL DEFAULT NULL,
    target_id      INT UNSIGNED    NULL DEFAULT NULL,
    old_value_json JSON            NULL DEFAULT NULL,
    new_value_json JSON            NULL DEFAULT NULL,
    ip_address     VARCHAR(45)     NULL DEFAULT NULL,
    user_agent     VARCHAR(500)    NULL DEFAULT NULL,
    created_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_audit_logs_actor_user_id (actor_user_id),
    INDEX idx_audit_logs_action_type   (action_type),
    INDEX idx_audit_logs_created_at    (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- End of schema.sql
-- =============================================================================
