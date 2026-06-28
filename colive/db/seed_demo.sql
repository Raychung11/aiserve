-- ============================================================
-- CoLive OS — Demo Seed Data
-- Company: SkyRise Living Sdn. Bhd.
-- ============================================================
-- All passwords: Demo@1234
-- Platform admin  : admin@slvgroup.my
-- Operator admin  : admin@skyrise.my
-- Staff manager   : manager@skyrise.my
-- Staff finance   : finance@skyrise.my
-- Staff maint     : maint@skyrise.my
-- Owner 1 portal  : datuk.razif@mail.com      / Demo@1234
-- Owner 2 portal  : puan.nora@mail.com        / Demo@1234
-- Resident portal : ali.hassan@mail.com       / Demo@1234  (and others below)
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+08:00';
SET FOREIGN_KEY_CHECKS = 0;

-- Clear all tables before seeding (safe to re-run)
TRUNCATE TABLE audit_logs;
TRUNCATE TABLE support_messages;
TRUNCATE TABLE lock_access_logs;
TRUNCATE TABLE smart_locks;
TRUNCATE TABLE maintenance_tickets;
TRUNCATE TABLE owner_payouts;
TRUNCATE TABLE utility_readings;
TRUNCATE TABLE utility_rates;
TRUNCATE TABLE payments;
TRUNCATE TABLE invoice_items;
TRUNCATE TABLE invoices;
TRUNCATE TABLE tenancies;
TRUNCATE TABLE bookings;
TRUNCATE TABLE residents;
TRUNCATE TABLE beds;
TRUNCATE TABLE rooms;
TRUNCATE TABLE units;
TRUNCATE TABLE buildings;
TRUNCATE TABLE owners;
TRUNCATE TABLE users;
TRUNCATE TABLE companies;
TRUNCATE TABLE platform_admins;

-- Hash for Demo@1234
SET @pw = '$2y$10$UikqzBZZTPafEX/RDnBRdOktSIFnAIWgu5XcNRf9ZEqUVocgNh5G.';

-- ============================================================
-- PLATFORM
-- ============================================================

INSERT INTO platform_admins (id, name, email, password_hash, is_active) VALUES
(1, 'SLV Admin', 'admin@slvgroup.my', @pw, 1);

-- ============================================================
-- COMPANY
-- ============================================================

INSERT INTO companies
  (id, name, email, phone, address, ssm_reg, brand_color,
   plan_id, status, trial_ends_at, sub_ends_at,
   invoice_prefix, invoice_seq, billing_email)
VALUES
(1,
 'SkyRise Living Sdn. Bhd.',
 'admin@skyrise.my',
 '03-2111 9988',
 'Level 12, Menara SkyRise, Jalan Ampang, 50450 Kuala Lumpur',
 '1234567-W',
 '#7c3aed',
 2,
 'active',
 NULL,
 DATE_ADD(NOW(), INTERVAL 365 DAY),
 'INV',
 28,
 'billing@skyrise.my');

-- ============================================================
-- USERS (staff)
-- ============================================================

INSERT INTO users (id, company_id, name, email, phone, password_hash, role, is_active) VALUES
(1, 1, 'Ahmad Farid',      'admin@skyrise.my',    '012-3456789', @pw, 'admin',            1),
(2, 1, 'Nurul Izzati',     'manager@skyrise.my',  '011-2345678', @pw, 'manager',          1),
(3, 1, 'Siti Rahimah',     'finance@skyrise.my',  '013-3456789', @pw, 'finance',          1),
(4, 1, 'Hakim Rosli',      'maint@skyrise.my',    '019-4567890', @pw, 'maintenance',      1),
(5, 1, 'Amirah Zulkifli',  'tr@skyrise.my',       '016-5678901', @pw, 'tenant_relations', 1);

-- ============================================================
-- OWNERS
-- ============================================================

INSERT INTO owners
  (id, company_id, name, ic_number, email, phone,
   bank_name, bank_account, bank_holder, portal_password, is_active)
VALUES
(1, 1, 'Datuk Razif Osman',  '680101015555',
   'datuk.razif@mail.com', '012-9988776',
   'Maybank', '5641 2345 6789', 'Razif Bin Osman', @pw, 1),
(2, 1, 'Puan Nora Hashim',   '730215025566',
   'puan.nora@mail.com',   '017-8877665',
   'CIMB',    '8012 3456 7890', 'Nora Binti Hashim', @pw, 1),
(3, 1, 'Lee Chong Fatt',     '790520105577',
   'lee.chongfatt@mail.com', '016-7766554',
   'Public Bank', '3123 4567 8901', 'Lee Chong Fatt', NULL, 1);

-- ============================================================
-- BUILDINGS
-- ============================================================

INSERT INTO buildings (id, company_id, name, address, city, state, postcode) VALUES
(1, 1, 'SkyRise Block A', 'No. 88, Jalan Gombak', 'Kuala Lumpur', 'WP Kuala Lumpur', '53000'),
(2, 1, 'SkyRise Block B', 'No. 88, Jalan Gombak', 'Kuala Lumpur', 'WP Kuala Lumpur', '53000'),
(3, 1, 'Vista Sentul',    'Lot 5, Jalan Sentul Pasar', 'Kuala Lumpur', 'WP Kuala Lumpur', '51000');

-- ============================================================
-- UNITS
-- ============================================================

INSERT INTO units (id, company_id, building_id, owner_id, unit_no, floor, total_rooms, master_rent, payout_day, is_active) VALUES
-- Block A
(1,  1, 1, 1, 'A-01-01', 1, 4, 3200.00, 5, 1),
(2,  1, 1, 1, 'A-01-02', 1, 3, 2400.00, 5, 1),
(3,  1, 1, 2, 'A-02-01', 2, 4, 3400.00, 5, 1),
-- Block B
(4,  1, 2, 2, 'B-01-01', 1, 4, 3200.00, 5, 1),
(5,  1, 2, 3, 'B-02-01', 2, 3, 2600.00, 5, 1),
-- Vista Sentul
(6,  1, 3, 3, 'VS-3A',   3, 5, 4000.00, 5, 1),
(7,  1, 3, 1, 'VS-4B',   4, 4, 3600.00, 5, 1);

