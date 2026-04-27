-- STRHub AI — Reservations Module
-- Tables: reservations, reservation_charges, reservation_payments

CREATE TABLE IF NOT EXISTS reservations (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id           INT UNSIGNED NOT NULL,
  property_id         INT UNSIGNED NOT NULL,
  confirmation_code   VARCHAR(100) DEFAULT NULL,
  platform            ENUM('airbnb','booking_com','agoda','expedia','direct','other') NOT NULL DEFAULT 'direct',
  guest_name          VARCHAR(255) NOT NULL,
  guest_email         VARCHAR(255) DEFAULT NULL,
  guest_phone         VARCHAR(30)  DEFAULT NULL,
  guest_ic            VARCHAR(50)  DEFAULT NULL,
  check_in            DATE         NOT NULL,
  check_out           DATE         NOT NULL,
  nights              INT UNSIGNED NOT NULL DEFAULT 1,
  adults              TINYINT UNSIGNED NOT NULL DEFAULT 1,
  children            TINYINT UNSIGNED NOT NULL DEFAULT 0,
  room_rate           DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total room rate (not per night)',
  cleaning_fee        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ota_commission_rate DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  ota_tax_amount      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  sst_rate            DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  sst_amount          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total_charges       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  payment_received    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  security_deposit    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  deposit_collected   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  deposit_refunded    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  outstanding_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status              ENUM('pending','confirmed','checked_in','checked_out','cancelled','no_show') NOT NULL DEFAULT 'confirmed',
  notes               TEXT         DEFAULT NULL,
  created_by          INT UNSIGNED DEFAULT NULL,
  created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tenant          (tenant_id),
  INDEX idx_property        (property_id),
  INDEX idx_check_in        (check_in),
  INDEX idx_tenant_status   (tenant_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reservation_charges (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id       INT UNSIGNED NOT NULL,
  reservation_id  INT UNSIGNED NOT NULL,
  charge_date     DATE         NOT NULL,
  charge_type     VARCHAR(100) NOT NULL,
  amount          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  remarks         TEXT         DEFAULT NULL,
  created_by      INT UNSIGNED DEFAULT NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_reservation (reservation_id),
  INDEX idx_tenant      (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reservation_payments (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id       INT UNSIGNED NOT NULL,
  reservation_id  INT UNSIGNED NOT NULL,
  payment_date    DATE         NOT NULL,
  amount          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  payment_method  ENUM('bank_transfer','cash','credit_card','duitnow','online','deposit','other') DEFAULT 'bank_transfer',
  reference_no    VARCHAR(100) DEFAULT NULL,
  notes           TEXT         DEFAULT NULL,
  created_by      INT UNSIGNED DEFAULT NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_reservation (reservation_id),
  INDEX idx_tenant      (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
