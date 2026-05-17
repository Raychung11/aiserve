-- eBizMedic Phase 5: Online Consultation
-- Run this in phpMyAdmin after migrate_v4.sql

CREATE TABLE consultation_messages (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT  NOT NULL,
    sender_id      INT  NOT NULL,
    message        TEXT NOT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id)      REFERENCES users(id)        ON DELETE CASCADE,
    INDEX idx_chat_appt (appointment_id, created_at)
);
