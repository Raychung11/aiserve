<?php
/**
 * ESRS — European Sustainability Reporting Standards (Set 1)
 * Under EU Corporate Sustainability Reporting Directive (CSRD)
 * Effective: Large companies from 2024, SMEs from 2026
 *
 * Mandatory for non-EU companies if:
 *   - Revenue from EU operations > €150M, OR
 *   - Have EU-listed securities, OR
 *   - Are significant supplier to EU companies subject to CSRD
 *
 * Critical for Malaysian exporters to EU (palm oil, rubber, electronics, textiles)
 */

return [

    // =====================================================
    // GOVERNANCE — ESRS 2 + ESRS G1
    // =====================================================
    'GOVERNANCE' => [

        [
            'indicator_id' => 'ESRS-GOV1',
            'code'         => 'ESRS 2-GOV1',
            'name'         => 'Governance Bodies\' Role in Sustainability',
            'description'  => 'Composition, responsibilities, and sustainability expertise of the governance bodies overseeing ESG strategy.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => true,
            'guidance'     => 'Describe board composition, ESG expertise of board members, dedicated sustainability committee, and frequency of ESG reporting. Include gender diversity of board (ESRS S1-9 links here).',
            'priority'     => 'critical',
            'financing_link' => 'Required for EU Green Bond Standard and EU taxonomy alignment',
        ],
        [
            'indicator_id' => 'ESRS-GOV2',
            'code'         => 'ESRS 2-GOV2',
            'name'         => 'Management Role in Sustainability',
            'description'  => 'Description of management\'s role and responsibilities for sustainability-related impacts, risks, and opportunities.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => true,
            'guidance'     => 'Name C-suite owner of sustainability. Describe how sustainability is integrated into management decisions, performance incentives, and reporting structures.',
            'priority'     => 'high',
            'financing_link' => null,
        ],
        [
            'indicator_id' => 'ESRS-GOV4',
            'code'         => 'ESRS 2-GOV4',
            'name'         => 'Statement on Due Diligence',
            'description'  => 'Whether the company has a due diligence process for principal adverse impacts, in line with international standards (OECD, UNGP).',
            'unit'         => null,
            'data_type'    => 'boolean',
            'required'     => true,
            'guidance'     => 'Describe your human rights and environmental due diligence (HREDD) process. Reference OECD MNE Guidelines and UN Guiding Principles on Business and Human Rights. Mandatory under EU CSRD.',
            'priority'     => 'critical',
            'financing_link' => 'Required for EU supply chain compliance from 2025',
        ],
        [
            'indicator_id' => 'ESRS-G1-1',
            'code'         => 'ESRS G1-1',
            'name'         => 'Anti-corruption and Anti-bribery Policies',
            'description'  => 'Description of anti-corruption and anti-bribery policies, procedures, and training coverage.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => true,
            'guidance'     => 'Describe: anti-bribery policy scope, MACC Section 17A compliance, anti-corruption training % coverage, gift/hospitality policy, political donation policy. ESRS requires quantitative training coverage.',
            'priority'     => 'critical',
            'financing_link' => 'Required for EU supplier codes of conduct',
        ],
        [
            'indicator_id' => 'ESRS-G1-4',
            'code'         => 'ESRS G1-4',
            'name'         => 'Confirmed Incidents of Corruption',
            'description'  => 'Number of confirmed incidents of corruption and fines or penalties paid.',
            'unit'         => 'incidents',
            'data_type'    => 'number',
            'required'     => true,
            'guidance'     => 'Report confirmed cases investigated and substantiated. Include: bribery, fraud, facilitation payments, kickbacks. Disclose legal proceedings, fines, and sanctions. Zero is an acceptable and expected response.',
            'priority'     => 'critical',
            'financing_link' => null,
        ],
    ],

    // =====================================================
    // ENVIRONMENT — ESRS E1 (Climate) + E2 (Pollution) + E3 (Water) + E5 (Circular)
    // =====================================================
    'ENVIRONMENT' => [

        [
            'indicator_id' => 'ESRS-E1-1',
            'code'         => 'ESRS E1-1',
            'name'         => 'Transition Plan for Climate Change Mitigation',
            'description'  => 'Description of the climate change transition plan including targets, milestones, and alignment with the Paris Agreement.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => true,
            'guidance'     => 'ESRS requires a formal transition plan aligned with 1.5°C pathway. Describe: GHG reduction milestones by 2025, 2030, 2040, 2050. Include capital expenditure plan for decarbonisation. Reference Malaysia Net Zero 2050 target.',
            'priority'     => 'critical',
            'financing_link' => 'EU Green Bond Standard — transition plan mandatory',
        ],
        [
            'indicator_id' => 'ESRS-E1-4',
            'code'         => 'ESRS E1-4',
            'name'         => 'Climate Change Mitigation Targets',
            'description'  => 'GHG emission reduction targets including Scope 1, 2, and 3 targets, and whether they are science-based.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => true,
            'guidance'     => 'State: absolute or intensity-based target, base year, target year, % reduction for each scope, and whether validated by Science Based Targets initiative (SBTi). Include interim milestones.',
            'priority'     => 'critical',
            'financing_link' => 'EU Taxonomy alignment for sustainable investment',
        ],
        [
            'indicator_id' => 'ESRS-E1-5',
            'code'         => 'ESRS E1-5',
            'name'         => 'Energy Consumption and Energy Mix',
            'description'  => 'Total energy consumption from fossil fuels, nuclear, and renewables. Energy intensity per unit of output.',
            'unit'         => 'MWh',
            'data_type'    => 'number',
            'required'     => true,
            'guidance'     => 'ESRS requires detailed energy breakdown: coal, oil, gas, nuclear, hydro, solar, wind, biomass. Calculate renewable % of total. ESRS is stricter than GRI — requires breakdown by fuel type.',
            'priority'     => 'critical',
            'financing_link' => 'EU Taxonomy — renewable energy % threshold for alignment',
        ],
        [
            'indicator_id' => 'ESRS-E1-6',
            'code'         => 'ESRS E1-6',
            'name'         => 'Gross Scope 1, 2, and 3 GHG Emissions',
            'description'  => 'Gross GHG emissions broken down by Scope 1, Scope 2 (location-based and market-based), and Scope 3.',
            'unit'         => 'tCO2e',
            'data_type'    => 'number',
            'required'     => true,
            'guidance'     => 'ESRS requires ALL THREE scopes with breakdown by GHG type (CO2, CH4, N2O, HFCs, PFCs, SF6, NF3). Express in tCO2e using IPCC AR6 GWP100. Disclose methodology, standards, and assumptions.',
            'priority'     => 'critical',
            'financing_link' => 'Required for EU supply chain CSRD compliance from 2025',
        ],
        [
            'indicator_id' => 'ESRS-E1-7',
            'code'         => 'ESRS E1-7',
            'name'         => 'GHG Removals and Carbon Credits',
            'description'  => 'GHG removals and the use of high-quality carbon credits to compensate for residual emissions.',
            'unit'         => 'tCO2e',
            'data_type'    => 'number',
            'required'     => false,
            'guidance'     => 'ESRS distinguishes between reductions (preferred) and offsets (lower quality). Disclose carbon credits used: type (removal vs avoidance), vintage, standard (Gold Standard, VCS), and project location.',
            'priority'     => 'medium',
            'financing_link' => null,
        ],
        [
            'indicator_id' => 'ESRS-E2-1',
            'code'         => 'ESRS E2-1',
            'name'         => 'Pollution Prevention Policies',
            'description'  => 'Policies related to prevention and control of pollution to air, water, and soil.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => true,
            'guidance'     => 'Describe: DOE licence compliance, air emission controls, wastewater treatment, soil contamination prevention, chemical management. Reference EQA 1974 and DOE Permit/Licence conditions.',
            'priority'     => 'high',
            'financing_link' => null,
        ],
        [
            'indicator_id' => 'ESRS-E3-1',
            'code'         => 'ESRS E3-1',
            'name'         => 'Water and Marine Resources Policy',
            'description'  => 'Policies for managing water use, reducing water consumption, and protecting marine resources.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => true,
            'guidance'     => 'Describe water management policy: withdrawal targets, recycling targets, zero-liquid-discharge aspirations. For coastal operations: describe marine protection commitments.',
            'priority'     => 'high',
            'financing_link' => null,
        ],
        [
            'indicator_id' => 'ESRS-E5-1',
            'code'         => 'ESRS E5-1',
            'name'         => 'Resource Use and Circular Economy Policy',
            'description'  => 'Policies related to sustainable resource use, waste reduction, and circular economy principles.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => true,
            'guidance'     => 'Describe: waste reduction targets, recycling rate, design for circularity, extended producer responsibility, refurbishment/remanufacturing programs. Reference EU Circular Economy Action Plan.',
            'priority'     => 'high',
            'financing_link' => 'EU Taxonomy — circular economy activities classification',
        ],
    ],

    // =====================================================
    // SOCIAL — ESRS S1 (Own Workforce)
    // =====================================================
    'SOCIAL' => [

        [
            'indicator_id' => 'ESRS-S1-6',
            'code'         => 'ESRS S1-6',
            'name'         => 'Workforce Headcount and Characteristics',
            'description'  => 'Total number of employees broken down by gender, employment type, and country.',
            'unit'         => 'persons',
            'data_type'    => 'number',
            'required'     => true,
            'guidance'     => 'ESRS requires detailed breakdown: employees vs contractors, full-time vs part-time, permanent vs temporary, by gender, and by country if multinational. More granular than GRI 401-1.',
            'priority'     => 'critical',
            'financing_link' => 'EU supply chain CSRD — your data feeds into customer reports',
        ],
        [
            'indicator_id' => 'ESRS-S1-8',
            'code'         => 'ESRS S1-8',
            'name'         => 'Collective Bargaining Coverage',
            'description'  => '% of own workforce covered by collective bargaining agreements.',
            'unit'         => '%',
            'data_type'    => 'percentage',
            'required'     => true,
            'guidance'     => 'Describe trade union presence, collective bargaining agreements, and whether workers\' right to organise is respected. Under Malaysia Industrial Relations Act 1967.',
            'priority'     => 'high',
            'financing_link' => null,
        ],
        [
            'indicator_id' => 'ESRS-S1-9',
            'code'         => 'ESRS S1-9',
            'name'         => 'Diversity Metrics (Gender and Age)',
            'description'  => '% of women in workforce, management, and governance body. Age group breakdown.',
            'unit'         => '%',
            'data_type'    => 'percentage',
            'required'     => true,
            'guidance'     => 'ESRS requires gender AND age diversity: <30 years, 30–50 years, >50 years for all three levels (board, management, all employees). Include non-binary if applicable.',
            'priority'     => 'critical',
            'financing_link' => 'EU CSRD mandatory for companies in EU supply chains',
        ],
        [
            'indicator_id' => 'ESRS-S1-10',
            'code'         => 'ESRS S1-10',
            'name'         => 'Adequate Wages',
            'description'  => '% of employees earning below the adequate wage threshold, and description of wages policy.',
            'unit'         => '%',
            'data_type'    => 'percentage',
            'required'     => true,
            'guidance'     => 'ESRS uses "adequate wage" concept (not just minimum wage). For Malaysia: above RM1,700/month + benefits. Disclose: % of lowest-paid workers relative to living wage benchmark.',
            'priority'     => 'critical',
            'financing_link' => 'UFLPA labour compliance for US market + EU CSRD',
        ],
        [
            'indicator_id' => 'ESRS-S1-14',
            'code'         => 'ESRS S1-14',
            'name'         => 'Health and Safety Metrics',
            'description'  => 'Number of fatalities, work-related ill health cases, and lost-time injury frequency rate.',
            'unit'         => 'incidents',
            'data_type'    => 'number',
            'required'     => true,
            'guidance'     => 'ESRS requires: fatalities (employees AND contractors), LTIFR, recordable injuries, occupational disease cases, days lost. More comprehensive than Bursa S-07 and S-08.',
            'priority'     => 'critical',
            'financing_link' => null,
        ],
        [
            'indicator_id' => 'ESRS-S1-16',
            'code'         => 'ESRS S1-16',
            'name'         => 'CEO Pay Ratio',
            'description'  => 'Ratio of CEO annual total compensation to the median annual total compensation of all employees.',
            'unit'         => 'ratio',
            'data_type'    => 'number',
            'required'     => false,
            'guidance'     => 'Divide CEO total compensation (salary + bonus + benefits) by median employee compensation. A high ratio (>100:1) may raise red flags with EU investors. Contextualise with industry norms.',
            'priority'     => 'medium',
            'financing_link' => null,
        ],
    ],
];
