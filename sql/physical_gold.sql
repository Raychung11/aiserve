-- Physical Gold Redemption
-- 0.2g per plate minimum; user collects from office

CREATE TABLE IF NOT EXISTS gold_physical_redemptions (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED NOT NULL,
    plates_count        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    grams_per_plate     DECIMAL(10,6) NOT NULL DEFAULT 0.200000,
    total_grams         DECIMAL(10,6) NOT NULL,
    points_redeemed     DECIMAL(18,4) NOT NULL,
    price_per_g_snapshot DECIMAL(12,4) NOT NULL DEFAULT 0,
    ledger_entry_id     INT UNSIGNED NULL,
    pickup_reference    VARCHAR(20) NULL,
    status              ENUM('pending','approved','ready','collected','rejected','cancelled') NOT NULL DEFAULT 'pending',
    user_notes          TEXT NULL,
    admin_notes         TEXT NULL,
    approved_by         INT UNSIGNED NULL,
    approved_at         DATETIME NULL,
    ready_at            DATETIME NULL,
    collected_at        DATETIME NULL,
    rejected_at         DATETIME NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_gpr_user   ON gold_physical_redemptions(user_id);
CREATE INDEX IF NOT EXISTS idx_gpr_status ON gold_physical_redemptions(status);
