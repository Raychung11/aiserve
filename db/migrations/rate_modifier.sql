-- STRHub AI — Rate Modifier Table

CREATE TABLE IF NOT EXISTS rate_modifiers (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id       INT UNSIGNED NOT NULL,
  property_id     INT UNSIGNED DEFAULT NULL COMMENT 'NULL = all properties',
  name            VARCHAR(100) NOT NULL,
  rule_type       ENUM('seasonal','weekend','weekday','length_of_stay','last_minute','early_bird') NOT NULL DEFAULT 'seasonal',
  adjustment_type ENUM('percent','flat') NOT NULL DEFAULT 'percent',
  adjustment      DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '+increase / -discount',
  base_rate       DECIMAL(10,2) DEFAULT NULL COMMENT 'Override base nightly rate if set',
  date_from       DATE DEFAULT NULL,
  date_to         DATE DEFAULT NULL,
  days_of_week    VARCHAR(20)  DEFAULT NULL COMMENT 'CSV 0-6 (Sun=0, Sat=6)',
  min_nights      INT UNSIGNED DEFAULT NULL,
  priority        TINYINT UNSIGNED NOT NULL DEFAULT 10 COMMENT 'Higher = applied first',
  is_active       TINYINT(1)   NOT NULL DEFAULT 1,
  notes           TEXT DEFAULT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tenant   (tenant_id),
  INDEX idx_property (property_id),
  INDEX idx_active   (tenant_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