-- ============================================================
-- ROOMS
-- ============================================================

INSERT INTO rooms (id, company_id, unit_id, room_no, room_type, capacity, base_rent, deposit_months, has_attached_bath, is_active) VALUES
-- A-01-01
(1,  1, 1, 'A1-M',  'master', 2, 980.00,  2, 1, 1),
(2,  1, 1, 'A1-S1', 'single', 1, 680.00,  1, 0, 1),
(3,  1, 1, 'A1-S2', 'single', 1, 680.00,  1, 0, 1),
(4,  1, 1, 'A1-S3', 'single', 1, 650.00,  1, 0, 1),
-- A-01-02
(5,  1, 2, 'A2-M',  'master', 2, 950.00,  2, 1, 1),
(6,  1, 2, 'A2-S1', 'single', 1, 650.00,  1, 0, 1),
(7,  1, 2, 'A2-S2', 'single', 1, 620.00,  1, 0, 1),
-- A-02-01
(8,  1, 3, 'B1-M',  'master', 2, 1050.00, 2, 1, 1),
(9,  1, 3, 'B1-S1', 'single', 1, 720.00,  1, 0, 1),
(10, 1, 3, 'B1-S2', 'single', 1, 720.00,  1, 0, 1),
(11, 1, 3, 'B1-S3', 'twin',   2, 550.00,  1, 0, 1),
-- B-01-01
(12, 1, 4, 'C1-M',  'master', 2, 980.00,  2, 1, 1),
(13, 1, 4, 'C1-S1', 'single', 1, 680.00,  1, 0, 1),
(14, 1, 4, 'C1-S2', 'single', 1, 660.00,  1, 0, 1),
(15, 1, 4, 'C1-TW', 'twin',   2, 520.00,  1, 0, 1),
-- B-02-01
(16, 1, 5, 'D1-M',  'master', 2, 950.00,  2, 1, 1),
(17, 1, 5, 'D1-S1', 'single', 1, 660.00,  1, 0, 1),
(18, 1, 5, 'D1-S2', 'single', 1, 640.00,  1, 0, 1),
-- VS-3A
(19, 1, 6, 'E1-M',  'master', 2, 1100.00, 2, 1, 1),
(20, 1, 6, 'E1-S1', 'single', 1, 780.00,  1, 0, 1),
(21, 1, 6, 'E1-S2', 'single', 1, 760.00,  1, 0, 1),
(22, 1, 6, 'E1-S3', 'single', 1, 750.00,  1, 0, 1),
(23, 1, 6, 'E1-ST', 'studio', 2, 1300.00, 2, 1, 1),
-- VS-4B
(24, 1, 7, 'F1-M',  'master', 2, 1080.00, 2, 1, 1),
(25, 1, 7, 'F1-S1', 'single', 1, 770.00,  1, 0, 1),
(26, 1, 7, 'F1-S2', 'single', 1, 750.00,  1, 0, 1),
(27, 1, 7, 'F1-ST', 'studio', 2, 1280.00, 2, 1, 1);

-- ============================================================
-- BEDS (twin rooms)
-- ============================================================

INSERT INTO beds (company_id, room_id, bed_label, is_active) VALUES
(1, 11, 'A', 1), (1, 11, 'B', 1),   -- B1-S3 twin
(1, 15, 'A', 1), (1, 15, 'B', 1);   -- C1-TW twin

-- ============================================================
-- RESIDENTS
-- ============================================================
-- password_hash = Demo@1234

INSERT INTO residents
  (id, company_id, name, ic_number, nationality, email, phone,
   emergency_name, emergency_phone, employer, occupation,
   password_hash, is_active)
VALUES
(1,  1, 'Ali Hassan',          '950712015511', 'Malaysian', 'ali.hassan@mail.com',        '012-1111001', 'Hassan Ibrahim',     '012-9999001', 'Petronas',      'Engineer',           @pw, 1),
(2,  1, 'Priya Nair',          '980330565522', 'Malaysian', 'priya.nair@mail.com',        '016-2222002', 'Rajan Nair',         '016-8888002', 'TM Berhad',     'Software Developer', @pw, 1),
(3,  1, 'Wang Jian Ming',      '000515135533', 'Malaysian', 'wangjm@mail.com',            '011-3333003', 'Wang De Ming',       '011-7777003', 'Grab Malaysia', 'Product Manager',    @pw, 1),
(4,  1, 'Siti Aishah Malik',   '970225025544', 'Malaysian', 'siti.aishah@mail.com',       '013-4444004', 'Malik Sulaiman',     '013-6666004', 'Lazada',        'Marketing Exec',     @pw, 1),
(5,  1, 'Rajesh Kumar',        '880610085555', 'Malaysian', 'rajesh.kumar@mail.com',      '019-5555005', 'Kumar Raman',        '019-5555999', 'Astro',         'Accountant',         @pw, 1),
(6,  1, 'Nur Fatin Izzati',    '010830016666', 'Malaysian', 'nurfatin@mail.com',          '017-6666006', 'Izzati Rahman',      '017-4444006', 'Shopee',        'UX Designer',        @pw, 1),
(7,  1, 'Michael Tan',         '930425085577', 'Malaysian', 'michael.tan@mail.com',       '012-7777007', 'Tan Ah Kow',         '012-3333007', 'Google MY',     'Data Analyst',       @pw, 1),
(8,  1, 'Azrul Nizam',         '960918016688', 'Malaysian', 'azrul.nizam@mail.com',       '014-8888008', 'Nizam Abdul',        '014-2222008', 'Axiata',        'Network Engineer',   @pw, 1),
(9,  1, 'Kavitha Suresh',      '990202565599', 'Malaysian', 'kavitha.suresh@mail.com',    '018-9999009', 'Suresh Pillai',      '018-1111009', 'Shell Malaysia','Chemist',            @pw, 1),
(10, 1, 'Lee Mei Ling',        '010330075510', 'Malaysian', 'meiling.lee@mail.com',       '016-1010010', 'Lee Ah Meng',        '016-1010999', 'KPMG',          'Audit Associate',    @pw, 1),
(11, 1, 'Hafizuddin Roslan',   '911210016611', 'Malaysian', 'hafiz.roslan@mail.com',      '011-1111011', 'Roslan Yahya',       '011-9999011', 'Bank Islam',    'Credit Officer',     @pw, 1),
(12, 1, 'Sarah Abdullah',      '020514026622', 'Malaysian', 'sarah.abdullah@mail.com',    '017-2222012', 'Abdullah Yusof',     '017-8888012', 'Maxis',         'Sales Executive',    @pw, 1);

