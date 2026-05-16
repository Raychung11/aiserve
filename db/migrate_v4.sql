-- Adcellent ESG OS — Migration v4
-- Adds 3-level consultant hierarchy: principal → associate → manager

-- Update role ENUM to include hierarchy roles
ALTER TABLE users
  MODIFY COLUMN role ENUM('admin','consultant','sme_owner','principal','associate','manager')
    NOT NULL DEFAULT 'sme_owner';

-- Add parent_id for hierarchy (associate's parent = principal; manager's parent = associate)
ALTER TABLE users
  ADD COLUMN parent_id INT UNSIGNED NULL DEFAULT NULL AFTER role,
  ADD KEY idx_users_parent (parent_id);

ALTER TABLE users
  ADD CONSTRAINT fk_users_parent FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL;
