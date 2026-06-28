-- ============================================================
-- CoLive OS — Listings Demo Seed
-- Run this in phpMyAdmin (select u822252863_roomee first).
-- Safe to run multiple times — uses INSERT IGNORE.
-- Shows 15 available rooms on the public listings page.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Company (SkyRise Living)
INSERT IGNORE INTO companies
  (id, name, email, phone, address, brand_color, status, trial_ends_at, invoice_prefix, invoice_seq)
VALUES
  (1, 'SkyRise Living Sdn. Bhd.', 'admin@skyrise.my', '03-12345678',
   'Jalan Semarak, Kuala Lumpur', '#7c3aed', 'active',
   DATE_ADD(NOW(), INTERVAL 60 DAY), 'INV', 0);

-- Buildings
INSERT IGNORE INTO buildings (id, company_id, name, address, city, state, postcode) VALUES
(1, 1, 'Alam Damai Residences', 'Jalan Alam Damai 2, Taman Alam Damai', 'Cheras',       'Kuala Lumpur', '56000'),
(2, 1, 'Bukit Bintang Suites',  '27 Jalan Bukit Bintang',               'Bukit Bintang','Kuala Lumpur', '55100'),
(3, 1, 'Vista Subang Lestari',  'Persiaran Subang Permai',               'Subang Jaya',  'Selangor',     '47500');

-- Units
INSERT IGNORE INTO units (id, company_id, building_id, unit_no, floor, total_rooms, master_rent, payout_day, is_active) VALUES
(1, 1, 1, 'A-01-01', 1, 4, 3200.00, 5, 1),
(2, 1, 1, 'A-01-02', 1, 3, 2800.00, 5, 1),
(3, 1, 2, 'B-01-01', 1, 4, 3400.00, 5, 1),
(4, 1, 2, 'B-02-01', 1, 3, 2900.00, 5, 1),
(5, 1, 3, 'VS-3A',   3, 5, 4200.00, 5, 1),
(6, 1, 3, 'VS-4B',   4, 4, 3900.00, 5, 1);

-- Available rooms (no active tenancies — will appear on listings page)
INSERT IGNORE INTO rooms (id, company_id, unit_id, room_no, room_type, capacity, base_rent, deposit_months, has_attached_bath, is_active) VALUES
-- Alam Damai — Unit A-01-01
( 3, 1, 1, 'A1-S2', 'single', 1,  680.00, 1, 0, 1),
( 4, 1, 1, 'A1-S3', 'single', 1,  650.00, 0, 0, 1),
-- Alam Damai — Unit A-01-02
( 6, 1, 2, 'A2-S1', 'single', 1,  650.00, 1, 0, 1),
( 7, 1, 2, 'A2-S2', 'single', 1,  620.00, 0, 0, 1),
-- Bukit Bintang — Unit B-01-01
( 8, 1, 3, 'B1-M',  'master', 2, 1050.00, 2, 1, 1),
(10, 1, 3, 'B1-S2', 'single', 1,  720.00, 1, 0, 1),
(11, 1, 3, 'B1-S3', 'twin',   2,  550.00, 1, 0, 1),
-- Bukit Bintang — Unit B-02-01
(15, 1, 4, 'C1-TW', 'twin',   2,  520.00, 0, 0, 1),
(16, 1, 4, 'D1-M',  'master', 2,  950.00, 2, 1, 1),
(17, 1, 4, 'D1-S1', 'single', 1,  660.00, 1, 0, 1),
-- Vista Subang — Unit VS-3A
(18, 1, 5, 'E1-S1', 'single', 1,  640.00, 0, 0, 1),
(21, 1, 5, 'E1-S3', 'single', 1,  760.00, 1, 0, 1),
(22, 1, 5, 'E1-S4', 'single', 1,  750.00, 0, 0, 1),
-- Vista Subang — Unit VS-4B
(26, 1, 6, 'F1-S2', 'single', 1,  750.00, 1, 0, 1),
(27, 1, 6, 'F1-ST', 'studio', 2, 1280.00, 2, 1, 1);

SET FOREIGN_KEY_CHECKS = 1;
