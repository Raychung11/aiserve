-- Gold Stock Control
-- Tracks physical gold inventory in the vault
-- Movements auto-logged when physical redemptions are collected and sell-backs are approved

CREATE TABLE IF NOT EXISTS gold_stock (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    current_grams    DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
    min_alert_grams  DECIMAL(14,4) NOT NULL DEFAULT 100.0000,
    alert_enabled    TINYINT(1)    NOT NULL DEFAULT 1,
    notes            TEXT          NULL,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed one row (singleton)
INSERT IGNORE INTO gold_stock (id, current_grams, min_alert_grams, alert_enabled)
VALUES (1, 0, 100, 1);

CREATE TABLE IF NOT EXISTS gold_stock_movements (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    movement_type   ENUM('restock','redemption_out','buyback_in','adjustment') NOT NULL,
    grams_change    DECIMAL(14,4) NOT NULL,   -- positive = added, negative = removed
    grams_after     DECIMAL(14,4) NOT NULL,
    notes           TEXT          NULL,
    reference_type  VARCHAR(50)   NULL,        -- e.g. 'physical_redemption', 'sell_request'
    reference_id    INT UNSIGNED  NULL,
    created_by      INT UNSIGNED  NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_gsm_type ON gold_stock_movements(movement_type);
CREATE INDEX IF NOT EXISTS idx_gsm_date ON gold_stock_movements(created_at);
