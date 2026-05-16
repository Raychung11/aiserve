<?php
/**
 * Subscription plans — source of truth for all pricing tiers
 * indicator_ids: array = specific indicators unlocked; 'all' = no gating
 */
return [

    // ── Free tier ────────────────────────────────────────────────────────────
    // All 15 mandatory Bursa SEDG indicators + basic report generation.
    // No credit card required — SMEs can start immediately.
    'starter' => [
        'code'          => 'starter',
        'name'          => 'Free',
        'tagline'       => 'Start your ESG journey at no cost',
        'price_myr'     => 0,
        'billing'       => 'free',
        'badge'         => 'Free forever',
        'color'         => '#16a34a',
        'company_limit' => 1,
        'frameworks'    => ['BURSA_SEDG'],
        // All 15 mandatory Bursa SEDG primary indicators
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
            'All 15 mandatory Bursa SEDG indicators',
            'ESG score dashboard',
            'Basic ESG report generation',
            '1 company profile',
            'Carbon calculator (view only)',
        ],
        'locked_features' => [
            'Full data collection OS (all indicators)',
            'Advanced report generation & PDF export',
            'Gap analysis & recommendations',
            'Benchmarking vs industry peers',
            'GRI, ISSB, ESRS & other frameworks',
        ],
        'cta'     => 'Get Started Free',
        'popular' => false,
    ],

    // ── Platform subscription ─────────────────────────────────────────────────
    // Full data collection on the AiServe ESG OS platform.
    // All indicators across all frameworks + full report generation.
    'standard' => [
        'code'          => 'standard',
        'name'          => 'Platform',
        'tagline'       => 'Full ESG data collection & report generation',
        'price_myr'     => 1500,
        'billing'       => 'annual',
        'badge'         => 'RM 1,500 / year',
        'color'         => '#0ea5e9',
        'company_limit' => 1,
        'frameworks'    => 'all',
        'indicator_ids' => 'all', // full access — no gating
        'features' => [
            'All indicators across all frameworks',
            'Full ESG OS data collection',
            'Full report generation & PDF export',
            'Gap analysis & prioritised action plan',
            'Carbon calculator (Scope 1, 2 & 3)',
            'Benchmarking vs industry peers',
            'GRI, ISSB, ESRS, CDP, TCFD frameworks',
            '1 company profile',
            'Email support',
        ],
        'locked_features' => [],
        'cta'          => 'Subscribe — RM 1,500/year',
        'popular'      => true,
        'trial_days'   => 14,
    ],

    // ── Professional (internal — used for 14-day trial fallback only) ─────────
    // Not shown publicly. Mirrors 'standard' for trial purposes.
    'professional' => [
        'code'          => 'professional',
        'name'          => 'Platform',
        'tagline'       => 'Full ESG data collection & report generation',
        'price_myr'     => 1500,
        'billing'       => 'annual',
        'badge'         => 'RM 1,500 / year',
        'color'         => '#0ea5e9',
        'company_limit' => 1,
        'frameworks'    => 'all',
        'indicator_ids' => 'all',
        'features'      => [],
        'locked_features' => [],
        'cta'           => 'Subscribe',
        'popular'       => false,
        'trial_days'    => 14,
    ],
];
