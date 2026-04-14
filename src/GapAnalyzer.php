<?php
/**
 * Gap Analyzer
 * Identifies ESG disclosure gaps, prioritizes them, and generates recommendations
 */
class GapAnalyzer {

    /**
     * Run full gap analysis for a company
     */
    public static function analyze(int $companyId, string $framework, string $period = '2024'): array {
        $indicators = Company::getFrameworkIndicators($framework);
        $savedData  = ESGDataManager::getAll($companyId, $period);
        $company    = Database::fetchOne('SELECT * FROM companies WHERE id = ?', [$companyId]);

        $gaps       = [];
        $completed  = [];
        $byCategory = ['ENVIRONMENT' => [], 'SOCIAL' => [], 'GOVERNANCE' => []];

        foreach ($indicators as $ind) {
            $id   = $ind['indicator_id'];
            $done = isset($savedData[$id]) && $savedData[$id]['value'] !== '' && $savedData[$id]['value'] !== null;

            if ($done) {
                $completed[] = $id;
            } else {
                $gap = [
                    'indicator'      => $ind,
                    'priority'       => self::calcPriority($ind, $company),
                    'recommendation' => self::getRecommendation($ind, $company),
                    'impact'         => self::getImpact($ind),
                    'effort'         => self::getEffort($ind),
                    'quick_win'      => self::isQuickWin($ind),
                ];
                $gaps[] = $gap;
                $byCategory[$ind['category']][] = $gap;
            }
        }

        // Sort gaps: critical first, then required, then by category
        usort($gaps, function($a, $b) {
            $priorityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            $pa = $priorityOrder[$a['priority']] ?? 4;
            $pb = $priorityOrder[$b['priority']] ?? 4;
            if ($pa !== $pb) return $pa - $pb;
            // Required before recommended
            $ra = $a['indicator']['required'] ? 0 : 1;
            $rb = $b['indicator']['required'] ? 0 : 1;
            return $ra - $rb;
        });

        $total     = count($indicators);
        $doneCount = count($completed);
        $score     = $total > 0 ? round(($doneCount / $total) * 100, 1) : 0;

        // Category scores
        $catScores = [];
        foreach ($byCategory as $cat => $catGaps) {
            $catTotal = count(array_filter($indicators, fn($i) => $i['category'] === $cat));
            $catDone  = $catTotal - count($catGaps);
            $catScores[$cat] = $catTotal > 0 ? round(($catDone / $catTotal) * 100, 1) : 0;
        }

        // Top 5 quick wins (low effort + high impact)
        $quickWins = array_slice(array_filter($gaps, fn($g) => $g['quick_win']), 0, 5);

        // Financing opportunities based on gaps
        $financingOps = self::getFinancingOpportunities($gaps, $company);

        $result = [
            'framework'          => $framework,
            'period'             => $period,
            'total_indicators'   => $total,
            'completed'          => $doneCount,
            'gaps_count'         => count($gaps),
            'score'              => $score,
            'env_score'          => $catScores['ENVIRONMENT'] ?? 0,
            'social_score'       => $catScores['SOCIAL'] ?? 0,
            'gov_score'          => $catScores['GOVERNANCE'] ?? 0,
            'gaps'               => $gaps,
            'by_category'        => $byCategory,
            'quick_wins'         => array_values($quickWins),
            'financing_ops'      => $financingOps,
            'generated_at'       => date('Y-m-d H:i:s'),
        ];

        // Cache result
        self::saveAnalysis($companyId, $framework, $period, $result);

        return $result;
    }

    /**
     * Calculate gap priority based on indicator properties and company profile
     */
    private static function calcPriority(array $ind, array $company): string {
        // Already critical indicators
        if ($ind['priority'] === 'critical') return 'critical';

        // Required indicators for listed/pre-IPO companies
        if ($ind['required'] && ($company['is_pre_ipo'] ?? false)) return 'critical';

        // Required indicators for large companies
        if ($ind['required'] && $company['revenue_tier'] === 'above_50M') return 'high';

        // Return indicator's own priority
        return $ind['priority'] ?? 'medium';
    }

