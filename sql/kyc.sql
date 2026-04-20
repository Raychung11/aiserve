-- eKYC submissions table
CREATE TABLE IF NOT EXISTS kyc_submissions (
    id                  INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    user_id             INT UNSIGNED   NOT NULL,
    full_name           VARCHAR(200)   NOT NULL DEFAULT '',
    ic_number           VARCHAR(30)    NOT NULL DEFAULT '',
    date_of_birth       DATE           NOT NULL,
    address             TEXT           NOT NULL,
    city                VARCHAR(100)   NOT NULL DEFAULT '',
    state               VARCHAR(100)   NOT NULL DEFAULT '',
    postcode            VARCHAR(10)    NOT NULL DEFAULT '',
    phone               VARCHAR(30)    NULL DEFAULT NULL,
    ic_front            VARCHAR(255)   NOT NULL DEFAULT '',
    ic_back             VARCHAR(255)   NOT NULL DEFAULT '',
    status              ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    rejection_reason    TEXT           NULL DEFAULT NULL,
    reviewed_by         INT UNSIGNED   NULL DEFAULT NULL,
    reviewed_at         DATETIME       NULL DEFAULT NULL,
    submitted_at        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_kyc_user (user_id),
    INDEX idx_kyc_status (status),
    CONSTRAINT fk_kyc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