-- ============================================================
-- BOOKINGS
-- ============================================================

INSERT INTO bookings (id, company_id, room_id, resident_id, name, email, phone, move_in_date, duration_months, quoted_rent, status, notes) VALUES
(1, 1, 26, NULL, 'Jason Lim',         'jason.lim@mail.com',     '016-3456789', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 12, 750.00, 'pending', 'Interested in 12-month lease. Works in Sentul.'),
(2, 1, 27, NULL, 'Nurain Zainudin',   'nurain.zainudin@mail.com','013-9876543', DATE_ADD(CURDATE(), INTERVAL 7 DAY),  6,  1280.00,'approved','Prefers studio, flexible on move-in date.'),
(3, 1, 14, 4,   'Siti Aishah Malik',  'siti.aishah@mail.com',   '013-4444004', DATE_SUB(CURDATE(), INTERVAL 8 MONTH),12, 660.00, 'converted', 'Converted to tenancy #4.');

-- ============================================================
-- TENANCIES
-- ============================================================
-- Active, with varying end dates to show expiry scenarios

INSERT INTO tenancies
  (id, company_id, room_id, resident_id, booking_id, start_date, end_date,
   monthly_rent, deposit, billing_day, status)
VALUES
-- Active tenancies
(1,  1, 1,  1,  NULL, DATE_SUB(CURDATE(), INTERVAL 10 MONTH), DATE_ADD(CURDATE(), INTERVAL 2 MONTH),  980.00, 1960.00, 1, 'active'),  -- expiring soon
(2,  1, 2,  2,  NULL, DATE_SUB(CURDATE(), INTERVAL 6 MONTH),  DATE_ADD(CURDATE(), INTERVAL 6 MONTH),  680.00, 680.00,  1, 'active'),
(3,  1, 5,  3,  NULL, DATE_SUB(CURDATE(), INTERVAL 4 MONTH),  DATE_ADD(CURDATE(), INTERVAL 8 MONTH),  950.00, 950.00,  1, 'active'),
(4,  1, 14, 4,  3,    DATE_SUB(CURDATE(), INTERVAL 8 MONTH),  DATE_ADD(CURDATE(), INTERVAL 4 MONTH),  660.00, 660.00,  1, 'active'),
(5,  1, 9,  5,  NULL, DATE_SUB(CURDATE(), INTERVAL 12 MONTH), DATE_ADD(CURDATE(), INTERVAL 0 MONTH),  720.00, 720.00,  1, 'active'),  -- expiring this month
(6,  1, 19, 6,  NULL, DATE_SUB(CURDATE(), INTERVAL 3 MONTH),  DATE_ADD(CURDATE(), INTERVAL 9 MONTH),  1100.00,2200.00, 1, 'active'),
(7,  1, 20, 7,  NULL, DATE_SUB(CURDATE(), INTERVAL 7 MONTH),  DATE_ADD(CURDATE(), INTERVAL 5 MONTH),  780.00, 780.00,  1, 'active'),
(8,  1, 12, 8,  NULL, DATE_SUB(CURDATE(), INTERVAL 5 MONTH),  DATE_ADD(CURDATE(), INTERVAL 7 MONTH),  980.00, 980.00,  1, 'active'),
(9,  1, 23, 9,  NULL, DATE_SUB(CURDATE(), INTERVAL 2 MONTH),  DATE_ADD(CURDATE(), INTERVAL 10 MONTH), 1300.00,2600.00, 1, 'active'),
(10, 1, 24, 10, NULL, DATE_SUB(CURDATE(), INTERVAL 11 MONTH), DATE_ADD(CURDATE(), INTERVAL 1 MONTH),  1080.00,2160.00, 1, 'active'),  -- expiring soon
(11, 1, 25, 11, NULL, DATE_SUB(CURDATE(), INTERVAL 8 MONTH),  DATE_ADD(CURDATE(), INTERVAL 4 MONTH),  770.00, 770.00,  1, 'active'),
(12, 1, 13, 12, NULL, DATE_SUB(CURDATE(), INTERVAL 1 MONTH),  DATE_ADD(CURDATE(), INTERVAL 11 MONTH), 680.00, 680.00,  1, 'active'),
-- Expired
(13, 1, 7,  2,  NULL, DATE_SUB(CURDATE(), INTERVAL 18 MONTH), DATE_SUB(CURDATE(), INTERVAL 6 MONTH),  620.00, 620.00,  1, 'expired');

-- ============================================================
-- INVOICES  (28 seeded — invoice_seq already set to 28)
-- Covers: paid, partial, overdue, issued statuses
-- ============================================================

