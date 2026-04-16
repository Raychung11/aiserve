-- =============================================================================
-- seed.sql for KASIH GOLD EASY platform
-- Sample data for development and testing
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- =============================================================================
-- USERS
-- Passwords: all use bcrypt hash of 'Admin@1234' for simplicity in seed
-- In production, use proper per-user hashes
-- bcrypt hash for 'Admin@1234' (cost 10)
-- =============================================================================
INSERT INTO users (id, full_name, email, phone, password_hash, role, status, referral_code, referred_by_user_id, email_verified_at, created_at) VALUES
(1, 'Super Administrator', 'admin@kasihgold.my', '0123456789', '$2y$10$TKh8H1.PfYi1hdTONSiAcuGpvGDrXxzYfR5Z5x5QxF8K9Xf5bHkFm', 'super_admin', 'active', 'ADMIN001', NULL, NOW(), NOW()),
(2, 'Ahmad Farhan Bin Ismail', 'user@kasihgold.my', '0112345678', '$2y$10$TKh8H1.PfYi1hdTONSiAcuGpvGDrXxzYfR5Z5x5QxF8K9Xf5bHkFm', 'user', 'active', 'USR00001', NULL, NOW(), NOW()),
(3, 'Lim Chee Keong', 'merchant@kasihgold.my', '0198765432', '$2y$10$TKh8H1.PfYi1hdTONSiAcuGpvGDrXxzYfR5Z5x5QxF8K9Xf5bHkFm', 'merchant', 'active', 'MRC00001', NULL, NOW(), NOW()),
(4, 'Siti Aminah Binti Rahman', 'siti@kasihgold.my', '0167891234', '$2y$10$TKh8H1.PfYi1hdTONSiAcuGpvGDrXxzYfR5Z5x5QxF8K9Xf5bHkFm', 'user', 'active', 'USR00002', 2, NOW(), NOW());

-- =============================================================================
-- MERCHANTS
-- =============================================================================
INSERT INTO merchants (id, user_id, company_name, contact_name, registration_no, business_address, status, payout_bank, payout_account_no, payout_account_name, approved_by, approved_at, created_at) VALUES
(1, 3, 'Perabot Jaya Sdn Bhd', 'Lim Chee Keong', '202201012345', 'No. 12, Jalan Utama, Taman Maju, 47500 Subang Jaya, Selangor', 'active', 'Maybank', '1234567890', 'Perabot Jaya Sdn Bhd', 1, NOW(), NOW());

-- =============================================================================
-- GOLD PRICES (price_per_g in RM)
-- =============================================================================
INSERT INTO gold_prices (id, price_per_g, effective_at, status, notes, created_by, created_at) VALUES
(1, 380.0000, '2026-04-10 09:00:00', 'superseded', 'Harga awal minggu', 1, '2026-04-10 09:00:00'),
(2, 382.5000, '2026-04-11 09:00:00', 'superseded', NULL, 1, '2026-04-11 09:00:00'),
(3, 385.0000, '2026-04-12 09:00:00', 'superseded', NULL, 1, '2026-04-12 09:00:00'),
(4, 388.0000, '2026-04-15 09:00:00', 'superseded', NULL, 1, '2026-04-15 09:00:00'),
(5, 390.0000, '2026-04-16 09:00:00', 'active',     'Harga terkini dikemaskini 16 April 2026', 1, '2026-04-16 09:00:00');

-- =============================================================================
-- WALLETS
-- =============================================================================
INSERT INTO wallets (id, user_id, points_balance, grams_balance, created_at) VALUES
(1, 1, 0.0000, 0.000000, NOW()),
(2, 2, 15000.0000, 150.000000, NOW()),
(3, 3, 8500.0000, 85.000000, NOW()),
(4, 4, 3200.0000, 32.000000, NOW());

-- =============================================================================
-- WALLET LEDGER (sample entries for user 2 and 4)
-- =============================================================================
INSERT INTO wallet_ledger (wallet_id, user_id, direction, points, grams, rm_reference_value, price_per_g_snapshot, source_type, source_id, description, status, created_at) VALUES
(2, 2, 'credit', 10000.0000, 100.000000, 3900.00, 390.0000, 'buy_credit', 1, 'Pembelian Emas — RM 390.00', 'completed', '2026-04-12 10:00:00'),
(2, 2, 'credit',  5000.0000,  50.000000, 1950.00, 390.0000, 'buy_credit', 2, 'Pembelian Emas — RM 195.00', 'completed', '2026-04-14 14:30:00'),
(3, 3, 'credit',  8500.0000,  85.000000, 3315.00, 390.0000, 'buy_credit', 3, 'Pembelian Emas — RM 331.50', 'completed', '2026-04-10 11:00:00'),
(4, 4, 'credit',  3200.0000,  32.000000, 1248.00, 390.0000, 'buy_credit', 4, 'Pembelian Emas — RM 124.80', 'completed', '2026-04-15 09:30:00');

