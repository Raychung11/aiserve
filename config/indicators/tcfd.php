<?php
/**
 * TCFD — Task Force on Climate-related Financial Disclosures
 * 4 Pillars: Governance, Strategy, Risk Management, Metrics & Targets
 * 11 Core Recommendations
 *
 * Note: TCFD is now absorbed into IFRS S2 (ISSB). Companies should ideally
 * adopt ISSB S2 which fully incorporates TCFD. TCFD is kept here as many
 * lenders, insurers, and supply chain customers still reference it explicitly.
 */

return [

    // =====================================================
    // GOVERNANCE
    // =====================================================
    'GOVERNANCE' => [

        [
            'indicator_id' => 'TCFD-GOV-01',
            'code'         => 'TCFD-G1',
            'name'         => 'Board Oversight of Climate-related Risks',
            'description'  => 'Describe the board\'s oversight of climate-related risks and opportunities.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => true,
            'guidance'     => 'Name which board committee oversees climate (e.g. Audit, Risk, or ESG Committee). State frequency of climate risk reporting to the board. Describe how climate is integrated into board decisions.',
            'priority'     => 'critical',
            'financing_link' => 'BNM and Bursa TCFD/ISSB alignment requirement',
        ],
        [
            'indicator_id' => 'TCFD-GOV-02',
            'code'         => 'TCFD-G2',
            'name'         => 'Management Role in Climate Risk Assessment',
            'description'  => 'Describe management\'s role in assessing and managing climate-related risks and opportunities.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => true,
            'guidance'     => 'Name the management-level owner of climate risk (CEO, CFO, CSO, or Chief Risk Officer). Describe internal processes and committees that manage climate issues. Include sustainability working group if applicable.',
            'priority'     => 'high',
            'financing_link' => null,
        ],
    ],

    // =====================================================
    // ENVIRONMENT (Strategy + Metrics pillars)
    // =====================================================
    'ENVIRONMENT' => [

        [
            'indicator_id' => 'TCFD-STR-01',
            'code'         => 'TCFD-S1',
            'name'         => 'Climate Risks and Opportunities Identified',
            'description'  => 'Describe the climate-related risks and opportunities the organisation has identified over the short, medium, and long term.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => true,
            'guidance'     => 'Physical risks: floods, heat waves, water stress in Malaysia. Transition risks: carbon pricing, energy cost, regulatory changes. Opportunities: green products, energy savings, green financing access.',
            'priority'     => 'critical',
            'financing_link' => 'Required for green loan and bond applications',
        ],
        [
            'indicator_id' => 'TCFD-STR-02',
            'code'         => 'TCFD-S2',
            'name'         => 'Impact on Business, Strategy, and Financial Planning',
            'description'  => 'Describe the impact of climate-related risks and opportunities on the organisation\'s businesses, strategy, and financial planning.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => true,
            'guidance'     => 'Describe how climate risks affect: revenue (e.g. market demand shifts), costs (e.g. carbon tax, energy prices), assets (e.g. flood risk to facilities), capital allocation (e.g. green capex).',
            'priority'     => 'critical',
            'financing_link' => 'Required for climate-related financial disclosure to investors',
        ],
        [
            'indicator_id' => 'TCFD-STR-03',
            'code'         => 'TCFD-S3',
            'name'         => 'Climate Scenario Analysis',
            'description'  => 'Describe the resilience of the organisation\'s strategy, taking into consideration different climate-related scenarios, including a 2°C or lower scenario.',
            'unit'         => null,
            'data_type'    => 'boolean',
            'required'     => true,
            'guidance'     => 'Use at least two scenarios: below 2°C transition scenario + high physical risk (3–4°C) scenario. Reference NGFS, IEA Net Zero 2050, or IPCC AR6 scenarios. Describe how strategy is resilient under each scenario.',
            'priority'     => 'high',
            'financing_link' => 'Required for Bursa climate disclosure alignment',
        ],
        [
            'indicator_id' => 'TCFD-RISK-01',
            'code'         => 'TCFD-R1',
            'name'         => 'Climate Risk Identification and Assessment Process',
            'description'  => 'Describe the organisation\'s processes for identifying and assessing climate-related risks.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => true,
            'guidance'     => 'Describe the climate risk assessment methodology: who conducts it (internal team / consultant), data sources used, frequency of assessment, how risks are scored (likelihood × impact).',
            'priority'     => 'high',
            'financing_link' => null,
        ],
        [
            'indicator_id' => 'TCFD-RISK-02',
            'code'         => 'TCFD-R2',
            'name'         => 'Climate Risk Management Process',
            'description'  => 'Describe the organisation\'s processes for managing climate-related risks.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => true,
            'guidance'     => 'Describe how climate risks are prioritised, mitigated, transferred (insurance), or accepted. Include specific actions taken (e.g. flood barriers, energy efficiency, supply chain diversification).',
            'priority'     => 'high',
            'financing_link' => null,
        ],
        [
            'indicator_id' => 'TCFD-RISK-03',
            'code'         => 'TCFD-R3',
            'name'         => 'Integration into Overall Risk Management',
            'description'  => 'Describe how processes for identifying, assessing, and managing climate-related risks are integrated into the organisation\'s overall risk management.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => true,
            'guidance'     => 'State whether climate risk appears in your Enterprise Risk Register. Describe how it links to the board risk committee. Confirm whether climate risk has been assessed against other top business risks.',
            'priority'     => 'medium',
            'financing_link' => null,
        ],
        [
            'indicator_id' => 'TCFD-MET-01',
            'code'         => 'TCFD-M1',
            'name'         => 'Scope 1 GHG Emissions (TCFD)',
            'description'  => 'Disclose Scope 1 greenhouse gas emissions in tCO2e.',
            'unit'         => 'tCO2e',
            'data_type'    => 'number',
            'required'     => true,
            'guidance'     => 'Direct emissions from company-owned operations. Key sources: diesel generators, company vehicles, LPG/natural gas. Use Malaysia-specific emission factors from MyHIJAU / IPCC AR5.',
            'priority'     => 'critical',
            'financing_link' => 'Carbon tax compliance from 2026',
        ],
        [
            'indicator_id' => 'TCFD-MET-02',
            'code'         => 'TCFD-M2',
            'name'         => 'Scope 2 GHG Emissions (TCFD)',
            'description'  => 'Disclose Scope 2 GHG emissions from purchased energy.',
            'unit'         => 'tCO2e',
            'data_type'    => 'number',
            'required'     => true,
            'guidance'     => 'From TNB electricity bills. Malaysia grid emission factor: 0.694 kgCO2e/kWh (2023). Multiply total kWh × 0.000694 to get tCO2e.',
            'priority'     => 'critical',
            'financing_link' => 'Required for BNM climate risk assessment',
        ],
        [
            'indicator_id' => 'TCFD-MET-03',
            'code'         => 'TCFD-M3',
            'name'         => 'Scope 3 GHG Emissions (TCFD)',
            'description'  => 'Disclose Scope 3 GHG emissions if material.',
            'unit'         => 'tCO2e',
            'data_type'    => 'number',
            'required'     => false,
            'guidance'     => 'Focus on most material categories. Manufacturing: purchased goods & services (Cat 1), upstream transport (Cat 4), use of sold products (Cat 11). Use GHG Protocol Scope 3 Standard.',
            'priority'     => 'high',
            'financing_link' => 'EU CBAM and supply chain disclosure requirement',
        ],
        [
            'indicator_id' => 'TCFD-MET-04',
            'code'         => 'TCFD-M4',
            'name'         => 'Climate-related Targets',
            'description'  => 'Describe the targets used by the organisation to manage climate-related risks and opportunities and performance against targets.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => true,
            'guidance'     => 'State your GHG reduction target (e.g. 30% reduction in Scope 1+2 by 2030 vs 2022). Include interim milestones. Report actual performance vs target. Consider SBTi validation.',
            'priority'     => 'critical',
            'financing_link' => 'Sustainability-linked loan KPI — rate tied to target achievement',
        ],
        [
            'indicator_id' => 'TCFD-MET-05',
            'code'         => 'TCFD-M5',
            'name'         => 'Weighted Average Carbon Intensity (WACI)',
            'description'  => 'Weighted average carbon intensity of the investment or loan portfolio (for financial institutions) or supply chain (for corporates).',
            'unit'         => 'tCO2e/RM million revenue',
            'data_type'    => 'number',
            'required'     => false,
            'guidance'     => 'For non-financial companies: report your own carbon intensity (Scope 1+2 ÷ revenue). This metric is increasingly requested by lenders and institutional investors for portfolio carbon assessment.',
            'priority'     => 'medium',
            'financing_link' => 'Used in BNM climate stress testing for bank loan portfolios',
        ],
    ],

    'SOCIAL' => [],
];