-- Tenant 1 (Ali Hassan, room A1-M, tenancy 1)
INSERT INTO invoices (id, company_id, invoice_no, tenancy_id, resident_id, period, due_date, subtotal, tax_amount, total_amount, amount_paid, balance, status, issued_at, paid_at) VALUES
(1,  1,'INV-00001',1,1, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 5 MONTH),'%Y-%m'), DATE_SUB(CURDATE(),INTERVAL 4 MONTH)+INTERVAL 5 DAY, 980.00,0,980.00, 980.00,0,'paid',  DATE_SUB(NOW(),INTERVAL 5 MONTH), DATE_SUB(NOW(),INTERVAL 4 MONTH)+INTERVAL 3 DAY),
(2,  1,'INV-00002',1,1, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 4 MONTH),'%Y-%m'), DATE_SUB(CURDATE(),INTERVAL 3 MONTH)+INTERVAL 5 DAY, 980.00,0,980.00, 980.00,0,'paid',  DATE_SUB(NOW(),INTERVAL 4 MONTH), DATE_SUB(NOW(),INTERVAL 3 MONTH)+INTERVAL 2 DAY),
(3,  1,'INV-00003',1,1, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 3 MONTH),'%Y-%m'), DATE_SUB(CURDATE(),INTERVAL 2 MONTH)+INTERVAL 5 DAY, 980.00,0,980.00, 980.00,0,'paid',  DATE_SUB(NOW(),INTERVAL 3 MONTH), DATE_SUB(NOW(),INTERVAL 2 MONTH)),
(4,  1,'INV-00004',1,1, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 2 MONTH),'%Y-%m'), DATE_SUB(CURDATE(),INTERVAL 1 MONTH)+INTERVAL 5 DAY, 980.00,0,980.00, 980.00,0,'paid',  DATE_SUB(NOW(),INTERVAL 2 MONTH), DATE_SUB(NOW(),INTERVAL 1 MONTH)),
(5,  1,'INV-00005',1,1, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), DATE_ADD(CURDATE(),INTERVAL 5 DAY),                  980.00,0,980.00, 0,      980.00,'issued',DATE_SUB(NOW(),INTERVAL 25 DAY), NULL),

-- Tenant 2 (Priya Nair, room A1-S1, tenancy 2)
(6,  1,'INV-00006',2,2, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 4 MONTH),'%Y-%m'), DATE_SUB(CURDATE(),INTERVAL 3 MONTH)+INTERVAL 5 DAY, 680.00,0,680.00, 680.00,0,'paid',  DATE_SUB(NOW(),INTERVAL 4 MONTH), DATE_SUB(NOW(),INTERVAL 3 MONTH)+INTERVAL 4 DAY),
(7,  1,'INV-00007',2,2, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 3 MONTH),'%Y-%m'), DATE_SUB(CURDATE(),INTERVAL 2 MONTH)+INTERVAL 5 DAY, 680.00,0,680.00, 680.00,0,'paid',  DATE_SUB(NOW(),INTERVAL 3 MONTH), DATE_SUB(NOW(),INTERVAL 2 MONTH)+INTERVAL 2 DAY),
(8,  1,'INV-00008',2,2, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 2 MONTH),'%Y-%m'), DATE_SUB(CURDATE(),INTERVAL 1 MONTH)+INTERVAL 5 DAY, 750.00,0,750.00, 500.00,250.00,'partial',DATE_SUB(NOW(),INTERVAL 2 MONTH),NULL),
(9,  1,'INV-00009',2,2, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), DATE_SUB(CURDATE(),INTERVAL 5 DAY),                  680.00,0,680.00, 0,      680.00,'overdue',DATE_SUB(NOW(),INTERVAL 28 DAY),NULL),

-- Tenant 3 (Wang Jian Ming, tenancy 3)
(10, 1,'INV-00010',3,3, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 3 MONTH),'%Y-%m'), DATE_SUB(CURDATE(),INTERVAL 2 MONTH)+INTERVAL 5 DAY, 950.00,0,950.00, 950.00,0,'paid', DATE_SUB(NOW(),INTERVAL 3 MONTH), DATE_SUB(NOW(),INTERVAL 2 MONTH)),
(11, 1,'INV-00011',3,3, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 2 MONTH),'%Y-%m'), DATE_SUB(CURDATE(),INTERVAL 1 MONTH)+INTERVAL 5 DAY, 950.00,0,950.00, 950.00,0,'paid', DATE_SUB(NOW(),INTERVAL 2 MONTH), DATE_SUB(NOW(),INTERVAL 1 MONTH)+INTERVAL 5 DAY),
(12, 1,'INV-00012',3,3, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), DATE_ADD(CURDATE(),INTERVAL 3 DAY),                  950.00,0,950.00, 0,      950.00,'issued',DATE_SUB(NOW(),INTERVAL 25 DAY),NULL),

-- Tenant 4 (Siti Aishah, tenancy 4)
(13, 1,'INV-00013',4,4, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 2 MONTH),'%Y-%m'), DATE_SUB(CURDATE(),INTERVAL 1 MONTH)+INTERVAL 5 DAY, 660.00,0,660.00, 660.00,0,'paid', DATE_SUB(NOW(),INTERVAL 2 MONTH), DATE_SUB(NOW(),INTERVAL 1 MONTH)),
(14, 1,'INV-00014',4,4, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), DATE_SUB(CURDATE(),INTERVAL 8 DAY),                  660.00,0,660.00, 0,      660.00,'overdue',DATE_SUB(NOW(),INTERVAL 28 DAY),NULL),

-- Tenant 5 (Rajesh Kumar, tenancy 5) — overdue (expiring this month)
(15, 1,'INV-00015',5,5, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 2 MONTH),'%Y-%m'), DATE_SUB(CURDATE(),INTERVAL 1 MONTH)+INTERVAL 5 DAY, 720.00,0,720.00, 720.00,0,'paid', DATE_SUB(NOW(),INTERVAL 2 MONTH), DATE_SUB(NOW(),INTERVAL 1 MONTH)+INTERVAL 7 DAY),
(16, 1,'INV-00016',5,5, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), DATE_SUB(CURDATE(),INTERVAL 12 DAY),                 720.00,0,720.00, 0,      720.00,'overdue',DATE_SUB(NOW(),INTERVAL 28 DAY),NULL),

