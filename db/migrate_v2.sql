-- AiServe ESG OS — Migration v2
-- Expands framework columns from ENUM to VARCHAR(50)
-- Required for installations running v1 (Bursa SEDG + GRI only)
-- before upgrading to v2 (all 10 frameworks)
--
-- Run once via phpMyAdmin or CLI:
--   mysql -u <user> -p <dbname> < migrate_v2.sql

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- companies.framework: add all new framework IDs
ALTER TABLE `companies`
  MODIFY COLUMN `framework` VARCHAR(50) NOT NULL DEFAULT 'BURSA_SEDG';

-- esg_data.framework: expand from ENUM('BURSA_SEDG','GRI') to VARCHAR
ALTER TABLE `esg_data`
  MODIFY COLUMN `framework` VARCHAR(50) NOT NULL DEFAULT 'BURSA_SEDG';

-- gap_analyses.framework: expand from ENUM('BURSA_SEDG','GRI','BOTH') to VARCHAR
ALTER TABLE `gap_analyses`
  MODIFY COLUMN `framework` VARCHAR(50) NOT NULL DEFAULT 'BURSA_SEDG';

-- reports.framework: expand from ENUM('BURSA_SEDG','GRI','BOTH') to VARCHAR
ALTER TABLE `reports`
  MODIFY COLUMN `framework` VARCHAR(50) NOT NULL DEFAULT 'BURSA_SEDG';

-- Extend indicator_id in esg_data if needed (ESRS IDs can be up to 20 chars)
ALTER TABLE `esg_data`
  MODIFY COLUMN `indicator_id` VARCHAR(50) NOT NULL;
