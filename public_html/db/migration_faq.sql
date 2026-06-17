-- MM2H FAQ / Application Guide Migration
-- Run this after the main schema.sql

CREATE TABLE IF NOT EXISTS `mm2h_faqs` (
  `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category`           VARCHAR(80)  NOT NULL,
  `question`           VARCHAR(500) NOT NULL,
  `answer`             TEXT         NOT NULL,
  `processing_time`    VARCHAR(120) DEFAULT NULL,
  `required_documents` TEXT         DEFAULT NULL,
  `fees`               VARCHAR(200) DEFAULT NULL,
  `sort_order`         SMALLINT     NOT NULL DEFAULT 0,
  `is_active`          TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- SEED: IMI Procedural Data
-- ─────────────────────────────────────────────────────────────────────────────

-- Category 1: Visa Issuance & Extensions
INSERT INTO `mm2h_faqs` (`category`, `question`, `answer`, `processing_time`, `required_documents`, `fees`, `sort_order`) VALUES

('Visa Issuance & Extensions',
 'How do I obtain the initial Social Visit Pass (MM2H Principal)?',
 'After your MM2H conditional approval letter is issued by Tourism Malaysia, you must enter Malaysia and apply to the Immigration Department (IMI) for your Social Visit Pass. Submit the required documents in person at the IMI counter. The pass is issued for up to 10 years and is renewable.',
 '1 business day',
 'Conditional Approval Letter from Tourism Malaysia; Valid passport (min. 18 months validity); IMI application form; Passport-sized photographs; Fixed Deposit confirmation letter; Medical insurance policy; Health declaration form',
 'RM 90 per year of pass (standard immigration levy)',
 10),

('Visa Issuance & Extensions',
 'How do I obtain a Social Visit Pass for my spouse or children under MM2H?',
 'Dependants (spouse and unmarried children under 21) may apply for a Social Visit Pass as MM2H dependants after the principal holder receives their pass. Applications are submitted to IMI with proof of relationship.',
 '30 working days',
 'Principal holder''s MM2H pass copy; Valid passport of dependant; Birth certificate or marriage certificate (apostille/legalised); IMI application form; Photographs; Medical insurance for dependant',
 'RM 90 per year per dependant',
 20),

('Visa Issuance & Extensions',
 'Can I bring my parents to Malaysia under MM2H?',
 'Yes. Parents of the MM2H principal holder (or spouse) may apply for a Social Visit Pass. The pass duration is aligned to the principal''s pass validity. A financial guarantee from the principal holder is required.',
 '30 working days',
 'Principal holder''s MM2H pass copy; Parent''s valid passport; Relationship proof (birth certificates); Financial guarantee letter; IMI application form; Medical insurance for parent',
 'RM 90 per year',
 30),

('Visa Issuance & Extensions',
 'How do I renew my MM2H Social Visit Pass (5-year renewal)?',
 'MM2H passes are renewable. Before expiry, submit a renewal application to IMI. Ensure your Fixed Deposit account remains active, your medical insurance is valid, and you have fulfilled the minimum annual stay requirement (90 days per year under current rules).',
 '30 working days',
 'Current MM2H pass; Valid passport; Fixed Deposit bank statement (showing balance maintained); Medical insurance renewal certificate; Proof of minimum 90-day stay (passport stamps / entry records); IMI renewal form',
 'RM 90 per year of renewed pass',
 40),

('Visa Issuance & Extensions',
 'What happens if I renew my passport while on an MM2H pass?',
 'When you renew your passport, you must transfer your MM2H endorsement to the new passport. This is done at any IMI office. Both old and new passports must be presented. The pass validity and conditions remain unchanged.',
 '1 business day',
 'Old passport (with MM2H endorsement); New valid passport; IMI application form; Photographs',
 'Administrative fee (nominal)',
 50);

-- Category 2: Permissions & Domestic Helpers
INSERT INTO `mm2h_faqs` (`category`, `question`, `answer`, `processing_time`, `required_documents`, `fees`, `sort_order`) VALUES

('Permissions & Domestic Helpers',
 'Can my child study in Malaysia on an MM2H dependant pass?',
 'Yes. Children under 18 on MM2H dependant passes may apply for a study permission letter from IMI, allowing them to enrol in Malaysian schools or international schools without a separate student visa.',
 '30 working days',
 'Child''s MM2H dependant pass copy; Acceptance letter from school; Principal holder''s MM2H pass copy; Completed IMI application form',
 'Nominal administrative fee',
 60),

('Permissions & Domestic Helpers',
 'Can MM2H holders work part-time in Malaysia?',
 'MM2H holders may apply for permission to work part-time (up to 20 hours per week) with a Malaysian employer. The employer must obtain IMI approval. MM2H holders cannot be self-employed or run a business without a separate work pass.',
 '30 working days',
 'MM2H pass copy; Offer letter from employer; Employer''s business registration documents; IMI application form; Passport copy',
 'Subject to standard IMI fees',
 70),

('Permissions & Domestic Helpers',
 'How do I bring a foreign domestic helper under MM2H?',
 'MM2H principal holders are permitted to employ one foreign domestic helper. The application is made to IMI and involves verification of the principal''s financial capacity. The helper must hold a valid work permit.',
 '30 working days',
 'MM2H pass copy; Helper''s valid passport; Employment contract; Medical fitness certificate for helper; Immigration security bond; Employer''s bank statement (to prove financial capacity)',
 'Work permit fee + security levy (varies by nationality)',
 80),

('Permissions & Domestic Helpers',
 'How do I renew my domestic helper''s work permit?',
 'Work permit renewals for domestic helpers must be initiated at least one month before expiry. Submit the renewal package to IMI with updated insurance and medical documentation.',
 '30 working days',
 'Current work permit; Helper''s passport; Updated medical fitness certificate; Renewed medical insurance; IMI renewal form; Employer''s MM2H pass copy',
 'Renewal permit fee + levy',
 90);

-- Category 3: Termination & Changes
INSERT INTO `mm2h_faqs` (`category`, `question`, `answer`, `processing_time`, `required_documents`, `fees`, `sort_order`) VALUES

('Termination & Changes',
 'How do I voluntarily terminate my MM2H pass while in Malaysia?',
 'If you decide to give up your MM2H status while in Malaysia, submit a termination letter and return your pass documents to IMI. After approval, you may withdraw your Fixed Deposit in full. The process must be completed before you depart.',
 '3 business days',
 'Written termination letter; MM2H pass (all family members''); Valid passport; Fixed Deposit account details; IMI termination form',
 'No termination fee',
 100),

('Termination & Changes',
 'How do I terminate my MM2H pass from abroad?',
 'Termination can be initiated at the nearest Malaysian Embassy or High Commission in your country of residence. The embassy forwards the application to IMI in Kuala Lumpur for processing.',
 '3 business days (after receipt by IMI)',
 'Written termination letter (notarised); Copies of MM2H passes (all holders); Passport copies; Fixed Deposit details; Embassy covering letter',
 'Embassy administrative fee may apply',
 110),

('Termination & Changes',
 'What happens if the MM2H principal holder passes away in Malaysia?',
 'In the event of the principal holder''s death, dependants must notify IMI within 7 days. IMI will process the cancellation of all passes. Dependants may apply for a new pass category or return to their home country. The Fixed Deposit is released to the estate.',
 '3 business days',
 'Death certificate; All MM2H passes; Dependants'' passports; Letter from next-of-kin or legal representative; IMI notification form',
 'No fee',
 120),

('Termination & Changes',
 'Can I change the principal MM2H holder (e.g., to a spouse)?',
 'In certain circumstances (e.g., divorce or separation), a dependant spouse may apply to become the new principal holder. This requires IMI approval and resubmission of financial documents to prove the new principal meets eligibility criteria independently.',
 '30 working days',
 'Application letter explaining reason for change; Current MM2H passes; New principal''s financial statements; Fixed Deposit proof in new principal''s name; Legal documents (divorce decree, etc. if applicable)',
 'Standard IMI processing fee',
 130);

-- Category 4: Fixed Deposit Withdrawals
INSERT INTO `mm2h_faqs` (`category`, `question`, `answer`, `processing_time`, `required_documents`, `fees`, `sort_order`) VALUES

('Fixed Deposit Withdrawals',
 'When can I make a partial withdrawal from my MM2H Fixed Deposit?',
 'From Year 2 of holding your MM2H pass, you may make one partial withdrawal per year for approved purposes: purchase of residential property in Malaysia, children''s education fees in Malaysia, medical expenses in Malaysia, or the annual interest/profit earned. The remaining balance must not fall below the minimum required (RM 150,000 for applicants under 50; RM 50,000 for applicants 50 and above).',
 'Processed by the bank (no IMI timeline)',
 'MM2H pass copy; Bank''s internal FD withdrawal form; Supporting documents for purpose (property SPA / school invoice / medical bill); Tourism Malaysia approval letter (if required by bank)',
 'Bank processing fee (varies)',
 140),

('Fixed Deposit Withdrawals',
 'How much can I withdraw for a residential property purchase?',
 'You may withdraw from your Fixed Deposit to finance the purchase of a residential property in Malaysia. After the purchase, the FD balance must be topped back up to the required minimum. Present the Sale and Purchase Agreement (SPA) to your bank.',
 'Subject to bank processing time',
 'MM2H pass; Signed SPA or booking receipt; Property developer''s or seller''s bank details; Bank withdrawal form; Tourism Malaysia endorsement (some banks require this)',
 'Bank charges apply',
 150),

('Fixed Deposit Withdrawals',
 'Can I withdraw from my Fixed Deposit for education expenses?',
 'Yes. Tuition fees for your children''s education at recognised Malaysian institutions can be funded through a partial FD withdrawal. The invoice or official fee statement from the institution is required.',
 'Subject to bank processing time',
 'MM2H pass; Official tuition fee invoice from school/university; Child''s MM2H dependant pass copy; Bank withdrawal form',
 'Bank charges apply',
 160),

('Fixed Deposit Withdrawals',
 'Can I use my Fixed Deposit for medical expenses?',
 'Medical expenses incurred in Malaysia can be funded through a partial FD withdrawal. Submit the original hospital or clinic invoice together with the withdrawal request to your bank.',
 'Subject to bank processing time',
 'MM2H pass; Original medical invoice/hospital bill; Bank withdrawal form',
 'Bank charges apply',
 170),

('Fixed Deposit Withdrawals',
 'Can I transfer or withdraw the annual interest earned on my Fixed Deposit?',
 'Yes. The annual interest or profit credited to your FD account may be withdrawn or transferred to a current/savings account at any time without affecting the principal balance requirement. This does not count as a partial withdrawal.',
 'Immediate (bank transaction)',
 'Standard bank instruction form; MM2H pass copy (for bank records)',
 'No fee (standard bank transfer rates may apply)',
 180);