-- Tenants 6–12 (current month invoices)
(17, 1,'INV-00017',6,6,  DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), DATE_ADD(CURDATE(),INTERVAL 2 DAY),  1100.00,0,1100.00,1100.00,0,'paid', DATE_SUB(NOW(),INTERVAL 28 DAY), DATE_SUB(NOW(),INTERVAL 20 DAY)),
(18, 1,'INV-00018',7,7,  DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), DATE_ADD(CURDATE(),INTERVAL 4 DAY),  780.00, 0,780.00, 780.00, 0,'paid', DATE_SUB(NOW(),INTERVAL 28 DAY), DATE_SUB(NOW(),INTERVAL 22 DAY)),
(19, 1,'INV-00019',8,8,  DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), DATE_ADD(CURDATE(),INTERVAL 6 DAY),  980.00, 0,980.00, 0,      980.00,'issued', DATE_SUB(NOW(),INTERVAL 26 DAY), NULL),
(20, 1,'INV-00020',9,9,  DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), DATE_ADD(CURDATE(),INTERVAL 8 DAY),  1300.00,0,1300.00,1300.00,0,'paid', DATE_SUB(NOW(),INTERVAL 28 DAY), DATE_SUB(NOW(),INTERVAL 18 DAY)),
(21, 1,'INV-00021',10,10,DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), DATE_ADD(CURDATE(),INTERVAL 1 DAY),  1080.00,0,1080.00,0,      1080.00,'issued',DATE_SUB(NOW(),INTERVAL 26 DAY),NULL),
(22, 1,'INV-00022',11,11,DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), DATE_SUB(CURDATE(),INTERVAL 3 DAY),  770.00, 0,770.00, 0,      770.00,'overdue',DATE_SUB(NOW(),INTERVAL 28 DAY),NULL),
(23, 1,'INV-00023',12,12,DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), DATE_ADD(CURDATE(),INTERVAL 10 DAY), 680.00, 0,680.00, 680.00, 0,'paid', DATE_SUB(NOW(),INTERVAL 26 DAY), DATE_SUB(NOW(),INTERVAL 15 DAY)),

-- Utility invoices (electric charges bundled)
(24, 1,'INV-00024',1,1, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), DATE_ADD(CURDATE(),INTERVAL 5 DAY),  126.00, 0,126.00, 0, 126.00,'issued',DATE_SUB(NOW(),INTERVAL 25 DAY),NULL),
(25, 1,'INV-00025',6,6, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), DATE_ADD(CURDATE(),INTERVAL 5 DAY),  98.40,  0,98.40,  0, 98.40,'issued', DATE_SUB(NOW(),INTERVAL 25 DAY),NULL),

-- Draft invoices (current month, not yet issued)
(26, 1,'INV-00026',3,3, DATE_FORMAT(CURDATE(),'%Y-%m'), DATE_ADD(LAST_DAY(CURDATE()-INTERVAL 1 MONTH)+INTERVAL 5 DAY, INTERVAL 1 MONTH), 950.00,0,950.00, 0, 950.00,'draft', NULL, NULL),
(27, 1,'INV-00027',5,5, DATE_FORMAT(CURDATE(),'%Y-%m'), DATE_ADD(LAST_DAY(CURDATE()-INTERVAL 1 MONTH)+INTERVAL 5 DAY, INTERVAL 1 MONTH), 720.00,0,720.00, 0, 720.00,'draft', NULL, NULL),
(28, 1,'INV-00028',7,7, DATE_FORMAT(CURDATE(),'%Y-%m'), DATE_ADD(LAST_DAY(CURDATE()-INTERVAL 1 MONTH)+INTERVAL 5 DAY, INTERVAL 1 MONTH), 780.00,0,780.00, 0, 780.00,'draft', NULL, NULL);

-- ============================================================
-- INVOICE ITEMS
-- ============================================================

INSERT INTO invoice_items (company_id, invoice_id, description, quantity, unit_price, amount, item_type) VALUES
-- Rent items (main)
(1,1,'Monthly Rent - A1-M (Master Room)',1,980.00,980.00,'rent'),
(1,2,'Monthly Rent - A1-M (Master Room)',1,980.00,980.00,'rent'),
(1,3,'Monthly Rent - A1-M (Master Room)',1,980.00,980.00,'rent'),
(1,4,'Monthly Rent - A1-M (Master Room)',1,980.00,980.00,'rent'),
(1,5,'Monthly Rent - A1-M (Master Room)',1,980.00,980.00,'rent'),
(1,6,'Monthly Rent - A1-S1 (Single Room)',1,680.00,680.00,'rent'),
(1,7,'Monthly Rent - A1-S1 (Single Room)',1,680.00,680.00,'rent'),
(1,8,'Monthly Rent - A1-S1 (Single Room)',1,680.00,680.00,'rent'),
(1,8,'Carpark Bay B12',1,70.00,70.00,'carpark'),
(1,9,'Monthly Rent - A1-S1 (Single Room)',1,680.00,680.00,'rent'),
(1,10,'Monthly Rent - A2-M (Master Room)',1,950.00,950.00,'rent'),
(1,11,'Monthly Rent - A2-M (Master Room)',1,950.00,950.00,'rent'),
(1,12,'Monthly Rent - A2-M (Master Room)',1,950.00,950.00,'rent'),
(1,13,'Monthly Rent - C1-S2 (Single Room)',1,660.00,660.00,'rent'),
(1,14,'Monthly Rent - C1-S2 (Single Room)',1,660.00,660.00,'rent'),
(1,15,'Monthly Rent - B1-S1 (Single Room)',1,720.00,720.00,'rent'),
(1,16,'Monthly Rent - B1-S1 (Single Room)',1,720.00,720.00,'rent'),
(1,17,'Monthly Rent - E1-M (Master Room)',1,1100.00,1100.00,'rent'),
(1,18,'Monthly Rent - E1-S1 (Single Room)',1,780.00,780.00,'rent'),
(1,19,'Monthly Rent - C1-M (Master Room)',1,980.00,980.00,'rent'),
(1,20,'Monthly Rent - E1-ST (Studio)',1,1300.00,1300.00,'rent'),
(1,21,'Monthly Rent - F1-M (Master Room)',1,1080.00,1080.00,'rent'),
(1,22,'Monthly Rent - F1-S1 (Single Room)',1,770.00,770.00,'rent'),
(1,23,'Monthly Rent - C1-S1 (Single Room)',1,680.00,680.00,'rent'),
-- Utility invoices
(1,24,'Electricity Usage - A1-M (84 kWh)',84,1.50,126.00,'utility'),
(1,25,'Electricity Usage - E1-M (82.5 kWh x RM0.38 x 3)',82.5,1.192,98.40,'utility'),
-- Draft rent items
(1,26,'Monthly Rent - A2-M (Master Room)',1,950.00,950.00,'rent'),
(1,27,'Monthly Rent - B1-S1 (Single Room)',1,720.00,720.00,'rent'),
(1,28,'Monthly Rent - E1-S1 (Single Room)',1,780.00,780.00,'rent');

