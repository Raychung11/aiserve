-- eBizMedic v3 Migration — Dispensary Module
-- Run via phpMyAdmin after migrate_v2.sql

-- 1. Add pharmacist to users role enum
ALTER TABLE users
    MODIFY COLUMN role ENUM('admin','medic','organisation','user','pharmacist') NOT NULL DEFAULT 'user';

-- 2. Medicine inventory per organisation
CREATE TABLE medicines (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    organisation_id INT NOT NULL,
    name            VARCHAR(255) NOT NULL,
    generic_name    VARCHAR(255),
    category        VARCHAR(100),
    unit            VARCHAR(50)  DEFAULT 'tablet',
    stock_qty       INT          NOT NULL DEFAULT 0,
    reorder_level   INT          DEFAULT 10,
    unit_price      DECIMAL(10,2) DEFAULT 0.00,
    description     TEXT,
    is_active       TINYINT(1)   DEFAULT 1,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE
);

-- 3. Stock movement log (in / out / adjustment)
CREATE TABLE stock_movements (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT         NOT NULL,
    type        ENUM('in','out','adjustment') NOT NULL,
    quantity    INT         NOT NULL,
    reference   VARCHAR(255),
    notes       TEXT,
    created_by  INT         NOT NULL,
    created_at  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by)  REFERENCES users(id)
);

-- 4. Dispensing transaction header
CREATE TABLE dispensings (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    patient_id        INT         NOT NULL,
    doctor_id         INT,
    organisation_id   INT         NOT NULL,
    medical_record_id INT,
    dispensed_by      INT         NOT NULL,
    notes             TEXT,
    total_amount      DECIMAL(10,2) DEFAULT 0.00,
    created_at        TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id)        REFERENCES users(id),
    FOREIGN KEY (doctor_id)         REFERENCES doctors(id)        ON DELETE SET NULL,
    FOREIGN KEY (organisation_id)   REFERENCES organisations(id),
    FOREIGN KEY (medical_record_id) REFERENCES medical_records(id) ON DELETE SET NULL,
    FOREIGN KEY (dispensed_by)      REFERENCES users(id)
);

-- 5. Pharmacist-organisation link
CREATE TABLE pharmacists (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL UNIQUE,
    organisation_id INT NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)         REFERENCES users(id)         ON DELETE CASCADE,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id)  ON DELETE CASCADE
);

-- 6. Dispensing line items
CREATE TABLE dispensing_items (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    dispensing_id        INT            NOT NULL,
    medicine_id          INT            NOT NULL,
    quantity             INT            NOT NULL,
    unit_price           DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    dosage_instructions  VARCHAR(255),
    FOREIGN KEY (dispensing_id) REFERENCES dispensings(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id)   REFERENCES medicines(id)
);