-- =============================================================================
-- GOLD PURCHASES (sample)
-- =============================================================================
INSERT INTO gold_purchases (id, user_id, rm_amount, price_per_g_snapshot, points_credited, grams_credited, payment_status, purchase_status, payment_reference, payment_gateway, created_at, paid_at) VALUES
(1, 2, 390.00, 390.0000, 10000.0000, 100.000000, 'paid', 'credited', 'FPX-202604121001', 'manual', '2026-04-12 10:00:00', '2026-04-12 10:05:00'),
(2, 2, 195.00, 390.0000,  5000.0000,  50.000000, 'paid', 'credited', 'FPX-202604141430', 'manual', '2026-04-14 14:30:00', '2026-04-14 14:35:00'),
(3, 3, 331.50, 390.0000,  8500.0000,  85.000000, 'paid', 'credited', 'FPX-202604101105', 'manual', '2026-04-10 11:00:00', '2026-04-10 11:05:00'),
(4, 4, 124.80, 390.0000,  3200.0000,  32.000000, 'paid', 'credited', 'FPX-202604150930', 'manual', '2026-04-15 09:30:00', '2026-04-15 09:35:00');

-- =============================================================================
-- REFERRALS
-- =============================================================================
INSERT INTO referrals (id, referrer_user_id, referred_user_id, level, created_at) VALUES
(1, 2, 4, 1, NOW());

-- =============================================================================
-- REFERRAL SETTINGS
-- =============================================================================
INSERT INTO referral_settings (id, level, rate_percent, is_active, updated_by) VALUES
(1, 1, 5.00, 1, 1),
(2, 2, 3.00, 1, 1),
(3, 3, 1.00, 1, 1);

-- =============================================================================
-- MARKETPLACE CATEGORIES
-- =============================================================================
INSERT INTO marketplace_categories (id, name, slug, description, icon, is_active, sort_order) VALUES
(1, 'Perabot & Hiasan Rumah',   'perabot-hiasan-rumah',   'Perabot berkualiti dan hiasan rumah moden', '🪑', 1, 1),
(2, 'Takaful & Perlindungan',   'takaful-perlindungan',   'Pakej perlindungan keluarga berdasarkan takaful', '🛡️', 1, 2),
(3, 'Pelan Pengebumian',        'pelan-pengebumian',      'Pelan simpanan dan perkhidmatan pengebumian', '🕌', 1, 3),
(4, 'Simpanan Koperasi',        'simpanan-koperasi',      'Pelan simpanan koperasi dan pelaburan komuniti', '🏛️', 1, 4),
(5, 'Gaya Hidup & Kesihatan',   'gaya-hidup-kesihatan',   'Produk kesihatan dan gaya hidup berkualiti', '💚', 1, 5),
(6, 'Penjagaan Warga Emas',     'penjagaan-warga-emas',   'Produk dan perkhidmatan untuk warga emas', '👴', 1, 6);

-- =============================================================================
-- MARKETPLACE PRODUCTS
-- =============================================================================
INSERT INTO marketplace_products (id, merchant_id, category_id, title, slug, description, points_price, rm_reference_value, stock_qty, status, is_featured, campaign_tag, created_at) VALUES
(1, 1, 1, 'Set Sofa Ruang Tamu Premium', 'set-sofa-ruang-tamu-premium',
 'Set sofa mewah 3+2+1 seater dengan bahan kulit berkualiti tinggi. Sesuai untuk rumah moden.', 38500.0000, 1501.50, 10, 'active', 1, NULL, NOW()),
(2, 1, 2, 'Pakej Takaful Keluarga Asas', 'pakej-takaful-keluarga-asas',
 'Perlindungan takaful komprehensif untuk seluruh keluarga. Termasuk perlindungan perubatan dan kemalangan.', 12800.0000, 499.20, NULL, 'active', 0, NULL, NOW()),
(3, 1, 3, 'Pelan Simpanan Pengebumian Amanah', 'pelan-simpanan-pengebumian-amanah',
 'Pelan simpanan pengebumian yang mesra Islam. Pastikan proses pengebumian berlangsung dengan sempurna tanpa bebanan keluarga.', 25600.0000, 998.40, NULL, 'active', 1, 'funeral_savings', NOW()),
(4, 1, 6, 'Kerusi Urut Warga Emas Pro', 'kerusi-urut-warga-emas-pro',
 'Kerusi urut ergonomik khusus untuk warga emas dengan fungsi panas, urutan penuh badan dan kawalan mudah.', 51200.0000, 1996.80, 5, 'draft', 0, NULL, NOW());

