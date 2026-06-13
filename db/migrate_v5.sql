-- Adcellent ESG OS — Migration v5
-- Adds platform_settings table for super admin configuration

CREATE TABLE IF NOT EXISTS `platform_settings` (
  `key`        VARCHAR(100) NOT NULL,
  `value`      TEXT         DEFAULT NULL,
  `grp`        VARCHAR(50)  NOT NULL DEFAULT 'general',
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `platform_settings` (`key`, `value`, `grp`) VALUES
  ('platform_name',      'Adcellent ESG OS', 'general'),
  ('default_framework',  'BURSA_SEDG',     'general'),
  ('reporting_year',     '2025',           'general'),
  ('maintenance_mode',   '0',              'general'),
  ('weight_environment', '40',             'scoring'),
  ('weight_social',      '35',             'scoring'),
  ('weight_governance',  '25',             'scoring'),
  ('score_excellent',    '80',             'thresholds'),
  ('score_good',         '60',             'thresholds'),
  ('score_moderate',     '40',             'thresholds'),
  ('score_poor',         '20',             'thresholds');
