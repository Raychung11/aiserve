<?php
/**
 * Benchmarker
 * Compares a company's ESG performance against anonymised peers and published averages.
 * Prefers Bursa Malaysia sector benchmarks when bursa_sector is set on the company.
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

    // Bursa Malaysia sectoral ESG averages
    // Source: Bursa Sustainability Report 2023, FTSE4Good Bursa Malaysia Index sector data, analyst estimates
    const SECTOR_BENCHMARKS = [
        'Consumer Products & Services' => [
            'below_10M'  => ['overall' => 23, 'env' => 19, 'social' => 26, 'gov' => 26],
            '10M_to_50M' => ['overall' => 41, 'env' => 37, 'social' => 45, 'gov' => 44],
            'above_50M'  => ['overall' => 63, 'env' => 60, 'social' => 65, 'gov' => 67],
        ],
        'Construction' => [
            'below_10M'  => ['overall' => 24, 'env' => 20, 'social' => 27, 'gov' => 28],
            '10M_to_50M' => ['overall' => 40, 'env' => 36, 'social' => 43, 'gov' => 44],
            'above_50M'  => ['overall' => 62, 'env' => 59, 'social' => 64, 'gov' => 67],
        ],
        'Energy' => [
            'below_10M'  => ['overall' => 26, 'env' => 30, 'social' => 24, 'gov' => 27],
            '10M_to_50M' => ['overall' => 46, 'env' => 52, 'social' => 42, 'gov' => 47],
            'above_50M'  => ['overall' => 70, 'env' => 78, 'social' => 65, 'gov' => 69],
        ],
        'Financial Services' => [
            'below_10M'  => ['overall' => 30, 'env' => 21, 'social' => 32, 'gov' => 42],
            '10M_to_50M' => ['overall' => 52, 'env' => 40, 'social' => 55, 'gov' => 67],
            'above_50M'  => ['overall' => 74, 'env' => 62, 'social' => 76, 'gov' => 87],
        ],
        'Health Care' => [
            'below_10M'  => ['overall' => 27, 'env' => 20, 'social' => 34, 'gov' => 30],
            '10M_to_50M' => ['overall' => 47, 'env' => 38, 'social' => 57, 'gov' => 50],
            'above_50M'  => ['overall' => 68, 'env' => 57, 'social' => 79, 'gov' => 71],
        ],
        'Industrial Products & Services' => [
            'below_10M'  => ['overall' => 26, 'env' => 22, 'social' => 28, 'gov' => 31],
            '10M_to_50M' => ['overall' => 43, 'env' => 39, 'social' => 45, 'gov' => 48],
            'above_50M'  => ['overall' => 66, 'env' => 63, 'social' => 67, 'gov' => 71],
        ],
        'Plantation' => [
            'below_10M'  => ['overall' => 29, 'env' => 28, 'social' => 30, 'gov' => 31],
            '10M_to_50M' => ['overall' => 50, 'env' => 52, 'social' => 50, 'gov' => 50],
            'above_50M'  => ['overall' => 71, 'env' => 74, 'social' => 70, 'gov' => 69],
        ],
        'Property' => [
            'below_10M'  => ['overall' => 21, 'env' => 17, 'social' => 23, 'gov' => 26],
            '10M_to_50M' => ['overall' => 38, 'env' => 32, 'social' => 41, 'gov' => 46],
            'above_50M'  => ['overall' => 59, 'env' => 53, 'social' => 62, 'gov' => 67],
        ],
        'Real Estate Investment Trusts' => [
            'below_10M'  => ['overall' => 33, 'env' => 27, 'social' => 33, 'gov' => 43],
            '10M_to_50M' => ['overall' => 54, 'env' => 48, 'social' => 54, 'gov' => 65],
            'above_50M'  => ['overall' => 72, 'env' => 67, 'social' => 72, 'gov' => 81],
        ],
        'Technology' => [
            'below_10M'  => ['overall' => 25, 'env' => 18, 'social' => 29, 'gov' => 31],
            '10M_to_50M' => ['overall' => 44, 'env' => 35, 'social' => 50, 'gov' => 52],
            'above_50M'  => ['overall' => 66, 'env' => 56, 'social' => 73, 'gov' => 72],
        ],
        'Telecommunications & Media' => [
            'below_10M'  => ['overall' => 24, 'env' => 15, 'social' => 30, 'gov' => 30],
            '10M_to_50M' => ['overall' => 45, 'env' => 32, 'social' => 54, 'gov' => 55],
            'above_50M'  => ['overall' => 68, 'env' => 54, 'social' => 77, 'gov' => 75],
        ],
        'Transportation & Logistics' => [
            'below_10M'  => ['overall' => 22, 'env' => 18, 'social' => 25, 'gov' => 27],
            '10M_to_50M' => ['overall' => 40, 'env' => 37, 'social' => 43, 'gov' => 44],
            'above_50M'  => ['overall' => 62, 'env' => 60, 'social' => 64, 'gov' => 65],
        ],
        'Utilities' => [
            'below_10M'  => ['overall' => 31, 'env' => 38, 'social' => 28, 'gov' => 30],
            '10M_to_50M' => ['overall' => 54, 'env' => 64, 'social' => 50, 'gov' => 52],
            'above_50M'  => ['overall' => 76, 'env' => 87, 'social' => 70, 'gov' => 73],
        ],
    ];

    public static function buildComparison(int $companyId, array $company, string $period): array {
        $framework   = $company['framework'];
        $industry    = $company['industry'];
        $revenueTier = $company['revenue_tier'];
        $bursaSector = !empty($company['bursa_sector']) ? $company['bursa_sector'] : null;

        // Current company stats
        $myStats   = ESGDataManager::getCompletionStats($companyId, $framework, $period);
        $myOverall = ESGDataManager::calcOverallScore($myStats);

        // Peer matching: prefer bursa_sector when set, fall back to industry
        if ($bursaSector) {
            $peerRows = Database::fetchAll(
                'SELECT c.id FROM companies c
                 WHERE c.id != ? AND c.bursa_sector = ? AND c.revenue_tier = ?
                 ORDER BY c.created_at DESC LIMIT 20',
                [$companyId, $bursaSector, $revenueTier]
            );
            // Widen to same industry if not enough sector peers
            if (count($peerRows) < 3) {
                $peerRows = array_merge($peerRows, Database::fetchAll(
                    'SELECT c.id FROM companies c
                     WHERE c.id != ? AND c.industry = ? AND c.revenue_tier = ?
                       AND (c.bursa_sector IS NULL OR c.bursa_sector != ?)
                     ORDER BY c.created_at DESC LIMIT ?',
                    [$companyId, $industry, $revenueTier, $bursaSector, 20 - count($peerRows)]
                ));
            }
        } else {
            $peerRows = Database::fetchAll(
                'SELECT c.id FROM companies c
                 WHERE c.id != ? AND c.industry = ? AND c.revenue_tier = ?
                 ORDER BY c.created_at DESC LIMIT 20',
                [$companyId, $industry, $revenueTier]
            );
        }

        $peers = [];
        foreach ($peerRows as $peer) {
            $ps     = ESGDataManager::getCompletionStats($peer['id'], $framework, $period);
            $pScore = ESGDataManager::calcOverallScore($ps);
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

        // Benchmark data: sector preferred over industry
        $benchmarkType = 'industry';
        $benchmarkLabel = $industry;
        if ($bursaSector && isset(self::SECTOR_BENCHMARKS[$bursaSector])) {
            $benchmarkData  = self::SECTOR_BENCHMARKS[$bursaSector];
            $benchmarkType  = 'sector';
            $benchmarkLabel = $bursaSector;
        } else {
            $benchmarkData  = self::INDUSTRY_BENCHMARKS[$industry] ?? self::INDUSTRY_BENCHMARKS['Other'];
        }
        $benchmarkAvg = $benchmarkData[$revenueTier] ?? $benchmarkData['below_10M'];

        // Percentile rank
        $rank = 'N/A';
        if ($myOverall > 0 && $benchmarkAvg['overall'] > 0) {
            $ratio = $myOverall / $benchmarkAvg['overall'];
            if      ($ratio >= 1.3) $rank = 'Top 10%';
            elseif  ($ratio >= 1.1) $rank = 'Top 25%';
            elseif  ($ratio >= 0.9) $rank = 'Average';
            elseif  ($ratio >= 0.7) $rank = 'Bottom 25%';
            else                    $rank = 'Bottom 10%';
        }

        return [
            'my' => [
                'overall' => $myOverall,
                'env'     => $myStats['ENVIRONMENT']['score'],
                'social'  => $myStats['SOCIAL']['score'],
                'gov'     => $myStats['GOVERNANCE']['score'],
            ],
            'peer_avg'        => $peerAvg,
            'peer_count'      => count($peers),
            'peers'           => $peers,
            'industry_avg'    => $benchmarkAvg,      // kept as 'industry_avg' for backward compat
            'benchmark_data'  => $benchmarkData,     // all tiers for the reference table
            'benchmark_type'  => $benchmarkType,     // 'sector' | 'industry'
            'benchmark_label' => $benchmarkLabel,    // sector name or industry name
            'industry'        => $industry,
            'bursa_sector'    => $bursaSector,
            'revenue_tier'    => $revenueTier,
            'rank'            => $rank,
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
