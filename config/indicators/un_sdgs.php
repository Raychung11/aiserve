<?php
/**
 * UN Sustainable Development Goals (SDGs) — Alignment Mapping
 * 2030 Agenda for Sustainable Development
 * 17 Goals — Map business activities to material SDGs
 *
 * This framework helps companies demonstrate SDG alignment for:
 * - SME Corp Malaysia grants
 * - UNDP/UNIDO programs
 * - Impact investor reporting
 * - Green sukuk and green bond frameworks
 * - BNM sustainable finance taxonomy
 */

return [

    // =====================================================
    // SDGs mapped to ENVIRONMENT category
    // =====================================================
    'ENVIRONMENT' => [

        [
            'indicator_id' => 'SDG-06',
            'code'         => 'SDG 6',
            'name'         => 'SDG 6: Clean Water and Sanitation',
            'description'  => 'Describe how the company ensures responsible water use, protects water quality, and contributes to clean water access.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => false,
            'guidance'     => 'Reference water consumption data (E-08). Describe water-saving initiatives, effluent treatment, and any community water access programs. State whether operations are in water-stressed areas.',
            'priority'     => 'medium',
            'financing_link' => 'Relevant for water-related green financing',
        ],
        [
            'indicator_id' => 'SDG-07',
            'code'         => 'SDG 7',
            'name'         => 'SDG 7: Affordable and Clean Energy',
            'description'  => 'Describe efforts to improve energy efficiency, transition to renewable energy, and contribute to affordable energy access.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => false,
            'guidance'     => 'Reference energy data (E-01, E-03). Describe: solar panel installation, LED lighting upgrades, energy management system, green tariff electricity purchase from TNB. State renewable energy % of total.',
            'priority'     => 'high',
            'financing_link' => 'SEDA Malaysia renewable energy incentives, BNM Low Carbon Transition Facility',
        ],
        [
            'indicator_id' => 'SDG-12',
            'code'         => 'SDG 12',
            'name'         => 'SDG 12: Responsible Consumption and Production',
            'description'  => 'Describe sustainable procurement practices, waste reduction, circular economy initiatives, and responsible production.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => false,
            'guidance'     => 'Reference waste data (E-10, E-11). Describe: waste reduction targets, recycling programs, supplier sustainability requirements, eco-design principles, sustainable packaging.',
            'priority'     => 'high',
            'financing_link' => 'SME Corp Sustainability Grant — circular economy',
        ],
        [
            'indicator_id' => 'SDG-13',
            'code'         => 'SDG 13',
            'name'         => 'SDG 13: Climate Action',
            'description'  => 'Describe the company\'s actions to combat climate change and its impacts, including GHG reduction and climate risk management.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => false,
            'guidance'     => 'Reference emissions data (E-04, E-05). Describe: GHG reduction target, decarbonisation actions, climate risk assessment, carbon offsetting. Alignment with Malaysia\'s NDC and Net Zero 2050 goal.',
            'priority'     => 'critical',
            'financing_link' => 'All green financing — carbon tax readiness',
        ],
        [
            'indicator_id' => 'SDG-14',
            'code'         => 'SDG 14',
            'name'         => 'SDG 14: Life Below Water',
            'description'  => 'Describe actions to reduce marine pollution, protect coastal ecosystems, and prevent ocean plastic waste.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => false,
            'guidance'     => 'Relevant for coastal/maritime operations, fisheries, tourism. Describe: wastewater treatment, marine plastic reduction, coastal conservation. Not applicable = state N/A.',
            'priority'     => 'low',
            'financing_link' => null,
        ],
        [
            'indicator_id' => 'SDG-15',
            'code'         => 'SDG 15',
            'name'         => 'SDG 15: Life on Land',
            'description'  => 'Describe efforts to protect terrestrial ecosystems, prevent deforestation, halt biodiversity loss, and combat land degradation.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => false,
            'guidance'     => 'Relevant for palm oil, timber, agriculture, mining, construction. Describe: no-deforestation commitment, biodiversity management plan, land rehabilitation. Reference E-13.',
            'priority'     => 'medium',
            'financing_link' => 'EU Deforestation Regulation (EUDR) compliance for palm oil and rubber exporters',
        ],
    ],

    // =====================================================
    // SDGs mapped to SOCIAL category
    // =====================================================
    'SOCIAL' => [

        [
            'indicator_id' => 'SDG-01',
            'code'         => 'SDG 1',
            'name'         => 'SDG 1: No Poverty',
            'description'  => 'Describe how the company contributes to poverty alleviation through living wages, supplier development, and community investment.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => false,
            'guidance'     => 'Reference: living wage compliance (S-13), community investment (S-15). Describe: whether all workers earn above minimum wage (RM1,700/month), supplier development programs, B40 community support.',
            'priority'     => 'medium',
            'financing_link' => 'Required for Khazanah, EPF, and impact investment mandates',
        ],
        [
            'indicator_id' => 'SDG-03',
            'code'         => 'SDG 3',
            'name'         => 'SDG 3: Good Health and Well-being',
            'description'  => 'Describe workplace health and safety practices, employee wellness programs, and community health contributions.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => false,
            'guidance'     => 'Reference OHS data (S-07, S-08). Describe: HIRARC process, DOSH compliance, employee health benefits, mental health support, medical coverage, wellness programs.',
            'priority'     => 'high',
            'financing_link' => null,
        ],
        [
            'indicator_id' => 'SDG-04',
            'code'         => 'SDG 4',
            'name'         => 'SDG 4: Quality Education',
            'description'  => 'Describe investments in employee education, skills training, and community education programs.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => false,
            'guidance'     => 'Reference training data (S-10, S-11). Describe: apprenticeships, TVET partnerships, scholarship programs, digital upskilling, HRD Corp levy utilisation.',
            'priority'     => 'medium',
            'financing_link' => 'HRD Corp / HRDC training grants',
        ],
        [
            'indicator_id' => 'SDG-05',
            'code'         => 'SDG 5',
            'name'         => 'SDG 5: Gender Equality',
            'description'  => 'Describe gender equality initiatives, women in leadership programs, and equal pay practices.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => false,
            'guidance'     => 'Reference: % women workforce (S-04), % women management (S-05), % women board (S-06). Describe: gender pay equity review, women\'s leadership program, maternity support beyond legal minimum.',
            'priority'     => 'high',
            'financing_link' => 'Bursa 30% women on board target — investor ESG scoring',
        ],
        [
            'indicator_id' => 'SDG-08',
            'code'         => 'SDG 8',
            'name'         => 'SDG 8: Decent Work and Economic Growth',
            'description'  => 'Describe how the company promotes decent work, fair wages, safe working conditions, and economic opportunity.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => false,
            'guidance'     => 'Reference: employee count (S-01), turnover (S-03), OHS (S-07, S-08), living wage (S-13). Describe: collective bargaining, freedom of association, anti-forced labour policy. Key for UFLPA compliance.',
            'priority'     => 'critical',
            'financing_link' => 'UFLPA compliance for US export market access',
        ],
        [
            'indicator_id' => 'SDG-10',
            'code'         => 'SDG 10',
            'name'         => 'SDG 10: Reduced Inequalities',
            'description'  => 'Describe efforts to reduce inequality within the workforce and in the communities where the company operates.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => false,
            'guidance'     => 'Describe: Bumiputera contractor development, B40 hiring programs, persons with disabilities (OKU) employment, equal pay review, migrant worker fair treatment.',
            'priority'     => 'medium',
            'financing_link' => null,
        ],
        [
            'indicator_id' => 'SDG-11',
            'code'         => 'SDG 11',
            'name'         => 'SDG 11: Sustainable Cities and Communities',
            'description'  => 'Describe contributions to sustainable urban development, affordable housing access, and community resilience.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => false,
            'guidance'     => 'Relevant for construction, real estate, local government suppliers. Describe: green building standards (GBI, GreenRE), affordable housing projects, community infrastructure support.',
            'priority'     => 'low',
            'financing_link' => null,
        ],
    ],

    // =====================================================
    // SDGs mapped to GOVERNANCE category
    // =====================================================
    'GOVERNANCE' => [

        [
            'indicator_id' => 'SDG-09',
            'code'         => 'SDG 9',
            'name'         => 'SDG 9: Industry, Innovation and Infrastructure',
            'description'  => 'Describe investments in sustainable infrastructure, innovation, and industrialisation.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => false,
            'guidance'     => 'Describe: R&D investment, Industry 4.0 adoption, green infrastructure capex, digital transformation, support for local technology suppliers. Include R&D spend as % of revenue if available.',
            'priority'     => 'medium',
            'financing_link' => 'MIDA green technology incentives, MDEC digital grants',
        ],
        [
            'indicator_id' => 'SDG-16',
            'code'         => 'SDG 16',
            'name'         => 'SDG 16: Peace, Justice and Strong Institutions',
            'description'  => 'Describe commitments to anti-corruption, rule of law, transparency, and inclusive governance.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => false,
            'guidance'     => 'Reference: anti-corruption policy (G-05), whistleblower policy (G-06), confirmed incidents (G-12). Describe: MACC Section 17A compliance, data privacy (PDPA), regulatory compliance record.',
            'priority'     => 'high',
            'financing_link' => 'Required for government contracts and procurement eligibility',
        ],
        [
            'indicator_id' => 'SDG-17',
            'code'         => 'SDG 17',
            'name'         => 'SDG 17: Partnerships for the Goals',
            'description'  => 'Describe partnerships with government, NGOs, industry associations, and the private sector to advance SDGs.',
            'unit'         => null,
            'data_type'    => 'text',
            'required'     => false,
            'guidance'     => 'Describe: industry association memberships, government-linked partnerships, UN Global Compact membership, SDG-focused supplier development, cross-sector sustainability collaborations.',
            'priority'     => 'low',
            'financing_link' => 'Required for UN Global Compact Communication on Progress (COP)',
        ],
    ],
];