-- =============================================================================
-- CAMPAIGNS
-- =============================================================================
INSERT INTO campaigns (id, title, slug, description, type, target_points, target_rm_estimate, target_date, monthly_suggested_contribution, cta_text, is_active, created_by, created_at) VALUES
(1, 'Tabung Pengebumian Keluarga', 'tabung-pengebumian-keluarga',
 'Rangkang simpanan emas untuk membiayai kos pengebumian ahli keluarga. Bersedia lebih awal, elak kesulitan di masa hadapan. Selaras prinsip muamalat Islam.',
 'funeral_savings', 100000.0000, 3900.00, '2027-04-16', 2500.0000,
 'Mulakan Tabungan Pengebumian Hari Ini', 1, 1, NOW()),
(2, 'Simpanan Pendidikan Anak', 'simpanan-pendidikan-anak',
 'Bina dana pendidikan anak dengan simpanan emas. Nilai emas yang stabil membantu masa hadapan anak-anak anda.',
 'child_savings', 200000.0000, 7800.00, '2030-01-01', 5000.0000,
 'Bina Masa Depan Anak Hari Ini', 1, 1, NOW()),
(3, 'Tabung Koperasi Warga Emas', 'tabung-koperasi-warga-emas',
 'Program simpanan koperasi khusus untuk warga emas. Sertai komuniti penjimatan bersama dan nikmati manfaat bersama.',
 'koperasi', 500000.0000, 19500.00, NULL, 10000.0000,
 'Sertai Koperasi Warga Emas', 1, 1, NOW());

-- =============================================================================
-- SETTINGS
-- =============================================================================
INSERT INTO settings (setting_key, setting_value, description, is_public, updated_by) VALUES
('site_name',                'Kasih Gold Easy',                        'Nama platform', 1, 1),
('tagline',                  'Emas Mudah, Kaya Mudah.',                'Tagline utama', 1, 1),
('support_whatsapp',         '+601234567890',                          'Nombor WhatsApp sokongan', 1, 1),
('min_topup_rm',             '5.00',                                   'Minimum pembelian emas (RM)', 0, 1),
('max_topup_rm',             '50000.00',                               'Maksimum pembelian emas (RM)', 0, 1),
('referral_enabled',         '1',                                      'Aktifkan sistem rujukan', 0, 1),
('marketplace_enabled',      '1',                                      'Aktifkan pasaran maya', 0, 1),
('campaign_module_enabled',  '1',                                      'Aktifkan modul kempen', 0, 1),
('ai_assistant_enabled',     '1',                                      'Aktifkan pembantu AI', 0, 1),
('shariah_disclaimer',       'Platform ini dibangunkan berdasarkan prinsip muamalat yang bertanggungjawab. Sila rujuk penasihat kewangan Islam anda untuk kepastian lanjut.',
                                                                        'Penafian Syariah', 1, 1),
('terms_url',                '/terms',                                 'URL Terma & Syarat', 1, 1),
('privacy_url',              '/privacy',                               'URL Dasar Privasi', 1, 1),
('payout_min_points',        '1000',                                   'Minimum mata untuk bayaran', 0, 1),
('merchant_approval_required','1',                                     'Peniaga mesti diluluskan admin', 0, 1),
('ai_model',                 'claude-sonnet-4-6',                      'Model AI yang digunakan', 0, 1),
('ai_system_prompt',         'Anda adalah pembantu AI untuk platform Kasih Gold Easy. Bantu pengguna memahami simpanan emas, pengiraan mata, dan cara menggunakan platform. Sentiasa gunakan harga emas terkini yang ditetapkan oleh admin. Jangan sesekali mengesahkan transaksi yang belum diproses.', 'Prompt sistem AI', 0, 1),
('bank_name',                'Maybank',                                 'Nama bank penerima pembayaran', 0, 1),
('bank_account',             '1234-5678-9012',                          'No. akaun bank penerima', 0, 1),
('bank_account_name',        'Kasih Gold Easy Sdn Bhd',                 'Nama pemegang akaun bank', 0, 1);

-- =============================================================================
-- AUDIT LOGS (initial entries)
-- =============================================================================
INSERT INTO audit_logs (actor_user_id, actor_role, action_type, target_type, target_id, new_value_json, ip_address, user_agent, created_at) VALUES
(1, 'super_admin', 'gold_price_set', 'gold_prices', 5, '{"price_per_g":"390.0000"}', '127.0.0.1', 'Seed Data', NOW()),
(1, 'super_admin', 'merchant_approved', 'merchants', 1, '{"company":"Perabot Jaya Sdn Bhd"}', '127.0.0.1', 'Seed Data', NOW());

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- End of seed.sql
-- IMPORTANT: Default password for all seed accounts is 'Admin@1234'
-- Change all passwords after installation!
-- =============================================================================
