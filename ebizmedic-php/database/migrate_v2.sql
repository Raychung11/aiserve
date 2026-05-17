-- eBizMedic v2 Migration
-- Run via phpMyAdmin after importing schema.sql

-- 1. Add approval status to users
ALTER TABLE users
    ADD COLUMN approved TINYINT(1) NOT NULL DEFAULT 1 AFTER is_active;

-- New medic/org registrations will be inserted with approved = 0
-- Existing users and admin are set to 1 by default above

-- 2. Medical records table
CREATE TABLE medical_records (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id   INT NOT NULL UNIQUE,
    doctor_id        INT NOT NULL,
    patient_id       INT NOT NULL,
    chief_complaint  TEXT,
    diagnosis        TEXT,
    treatment        TEXT,
    prescription     TEXT,
    follow_up_date   DATE,
    notes            TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id)      REFERENCES doctors(id)      ON DELETE CASCADE,
    FOREIGN KEY (patient_id)     REFERENCES users(id)        ON DELETE CASCADE
);
