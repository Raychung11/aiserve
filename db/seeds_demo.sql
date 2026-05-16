-- Adcellent ESG OS — Demo Account Seeds
-- Password for ALL accounts: Demo@1234
-- Run AFTER schema.sql and migrate_v4.sql
-- ─────────────────────────────────────────────────────────────

-- Demo users (all share password: Demo@1234)
INSERT IGNORE INTO `users` (`name`, `email`, `password`, `role`, `is_active`) VALUES
  ('Admin',         'admin@demo.com',      '$2y$12$2V/IXoR0Y/Ctn7xVuKdNNu0NnOfbKZwvvOb8ReSmq27xTLefW3y86', 'admin',      1),
  ('Principal Lee', 'principal@demo.com',  '$2y$12$2V/IXoR0Y/Ctn7xVuKdNNu0NnOfbKZwvvOb8ReSmq27xTLefW3y86', 'principal',  1),
  ('Associate Tan', 'associate@demo.com',  '$2y$12$2V/IXoR0Y/Ctn7xVuKdNNu0NnOfbKZwvvOb8ReSmq27xTLefW3y86', 'associate',  1),
  ('Manager Wong',  'manager@demo.com',    '$2y$12$2V/IXoR0Y/Ctn7xVuKdNNu0NnOfbKZwvvOb8ReSmq27xTLefW3y86', 'manager',    1),
  ('Consultant Ng', 'consultant@demo.com', '$2y$12$2V/IXoR0Y/Ctn7xVuKdNNu0NnOfbKZwvvOb8ReSmq27xTLefW3y86', 'consultant', 1),
  ('SME Owner Lim', 'owner@demo.com',      '$2y$12$2V/IXoR0Y/Ctn7xVuKdNNu0NnOfbKZwvvOb8ReSmq27xTLefW3y86', 'sme_owner',  1);

-- Set hierarchy: Associate's parent = Principal, Manager's parent = Associate
UPDATE `users` SET parent_id = (SELECT id FROM (SELECT id FROM `users` WHERE email = 'principal@demo.com') t)
  WHERE email = 'associate@demo.com';
UPDATE `users` SET parent_id = (SELECT id FROM (SELECT id FROM `users` WHERE email = 'associate@demo.com') t)
  WHERE email = 'manager@demo.com';

-- Demo company (owned by SME Owner)
INSERT IGNORE INTO `companies`
  (`name`, `registration_no`, `industry`, `revenue_tier`, `employee_count`, `framework`, `reporting_year`, `created_by`)
  VALUES (
    'Demo Sdn Bhd', '202401012345', 'Manufacturing', '10M_to_50M', 120,
    'BURSA_SEDG', YEAR(NOW()),
    (SELECT id FROM `users` WHERE email = 'owner@demo.com')
  );

-- Link SME Owner to demo company as owner
INSERT IGNORE INTO `user_companies` (`user_id`, `company_id`, `role`)
  SELECT u.id, c.id, 'owner'
  FROM `users` u, `companies` c
  WHERE u.email = 'owner@demo.com' AND c.name = 'Demo Sdn Bhd';

-- Link Consultant to demo company as editor
INSERT IGNORE INTO `user_companies` (`user_id`, `company_id`, `role`)
  SELECT u.id, c.id, 'editor'
  FROM `users` u, `companies` c
  WHERE u.email = 'consultant@demo.com' AND c.name = 'Demo Sdn Bhd';