    /**
     * Generate specific recommendation for a gap
     */
    private static function getRecommendation(array $ind, array $company): string {
        $recommendations = [
            'SEDG-E01' => 'Collect 12 months of TNB electricity bills + fuel purchase receipts. Convert kWh to MWh (÷ 1000).',
            'SEDG-E04' => 'Use diesel/petrol consumption × IPCC emission factor (diesel: 2.67 kgCO2/litre, petrol: 2.31 kgCO2/litre).',
            'SEDG-E05' => 'Multiply total kWh (from TNB bills) × 0.694 tCO2e/MWh (Malaysia grid factor 2023).',
            'SEDG-E08' => 'Pull 12 months of water utility bills (SYABAS/SAJ). Convert litres to m³ (÷ 1,000).',
            'SEDG-E10' => 'Request weighing records from licensed waste contractor. If unavailable, estimate from bin size × collection frequency.',
            'SEDG-E11' => 'Collect DOE scheduled waste manifests (Form A). Required by DOE — cross-reference with contractor records.',
            'SEDG-S01' => 'Pull headcount data from payroll system or HR records. Separate permanent vs contract vs part-time.',
            'SEDG-S07' => 'Cross-reference DOSH (JKKP) incident reports + accident book (Log Buku Kemalangan). Zero is a valid answer.',
            'SEDG-S08' => 'Formula: (LTI incidents × 1,000,000) ÷ total hours worked. Get hours from payroll system.',
            'SEDG-S10' => 'Export training records from HRD Corp portal or internal LMS. Total hours ÷ average headcount.',
            'SEDG-G01' => 'From latest Form 49 / Form 58 filed with SSM. Count all executive + non-executive directors.',
            'SEDG-G04' => 'Draft a one-page ESG Committee Terms of Reference (TOR). Board approval needed — typically 2-4 weeks.',
            'SEDG-G05' => 'Download MACC Section 17A Anti-Bribery Policy template from www.sprm.gov.my. Customize and get board approval.',
            'SEDG-G06' => 'Set up free whistleblower email (e.g., ethics@yourcompany.com) + document in HR handbook. Takes 1-2 days.',
            'SEDG-G08' => 'Use AiServe ESG OS report generator to produce your first sustainability report in under 2 hours.',
            'GRI-305-1'=> 'Calculate from fuel consumption records. Diesel: 2.67 kgCO2/L, Petrol: 2.31 kgCO2/L, Natural gas: use IPCC factors.',
            'GRI-305-2'=> 'Total kWh from TNB bills × 0.694 tCO2e/MWh. This is your Scope 2 figure.',
            'GRI-403-2'=> 'Document your HIRARC process (Hazard Identification, Risk Assessment, Risk Control). Template available from DOSH.',
            'GRI-408-1'=> 'Complete a simple declaration: confirm no employees under 18 in hazardous roles. Check employment contracts.',
            'GRI-409-1'=> 'Confirm no passport confiscation, wage deductions for recruitment, or restricted movement. Document in policy.',
        ];

        if (isset($recommendations[$ind['indicator_id']])) {
            return $recommendations[$ind['indicator_id']];
        }

        if ($ind['data_type'] === 'boolean') {
            return "Draft and formally approve a policy document. Engage HR/Legal. Typically takes 1-3 weeks.";
        }
        if ($ind['data_type'] === 'number' || $ind['data_type'] === 'percentage') {
            return "Collect from " . strtolower($ind['category']) . " records in HR, Finance, or Operations systems.";
        }
        return "Document and disclose in your next ESG report. Describe your current approach and any improvement plans.";
    }

    /**
     * Get business impact of filling this gap
     */
    private static function getImpact(array $ind): string {
        if ($ind['financing_link']) return $ind['financing_link'];
        if ($ind['priority'] === 'critical') return 'Regulatory/legal compliance risk if not addressed';
        if ($ind['required'])               return 'Required disclosure — Bursa/GRI compliance gap';
        return 'Improves ESG score and investor confidence';
    }

    /**
     * Estimate effort to fill the gap
     */
    private static function getEffort(array $ind): string {
        if ($ind['data_type'] === 'boolean') return 'Low (1-3 days)';
        if ($ind['data_type'] === 'text')    return 'Low (1-2 hours)';
        if (in_array($ind['indicator_id'], ['SEDG-E04','SEDG-E05','GRI-305-1','GRI-305-2','GRI-302-1'])) {
            return 'Medium (1-3 days) — need utility bills';
        }
        if ($ind['priority'] === 'critical') return 'Medium (1-2 weeks)';
        return 'Low-Medium (2-5 days)';
    }

