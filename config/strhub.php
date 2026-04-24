<?php

return [
    'platform_revenue_share' => env('PLATFORM_REVENUE_SHARE_PCT', 15) / 100,

    'subscription_plans' => [
        'starter'    => ['price' => 500,  'properties' => 5,    'agents' => 2],
        'growth'     => ['price' => 1500, 'properties' => 20,   'agents' => 10],
        'enterprise' => ['price' => 4000, 'properties' => 9999, 'agents' => 9999],
    ],

    'trial_days' => 14,

    'compliance_statuses' => ['green', 'amber', 'red'],

    'strategy_modes' => ['STR', 'MID_TERM', 'SUBLET', 'CORPORATE'],

    'tenancy_types' => ['STR', 'MID_TERM', 'SUBLET', 'CORPORATE'],

    'revenue_categories' => [
        'income'  => ['rental'],
        'expense' => ['cleaning', 'utilities', 'maintenance', 'platform_fee', 'commission', 'insurance', 'assessment', 'management_fee', 'other'],
    ],

    'agent_commission_tiers' => [5, 7, 10],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'model'   => env('OPENAI_MODEL', 'gpt-4o'),
    ],
];
