-- STRHub AI — STR Monthly Report Table
-- Stores per-property monthly STR performance data

CREATE TABLE IF NOT EXISTS str_monthly_stats (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id      INT UNSIGNED NOT NULL,
  property_id    INT UNSIGNED NOT NULL,
  period         CHAR(7)      NOT NULL COMMENT 'YYYY-MM',
  platform_sales DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  cleaning_fee   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  days_booked    INT UNSIGNED  NOT NULL DEFAULT 0,
  notes          VARCHAR(255)  DEFAULT NULL,
  updated_by     INT UNSIGNED  DEFAULT NULL,
  updated_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_prop_period (property_id, period),
  INDEX idx_tenant_period  (tenant_id, period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