    /**
     * Identify quick wins (boolean fields and simple data pulls)
     */
    private static function isQuickWin(array $ind): bool {
        if ($ind['data_type'] === 'boolean') return true;
        if ($ind['data_type'] === 'text')    return true;
        if (in_array($ind['indicator_id'], ['SEDG-S01','SEDG-S02','SEDG-S03','SEDG-G01','SEDG-G02','SEDG-G03'])) {
            return true; // HR/payroll data easily accessible
        }
        return false;
    }

    /**
     * Identify financing opportunities based on current gaps
     */
    private static function getFinancingOpportunities(array $gaps, array $company): array {
        $gapIds = array_column(array_column($gaps, 'indicator'), 'indicator_id');
        $ops    = [];

        $criticalGaps = ['SEDG-E04', 'SEDG-E05', 'GRI-305-1', 'GRI-305-2'];
        $missingEmissions = !empty(array_intersect($gapIds, $criticalGaps));

        if (!$missingEmissions) {
            $ops[] = [
                'name'        => 'BNM Low Carbon Transition Facility',
                'benefit'     => 'Up to RM10M at preferential rate',
                'status'      => 'eligible',
                'requirement' => 'Scope 1 & 2 data available',
            ];
        } else {
            $ops[] = [
                'name'        => 'BNM Low Carbon Transition Facility',
                'benefit'     => 'Up to RM10M at preferential rate',
                'status'      => 'fix_required',
                'requirement' => 'Complete Scope 1 & 2 emissions data first',
            ];
        }

        $ops[] = [
            'name'        => 'CGC Green Lane Financing',
            'benefit'     => 'Faster loan approval + reduced guarantee fee',
            'status'      => count($gaps) < 15 ? 'eligible' : 'fix_required',
            'requirement' => count($gaps) < 15 ? 'ESG baseline met' : 'Close at least ' . (count($gaps) - 14) . ' more gaps',
        ];

        $ops[] = [
            'name'        => 'SME Corp Sustainability Grant',
            'benefit'     => 'Up to RM500K co-funding for SMEs <RM50M revenue',
            'status'      => in_array($company['revenue_tier'] ?? '', ['below_10M', '10M_to_50M']) ? 'eligible' : 'check',
            'requirement' => 'Revenue below RM50M + ESG baseline report',
        ];

        $ops[] = [
            'name'        => 'Sustainability-Linked Loan (SLL)',
            'benefit'     => '0.25–0.5% rate reduction tied to ESG KPIs',
            'status'      => in_array('SEDG-G09', $gapIds) ? 'fix_required' : 'eligible',
            'requirement' => 'Formal ESG targets must be set (G-09)',
        ];

        return $ops;
    }

    /**
     * Save gap analysis result to database
     */
    private static function saveAnalysis(int $companyId, string $framework, string $period, array $result): void {
        // Delete old analysis for same company/framework/period
        Database::query(
            'DELETE FROM gap_analyses WHERE company_id = ? AND framework = ? AND period = ?',
            [$companyId, $framework, $period]
        );

        Database::insert('gap_analyses', [
            'company_id'           => $companyId,
            'framework'            => $framework,
            'period'               => $period,
            'total_indicators'     => $result['total_indicators'],
            'completed_indicators' => $result['completed'],
            'score'                => $result['score'],
            'env_score'            => $result['env_score'],
            'social_score'         => $result['social_score'],
            'gov_score'            => $result['gov_score'],
            'analysis_json'        => json_encode($result),
        ]);
    }

    /**
     * Load cached analysis
     */
    public static function loadCached(int $companyId, string $framework, string $period): ?array {
        $row = Database::fetchOne(
            'SELECT * FROM gap_analyses WHERE company_id = ? AND framework = ? AND period = ? ORDER BY generated_at DESC LIMIT 1',
            [$companyId, $framework, $period]
        );
        if (!$row) return null;
        $data = json_decode($row['analysis_json'], true);
        return $data;
    }
}
