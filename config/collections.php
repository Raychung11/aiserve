<?php
/**
 * Indicator Collections — add-on bundles for Standard plan users
 * Each collection unlocks additional indicators from specific frameworks
 * Compatible with Standard plan; Professional plan includes all collections
 */
return [

    'bursa_full' => [
        'code'        => 'bursa_full',
        'name'        => 'Bursa SEDG Full Pack',
        'tagline'     => 'All 41 Bursa SEDG indicators',
        'description' => 'Unlock the remaining 26 Bursa SEDG indicators beyond the standard 15. Full coverage of all recommended and sector-specific disclosures for Main Market and ACE Market companies.',
        'price_myr'   => 500,
        'billing'     => 'annual',
        'icon'        => 'bi-bank2',
        'color'       => '#16a34a',
        'framework'   => 'BURSA_SEDG',
        'badge'       => 'RM 500/yr',
        'indicator_ids' => [
            // Additional Environment (8)
            'SEDG-E02','SEDG-E06','SEDG-E08','SEDG-E09','SEDG-E10','SEDG-E11','SEDG-E12','SEDG-E13',
            // Additional Social (9)
            'SEDG-S03','SEDG-S05','SEDG-S06','SEDG-S10','SEDG-S11','SEDG-S12','SEDG-S13','SEDG-S14','SEDG-S15',
            // Additional Governance (9)
            'SEDG-G02','SEDG-G04','SEDG-G05','SEDG-G06','SEDG-G08','SEDG-G10','SEDG-G11','SEDG-G12',
        ],
        'best_for'     => 'Main Market & ACE Market listed companies, pre-IPO targets',
        'urgent_note'  => null,
        'indicator_count' => 26,
    ],

    'climate_pack' => [
        'code'        => 'climate_pack',
        'name'        => 'Climate Action Pack',
        'tagline'     => 'CDP + TCFD + Scope 3',
        'description' => 'Complete climate disclosure set: TCFD scenario analysis, CDP Climate questionnaire, and full Scope 1/2/3 reporting. Mandatory for suppliers to Apple, IKEA, Walmart, Siemens, and other CDP-requesting buyers.',
        'price_myr'   => 800,
        'billing'     => 'annual',
        'icon'        => 'bi-cloud-lightning-rain',
        'color'       => '#0891b2',
        'framework'   => 'CDP',
        'badge'       => 'RM 800/yr',
        'indicator_ids' => [
            // TCFD (11)
            'TCFD-GOV-01','TCFD-GOV-02',
            'TCFD-STR-01','TCFD-STR-02','TCFD-STR-03',
            'TCFD-RISK-01','TCFD-RISK-02','TCFD-RISK-03',
            'TCFD-MET-01','TCFD-MET-02','TCFD-MET-03','TCFD-MET-04','TCFD-MET-05',
            // CDP (12)
            'CDP-C1-1','CDP-C1-2','CDP-C2-1','CDP-C3-1',
            'CDP-C4-1','CDP-C4-2','CDP-C6-1','CDP-C6-3',
            'CDP-C6-5','CDP-C8-1','CDP-C10-1','CDP-C11-2',
        ],
        'best_for'     => 'Suppliers to multinational buyers requiring CDP reporting',
        'urgent_note'  => 'CDP 2024 deadline: July 31 annually',
        'indicator_count' => 25,
    ],

    'eu_csrd_pack' => [
        'code'        => 'eu_csrd_pack',
        'name'        => 'EU Export Ready Pack',
        'tagline'     => 'Full ESRS Set 1 (CSRD)',
        'description' => 'All 22 ESRS mandatory disclosures under the EU Corporate Sustainability Reporting Directive (CSRD). Critical for Malaysian exporters of palm oil, rubber, electronics, and textiles with EU revenue > €150M.',
        'price_myr'   => 1200,
        'billing'     => 'annual',
        'icon'        => 'bi-globe-europe-africa',
        'color'       => '#dc2626',
        'framework'   => 'ESRS',
        'badge'       => 'RM 1,200/yr',
        'indicator_ids' => [
            // ESRS Governance (5)
            'ESRS-GOV1','ESRS-GOV2','ESRS-GOV4','ESRS-G1-1','ESRS-G1-4',
            // ESRS Environment (8)
            'ESRS-E1-1','ESRS-E1-4','ESRS-E1-5','ESRS-E1-6','ESRS-E1-7',
            'ESRS-E2-1','ESRS-E3-1','ESRS-E5-1',
            // ESRS Social (6)
            'ESRS-S1-6','ESRS-S1-8','ESRS-S1-9','ESRS-S1-10','ESRS-S1-14','ESRS-S1-16',
        ],
        'best_for'     => 'Palm oil, rubber, electronics, textile companies exporting to EU',
        'urgent_note'  => 'CSRD effective for large companies from 2025 reporting year',
        'indicator_count' => 22,
    ],

    'sasb_industry' => [
        'code'        => 'sasb_industry',
        'name'        => 'SASB Industry Pack',
        'tagline'     => 'Manufacturing + F&B + Tech Hardware',
        'description' => 'Sector-specific SASB indicators for all three Malaysia-dominant industries in one pack. Covers RT-IG (Manufacturing), FB-PF (Food & Beverage), and TC-HW (Technology Hardware).',
        'price_myr'   => 800,
        'billing'     => 'annual',
        'icon'        => 'bi-gear-wide-connected',
        'color'       => '#d97706',
        'framework'   => 'SASB_MANUFACTURING',
        'badge'       => 'RM 800/yr',
        'indicator_ids' => [
            // SASB Manufacturing (13)
            'SASB-MFG-E01','SASB-MFG-E02','SASB-MFG-E03','SASB-MFG-E04','SASB-MFG-E05','SASB-MFG-E06',
            'SASB-MFG-S01','SASB-MFG-S02','SASB-MFG-S03','SASB-MFG-S04',
            'SASB-MFG-G01','SASB-MFG-G02','SASB-MFG-G03',
            // SASB Food & Beverage (10)
            'SASB-FB-E01','SASB-FB-E02','SASB-FB-E03','SASB-FB-E04','SASB-FB-E05',
            'SASB-FB-S01','SASB-FB-S02','SASB-FB-S03',
            'SASB-FB-G01','SASB-FB-G02',
            // SASB Tech Hardware (10)
            'SASB-TECH-E01','SASB-TECH-E02','SASB-TECH-E03','SASB-TECH-E04',
            'SASB-TECH-S01','SASB-TECH-S02','SASB-TECH-S03','SASB-TECH-S04',
            'SASB-TECH-G01','SASB-TECH-G02',
        ],
        'best_for'     => 'Manufacturers, food producers, Penang/Johor electronics factories',
        'urgent_note'  => null,
        'indicator_count' => 33,
    ],
];