-- ============================================================
-- PAYMENTS
-- ============================================================

INSERT INTO payments (company_id, invoice_id, resident_id, amount, payment_method, gateway_ref, payment_date, notes, recorded_by) VALUES
-- Inv 1-4 (Ali, paid)
(1,1,1,980.00,'duitnow','DNW2502001',DATE_SUB(CURDATE(),INTERVAL 4 MONTH)+INTERVAL 3 DAY,'',1),
(1,2,1,980.00,'fpx','FPX2503001',DATE_SUB(CURDATE(),INTERVAL 3 MONTH)+INTERVAL 2 DAY,'',1),
(1,3,1,980.00,'bank_transfer','TT2504001',DATE_SUB(CURDATE(),INTERVAL 2 MONTH),'',1),
(1,4,1,980.00,'duitnow','DNW2505001',DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'',1),
-- Inv 6-7 (Priya, paid)
(1,6,2,680.00,'fpx','FPX2502002',DATE_SUB(CURDATE(),INTERVAL 3 MONTH)+INTERVAL 4 DAY,'',1),
(1,7,2,680.00,'fpx','FPX2503002',DATE_SUB(CURDATE(),INTERVAL 2 MONTH)+INTERVAL 2 DAY,'',1),
-- Inv 8 (Priya, partial)
(1,8,2,500.00,'cash',NULL,DATE_SUB(CURDATE(),INTERVAL 1 MONTH)+INTERVAL 10 DAY,'Partial payment in cash',2),
-- Inv 10-11 (Wang, paid)
(1,10,3,950.00,'duitnow','DNW2503003',DATE_SUB(CURDATE(),INTERVAL 2 MONTH),'',1),
(1,11,3,950.00,'duitnow','DNW2504003',DATE_SUB(CURDATE(),INTERVAL 1 MONTH)+INTERVAL 5 DAY,'',1),
-- Inv 13 (Siti, paid)
(1,13,4,660.00,'fpx','FPX2504004',DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'',1),
-- Inv 15 (Rajesh, paid)
(1,15,5,720.00,'bank_transfer','TT2504005',DATE_SUB(CURDATE(),INTERVAL 1 MONTH)+INTERVAL 7 DAY,'',1),
-- Inv 17 (Nur Fatin, paid)
(1,17,6,1100.00,'duitnow','DNW2505006',DATE_SUB(CURDATE(),INTERVAL 20 DAY),'',3),
-- Inv 18 (Michael, paid)
(1,18,7,780.00,'fpx','FPX2505007',DATE_SUB(CURDATE(),INTERVAL 22 DAY),'',3),
-- Inv 20 (Kavitha, paid)
(1,20,9,1300.00,'duitnow','DNW2505009',DATE_SUB(CURDATE(),INTERVAL 18 DAY),'',3),
-- Inv 23 (Sarah, paid)
(1,23,12,680.00,'bank_transfer','TT2505012',DATE_SUB(CURDATE(),INTERVAL 15 DAY),'',3);

-- ============================================================
-- UTILITY RATES
-- ============================================================

INSERT INTO utility_rates (company_id, utility_type, rate_per_unit, unit_label, effective_from) VALUES
(1, 'electric', 0.5710, 'kWh',  '2024-01-01'),
(1, 'water',    1.1200, 'm3',   '2024-01-01'),
(1, 'gas',      2.4000, 'unit', '2024-01-01');

-- ============================================================
-- UTILITY READINGS (last 2 months)
-- ============================================================

INSERT INTO utility_readings (company_id, room_id, utility_type, period, reading_open, reading_close, read_date, source) VALUES
-- Last month — electric
(1,1, 'electric', DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), 1240.000,1324.000, DATE_SUB(CURDATE(),INTERVAL 28 DAY),'manual'),
(1,2, 'electric', DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), 880.000, 946.500,  DATE_SUB(CURDATE(),INTERVAL 28 DAY),'manual'),
(1,5, 'electric', DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), 2100.000,2182.000, DATE_SUB(CURDATE(),INTERVAL 28 DAY),'manual'),
(1,8, 'electric', DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), 755.000, 833.500,  DATE_SUB(CURDATE(),INTERVAL 28 DAY),'manual'),
(1,19,'electric', DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), 3100.000,3197.200, DATE_SUB(CURDATE(),INTERVAL 28 DAY),'manual'),
-- Last month — water
(1,1, 'water',    DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), 312.000, 323.500,  DATE_SUB(CURDATE(),INTERVAL 28 DAY),'manual'),
(1,5, 'water',    DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), 198.000, 210.800,  DATE_SUB(CURDATE(),INTERVAL 28 DAY),'manual'),
-- 2 months ago — electric
(1,1, 'electric', DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 2 MONTH),'%Y-%m'), 1152.000,1240.000, DATE_SUB(CURDATE(),INTERVAL 58 DAY),'manual'),
(1,2, 'electric', DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 2 MONTH),'%Y-%m'), 812.000, 880.000,  DATE_SUB(CURDATE(),INTERVAL 58 DAY),'manual');

-- ============================================================
-- OWNER PAYOUTS (last 4 months)
-- ============================================================

