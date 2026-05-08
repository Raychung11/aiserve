<?php
/**
 * Subscription plans — source of truth for all pricing tiers
 * indicator_ids: array = specific indicators unlocked; 'all' = no gating
 */
return [

    'starter' => [
        'code'          => 'starter',
        'name'          => 'Starter',
        'tagline'       => 'Try before you commit',
        'price_myr'     => 0,
        'billing'       => 'free',
        'badge'         => 'Free forever',
        'color'         => '#64748b',
        'company_limit' => 1,
        'frameworks'    => ['BURSA_SEDG'],
        // 5 preview indicators — one from each mandatory category
        'indicator_ids' => [
            'SEDG-E01', // Energy — most visible
            'SEDG-S01', // Employee headcount — easy to fill
            'SEDG-S07', // Safety incidents — critical
            'SEDG-G01', // Board gender — Bursa mandatory
            'SEDG-G07', // Anti-corruption — MACC S17A
        ],
        'features' => [
            '5 Bursa SEDG core indicators',
            '1 company',
            'ESG score dashboard',
            'Basic data entry',
        ],
        'locked_features' => [
            'Gap analysis & recommendations',
            'PDF report generation',
            'Carbon calculator',
            'Benchmarking',
            'Indicator Collections add-ons',
        ],
        'cta'     => 'Get Started Free',
        'popular' => false,
    ],

    'standard' => [
        'code'          => 'standard',
        'name'          => 'Standard',
        'tagline'       => 'Bursa SEDG mandatory compliance',
        'price_myr'     => 1500,
        'billing'       => 'annual',
        'badge'         => 'RM 1,500 / year',
        'color'         => '#16a34a',
        'company_limit' => 1,
        'frameworks'    => ['BURSA_SEDG'],
        // 15 mandatory Bursa SEDG primary indicators (SEDG 2022)
        'indicator_ids' => [
            // Environment — 5 mandatory
            'SEDG-E01', // Energy intensity
            'SEDG-E03', // Water intensity
            'SEDG-E04', // Scope 1 GHG
            'SEDG-E05', // Scope 2 GHG
            'SEDG-E07', // Waste generated
            // Social — 6 mandatory
            'SEDG-S01', // Employee headcount by gender
            'SEDG-S02', // Employee turnover
            'SEDG-S04', // Average training hours
            'SEDG-S07', // LTIFR / work injuries
            'SEDG-S08', // Fatalities
            'SEDG-S09', // Parental leave
            // Governance — 4 mandatory
            'SEDG-G01', // Board gender diversity
            'SEDG-G03', // Board age diversity
            'SEDG-G07', // Anti-corruption
            'SEDG-G09', // Community investment
        ],
        'features' => [
            '15 mandatory Bursa SEDG indicators',
            '1 company',
            'Gap analysis & prioritised fixes',
            'PDF compliance report',
            'Carbon calculator (Scope 1, 2, 3)',
            'Benchmarking vs industry peers',
            'Indicator Collections add-ons',
            'Email support',
        ],
        'locked_features' => [
            'Multi-framework access',
            'Consultant multi-company mode',
        ],
        'cta'     => 'Choose Standard',
        'popular' => true,
        'trial_days' => 14,
    ],

    'professional' => [
        'code'          => 'professional',
        'name'          => 'Professional',
        'tagline'       => 'All frameworks. All indicators.',
        'price_myr'     => 3500,
        'billing'       => 'annual',
        'badge'         => 'RM 3,500 / year',
        'color'         => '#7c3aed',
        'company_limit' => 5,
        'frameworks'    => 'all',
        'indicator_ids' => 'all', // no gating
        'features' => [
            'All 200+ indicators across 10 frameworks',
            'Up to 5 companies',
            'GRI, ISSB, ESRS, CDP, TCFD, UN SDGs, SASB',
            'All Indicator Collections included',
            'Consultant multi-company management',
            'Admin panel',
            'API access (coming soon)',
            'Priority support + onboarding call',
        ],
        'locked_features' => [],
        'cta'     => 'Go Professional',
        'popular' => false,
        'trial_days' => 14,
    ],
];
