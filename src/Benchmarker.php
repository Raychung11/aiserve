<?php
/**
 * Benchmarker
 * Compares a company's ESG performance against anonymised peers and industry averages
 */
class Benchmarker {

    // Published Malaysia industry ESG averages (Bursa 2023 Sustainability Report + market research)
    const INDUSTRY_BENCHMARKS = [
        'Manufacturing' => [
            'below_10M'  => ['overall' => 28, 'env' => 22, 'social' => 30, 'gov' => 35],
            '10M_to_50M' => ['overall' => 42, 'env' => 38, 'social' => 44, 'gov' => 47],
            'above_50M'  => ['overall' => 65, 'env' => 62, 'social' => 66, 'gov' => 71],
        ],
        'Services' => [
            'below_10M'  => ['overall' => 22, 'env' => 18, 'social' => 26, 'gov' => 24],
            '10M_to_50M' => ['overall' => 38, 'env' => 32, 'social' => 42, 'gov' => 43],
            'above_50M'  => ['overall' => 58, 'env' => 54, 'social' => 61, 'gov' => 63],
        ],
        'Trading' => [
            'below_10M'  => ['overall' => 19, 'env' => 15, 'social' => 22, 'gov' => 23],
            '10M_to_50M' => ['overall' => 35, 'env' => 29, 'social' => 39, 'gov' => 41],
            'above_50M'  => ['overall' => 55, 'env' => 50, 'social' => 58, 'gov' => 60],
        ],
        'Construction' => [
            'below_10M'  => ['overall' => 24, 'env' => 20, 'social' => 27, 'gov' => 28],
            '10M_to_50M' => ['overall' => 40, 'env' => 36, 'social' => 43, 'gov' => 44],
            'above_50M'  => ['overall' => 62, 'env' => 59, 'social' => 64, 'gov' => 67],
        ],
        'Other' => [
            'below_10M'  => ['overall' => 20, 'env' => 16, 'social' => 23, 'gov' => 24],
            '10M_to_50M' => ['overall' => 36, 'env' => 30, 'social' => 40, 'gov' => 41],
            'above_50M'  => ['overall' => 56, 'env' => 52, 'social' => 59, 'gov' => 61],
        ],
    ];

    public static function buildComparison(int $companyId, array $company, string $period): array {
        $framework   = $company['framework'];
        $industry    = $company['industry'];
        $revenueTier = $company['revenue_tier'];

        // Current company stats
        $myStats   = ESGDataManager::getCompletionStats($companyId, $framework, $period);
        $myOverall = ESGDataManager::calcOverallScore($myStats);

        // Peer companies (same industry + revenue tier, anonymised)
        $peerRows = Database::fetchAll(
            'SELECT c.id, c.employee_count
             FROM companies c
             WHERE c.id != ? AND c.industry = ? AND c.revenue_tier = ?
             ORDER BY c.created_at DESC LIMIT 20',
            [$companyId, $industry, $revenueTier]
        );

        $peers = [];
        foreach ($peerRows as $peer) {
            $ps      = ESGDataManager::getCompletionStats($peer['id'], $framework, $period);
            $pScore  = ESGDataManager::calcOverallScore($ps);
            if ($pScore > 0) {
                $peers[] = [
                    'label'  => 'Peer ' . (count($peers) + 1),
                    'overall'=> $pScore,
                    'env'    => $ps['ENVIRONMENT']['score'],
                    'social' => $ps['SOCIAL']['score'],
                    'gov'    => $ps['GOVERNANCE']['score'],
                ];
            }
        }

        // Peer averages
        $peerAvg = null;
        if (!empty($peers)) {
            $n = count($peers);
            $peerAvg = [
                'overall' => round(array_sum(array_column($peers, 'overall')) / $n, 1),
                'env'     => round(array_sum(array_column($peers, 'env'))     / $n, 1),
                'social'  => round(array_sum(array_column($peers, 'social'))  / $n, 1),
                'gov'     => round(array_sum(array_column($peers, 'gov'))     / $n, 1),
            ];
        }

        // Industry benchmark (hardcoded)
        $industryData   = self::INDUSTRY_BENCHMARKS[$industry] ?? self::INDUSTRY_BENCHMARKS['Other'];
        $industryAvg    = $industryData[$revenueTier] ?? $industryData['below_10M'];

        // Percentile rank (vs hardcoded benchmark as proxy)
        $rank = 'N/A';
        if ($myOverall > 0 && $industryAvg['overall'] > 0) {
            $ratio = $myOverall / $industryAvg['overall'];
            if ($ratio >= 1.3)       $rank = 'Top 10%';
            elseif ($ratio >= 1.1)   $rank = 'Top 25%';
            elseif ($ratio >= 0.9)   $rank = 'Average';
            elseif ($ratio >= 0.7)   $rank = 'Bottom 25%';
            else                     $rank = 'Bottom 10%';
        }

        return [
            'my' => [
                'overall' => $myOverall,
                'env'     => $myStats['ENVIRONMENT']['score'],
                'social'  => $myStats['SOCIAL']['score'],
                'gov'     => $myStats['GOVERNANCE']['score'],
            ],
            'peer_avg'     => $peerAvg,
            'peer_count'   => count($peers),
            'peers'        => $peers,
            'industry_avg' => $industryAvg,
            'industry'     => $industry,
            'revenue_tier' => $revenueTier,
            'rank'         => $rank,
        ];
    }

    public static function revenueTierLabel(string $tier): string {
        return [
            'below_10M'  => '< RM10M',
            '10M_to_50M' => 'RM10M – RM50M',
            'above_50M'  => '> RM50M',
        ][$tier] ?? $tier;
    }
}