INSERT INTO owner_payouts (company_id, owner_id, unit_id, period, gross_rent, deductions, net_payout, status, paid_date, bank_ref) VALUES
-- Datuk Razif (owner of units 1, 2, 7)
(1,1,1, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 4 MONTH),'%Y-%m'), 3200.00,320.00,2880.00,'paid',DATE_SUB(CURDATE(),INTERVAL 88 DAY),'TT-RAZIF-0422'),
(1,1,2, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 4 MONTH),'%Y-%m'), 2400.00,240.00,2160.00,'paid',DATE_SUB(CURDATE(),INTERVAL 88 DAY),'TT-RAZIF-0422'),
(1,1,1, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 3 MONTH),'%Y-%m'), 3200.00,320.00,2880.00,'paid',DATE_SUB(CURDATE(),INTERVAL 58 DAY),'TT-RAZIF-0522'),
(1,1,2, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 3 MONTH),'%Y-%m'), 2400.00,240.00,2160.00,'paid',DATE_SUB(CURDATE(),INTERVAL 58 DAY),'TT-RAZIF-0522'),
(1,1,1, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 2 MONTH),'%Y-%m'), 3200.00,320.00,2880.00,'paid',DATE_SUB(CURDATE(),INTERVAL 28 DAY),'TT-RAZIF-0622'),
(1,1,2, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 2 MONTH),'%Y-%m'), 2400.00,240.00,2160.00,'paid',DATE_SUB(CURDATE(),INTERVAL 28 DAY),'TT-RAZIF-0622'),
(1,1,1, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), 3200.00,320.00,2880.00,'approved',NULL,NULL),
(1,1,2, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), 2400.00,240.00,2160.00,'approved',NULL,NULL),
(1,1,7, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), 3600.00,360.00,3240.00,'draft',NULL,NULL),
-- Puan Nora (units 3, 4)
(1,2,3, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 3 MONTH),'%Y-%m'), 3400.00,340.00,3060.00,'paid',DATE_SUB(CURDATE(),INTERVAL 58 DAY),'TT-NORA-0522'),
(1,2,4, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 3 MONTH),'%Y-%m'), 3200.00,320.00,2880.00,'paid',DATE_SUB(CURDATE(),INTERVAL 58 DAY),'TT-NORA-0522'),
(1,2,3, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 2 MONTH),'%Y-%m'), 3400.00,340.00,3060.00,'paid',DATE_SUB(CURDATE(),INTERVAL 28 DAY),'TT-NORA-0622'),
(1,2,4, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 2 MONTH),'%Y-%m'), 3200.00,320.00,2880.00,'paid',DATE_SUB(CURDATE(),INTERVAL 28 DAY),'TT-NORA-0622'),
(1,2,3, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), 3400.00,340.00,3060.00,'approved',NULL,NULL),
(1,2,4, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), 3200.00,320.00,2880.00,'draft',NULL,NULL),
-- Lee Chong Fatt (units 5, 6)
(1,3,5, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 2 MONTH),'%Y-%m'), 2600.00,260.00,2340.00,'paid',DATE_SUB(CURDATE(),INTERVAL 28 DAY),'TT-LEE-0622'),
(1,3,6, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 2 MONTH),'%Y-%m'), 4000.00,400.00,3600.00,'paid',DATE_SUB(CURDATE(),INTERVAL 28 DAY),'TT-LEE-0622'),
(1,3,5, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), 2600.00,260.00,2340.00,'draft',NULL,NULL),
(1,3,6, DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m'), 4000.00,400.00,3600.00,'draft',NULL,NULL);

-- ============================================================
-- MAINTENANCE TICKETS
-- ============================================================

INSERT INTO maintenance_tickets
  (company_id, room_id, resident_id, assigned_to, title, description, category, priority, status, resolved_at, notes)
VALUES
(1, 1,  1, 4, 'Aircon not cooling properly',      'Unit A1-M master room aircon is only blowing warm air since last night.', 'aircon',     'urgent', 'in_progress', NULL, 'Technician scheduled.'),
(1, 2,  2, 4, 'Bathroom pipe leaking',             'Water dripping from the pipe under the bathroom sink.', 'plumbing', 'high', 'open', NULL, NULL),
(1, 9,  5, 4, 'Ceiling light flickering',          'The main ceiling light in B1-S1 keeps flickering. Happens mostly at night.', 'electrical', 'medium', 'open', NULL, NULL),
(1, 19, 6, 4, 'WiFi router not working',           'No internet connection in the room. Router shows red light.', 'other', 'high', 'open', NULL, 'ISP ticket raised.'),
(1, 12, 8, 4, 'Wardrobe door hinge broken',        'The left wardrobe door hinge is broken — door keeps falling off.', 'furniture', 'low', 'open', NULL, NULL),
(1, 5,  3, 4, 'Cockroach infestation in kitchen',  'Spotted multiple cockroaches near the kitchen sink area.', 'pest', 'high', 'in_progress', NULL, 'Pest control booked for Saturday.'),
(1, 14, 4, 4, 'Smart lock battery low warning',    'Door lock app showing battery at 5%. Need replacement.', 'lock', 'medium', 'open', NULL, NULL),
(1, 23, 9, 4, 'Hot water not working',             'Studio hot water heater has been off since 2 days ago.', 'electrical', 'urgent', 'in_progress', NULL, 'Checked — faulty heater element. Part ordered.'),
(1, 25, 11,4, 'Window screen torn',                'Window screen in bedroom is torn. Mosquitoes coming in.', 'other', 'low', 'resolved', DATE_SUB(NOW(),INTERVAL 5 DAY), 'Replaced with new screen.'),
(1, 13, 12,4, 'Drain clogged in bathroom',         'Shower drain is very slow. Water pools for 10+ minutes after shower.', 'plumbing', 'medium', 'resolved', DATE_SUB(NOW(),INTERVAL 10 DAY), 'Cleared blockage.'),
(1, NULL,NULL,4,'Common area corridor light out',  'Level 2 corridor near staircase — two bulbs blown.', 'electrical', 'low', 'open', NULL, NULL),
(1, NULL,NULL,4,'Main entrance gate slow closing', 'Auto-gate takes very long to close. Security concern.', 'other', 'high', 'open', NULL, NULL);

-- ============================================================
-- SMART LOCKS
-- ============================================================

INSERT INTO smart_locks (company_id, room_id, device_id, vendor, label, is_active) VALUES
(1, 1,  'TTL-A1M-0001',  'TTLock', 'Block A Unit A-01-01 Master',  1),
(1, 2,  'TTL-A1S1-0002', 'TTLock', 'Block A Unit A-01-01 Single 1',1),
(1, 5,  'TTL-A2M-0003',  'TTLock', 'Block A Unit A-01-02 Master',  1),
(1, 19, 'IGL-E1M-0004',  'Igloohome','Vista E1 Master',            1),
(1, 23, 'IGL-E1ST-0005', 'Igloohome','Vista E1 Studio',            1);

-- ============================================================
-- LOCK ACCESS LOGS
-- ============================================================

INSERT INTO lock_access_logs (company_id, lock_id, triggered_by, resident_id, method, result, note, created_at) VALUES
(1,1,NULL,1,'pin','success',NULL, DATE_SUB(NOW(),INTERVAL 2 HOUR)),
(1,1,NULL,1,'pin','success',NULL, DATE_SUB(NOW(),INTERVAL 14 HOUR)),
(1,2,NULL,2,'pin','success',NULL, DATE_SUB(NOW(),INTERVAL 3 HOUR)),
(1,2,NULL,2,'pin','failed','Wrong PIN entered', DATE_SUB(NOW(),INTERVAL 3 HOUR)+INTERVAL 1 MINUTE),
(1,2,NULL,2,'pin','success',NULL, DATE_SUB(NOW(),INTERVAL 3 HOUR)+INTERVAL 2 MINUTE),
(1,1,1,NULL,'remote','success','Remote unlock via dashboard - staff Ahmad Farid', DATE_SUB(NOW(),INTERVAL 1 DAY)),
(1,4,NULL,6,'pin','success',NULL, DATE_SUB(NOW(),INTERVAL 5 HOUR)),
(1,5,NULL,9,'app','success','Unlocked via mobile app', DATE_SUB(NOW(),INTERVAL 1 HOUR));

-- ============================================================
-- SUPPORT MESSAGES
-- ============================================================

INSERT INTO support_messages (company_id, resident_id, parent_id, sender_type, sender_id, body, status, is_read, is_escalated, created_at) VALUES
-- Thread 1: Ali Hassan asking about invoice
(1, 1, NULL, 'resident', NULL, 'Hi, I would like to clarify — I received invoice INV-00005 for this month but I thought I had already paid. Can someone check?', 'open', 1, 0, DATE_SUB(NOW(),INTERVAL 2 DAY)),
(1, 1, 1,    'staff',    2,    'Hi Ali, let me check your payment records. I can see your last payment was for INV-00004 covering last month. INV-00005 is for the current month, due on the 5th. Your account is all clear up to last month!', 'open', 1, 0, DATE_SUB(NOW(),INTERVAL 2 DAY)+INTERVAL 2 HOUR),
(1, 1, 1,    'resident', NULL, 'Oh I see, thank you for clarifying! I will make the payment before the 5th.', 'open', 1, 0, DATE_SUB(NOW(),INTERVAL 1 DAY)),
-- Thread 2: Priya Nair asking about partial invoice
(1, 2, NULL, 'resident', NULL, 'Hello, I notice my invoice INV-00008 shows partial payment. I did pay RM500 last month — is this correct?', 'open', 1, 0, DATE_SUB(NOW(),INTERVAL 5 DAY)),
(1, 2, 4,    'staff',    2,    'Hi Priya, yes that is correct. We received RM500 on the payment date. The remaining balance of RM250 is still outstanding. Please settle it at your earliest convenience. Thank you!', 'open', 1, 0, DATE_SUB(NOW(),INTERVAL 4 DAY)),
(1, 2, 4,    'resident', NULL, 'Understood. I will pay the balance this week. Sorry for the delay.', 'open', 1, 0, DATE_SUB(NOW(),INTERVAL 3 DAY)),
-- Thread 3: Nur Fatin — maintenance follow up (unread, recent)
(1, 6, NULL, 'resident', NULL, 'My WiFi has been down for 3 days now. The ticket says in progress but I have not heard anything. When will it be fixed? I work from home and this is really affecting me.', 'open', 0, 1, DATE_SUB(NOW(),INTERVAL 4 HOUR)),
-- Thread 4: Kavitha — studio inquiry (closed)
(1, 9, NULL, 'resident', NULL, 'Hi team, just wanted to ask — is there any possibility to extend my studio tenancy for another 6 months after my current contract ends?', 'closed', 1, 0, DATE_SUB(NOW(),INTERVAL 30 DAY)),
(1, 9, 8,    'staff',    2,    'Hi Kavitha, great to hear you are happy here! Yes, we can definitely arrange a renewal. Our manager will reach out 60 days before your end date to discuss terms. Stay tuned!', 'closed', 1, 0, DATE_SUB(NOW(),INTERVAL 29 DAY));

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DEMO SUMMARY
-- ============================================================
-- Company    : SkyRise Living Sdn. Bhd. (company_id = 1)
-- Buildings  : 3 (Block A, Block B, Vista Sentul)
-- Units      : 7 | Rooms: 27 | Beds: 4 (in 2 twin rooms)
-- Owners     : 3 (Datuk Razif, Puan Nora, Lee Chong Fatt)
-- Residents  : 12 active
-- Tenancies  : 12 active (3 expiring within 60 days), 1 expired
-- Invoices   : 28 (paid/partial/overdue/issued/draft mix)
-- Payments   : 15 recorded
-- Payouts    : 19 records (paid/approved/draft across 3 owners)
-- Maintenance: 12 tickets (urgent/high/medium/low mix)
-- Smart Locks: 5 locks, 8 access log entries
-- Support    : 4 threads (3 open, 1 closed)
-- ============================================================
