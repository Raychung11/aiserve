<?php
class KPITracker {

    /** Save (upsert) a monthly KPI snapshot for the current state of a company. */
    public static function snapshot(int $companyId, string $framework, int $year, int $month): array {
        $stats   = ESGDataManager::getCompletionStats($companyId, $framework, (string)$year);
        $overall = ESGDataManager::calcOverallScore($stats);
        $eScore  = round($stats['ENVIRONMENT']['score'] ?? 0, 2);
        $sScore  = round($stats['SOCIAL']['score']      ?? 0, 2);
        $gScore  = round($stats['GOVERNANCE']['score']  ?? 0, 2);
        $filled  = array_sum(array_column($stats, 'filled'));
        $total   = array_sum(array_column($stats, 'total'));
        $completion = $total > 0 ? round($filled / $total * 100, 2) : 0;

        // Carbon data (latest saved values)
        $c1 = Database::fetchOne(
            "SELECT COALESCE(SUM(CAST(value AS DECIMAL(14,4))), 0) AS v FROM esg_data
             WHERE company_id = ? AND indicator_id IN ('SEDG-E04','GRI-305-1','ISSB-MET-01') AND period = ?",
            [$companyId, (string)$year]
        );
        $c2 = Database::fetchOne(
            "SELECT COALESCE(SUM(CAST(value AS DECIMAL(14,4))), 0) AS v FROM esg_data
             WHERE company_id = ? AND indicator_id IN ('SEDG-E05','GRI-305-2','ISSB-MET-02') AND period = ?",
            [$companyId, (string)$year]
        );
        $c3 = Database::fetchOne(
            "SELECT COALESCE(SUM(CAST(value AS DECIMAL(14,4))), 0) AS v FROM esg_data
             WHERE company_id = ? AND indicator_id IN ('SEDG-E06','GRI-305-3','ISSB-MET-03') AND period = ?",
            [$companyId, (string)$year]
        );

        $row = [
            'company_id'        => $companyId,
            'year'              => $year,
            'month'             => $month,
            'e_score'           => $eScore,
            's_score'           => $sScore,
            'g_score'           => $gScore,
            'overall_score'     => round($overall, 2),
            'carbon_scope1'     => (float)($c1['v'] ?? 0),
            'carbon_scope2'     => (float)($c2['v'] ?? 0),
            'carbon_scope3'     => (float)($c3['v'] ?? 0),
            'data_completion'   => $completion,
            'indicators_filled' => $filled,
            'indicators_total'  => $total,
            'snapshot_at'       => date('Y-m-d H:i:s'),
        ];

        Database::upsert('monthly_kpi_snapshots', $row, ['company_id', 'year', 'month']);
        return $row;
    }

    /** Get last N months of snapshots for a company (ordered oldest → newest). */
    public static function getTrend(int $companyId, int $months = 6): array {
        return self::getTrendOldestFirst($companyId, $months);
    }

    /** Get last N months of snapshots for a company (oldest first). */
    public static function getTrendOldestFirst(int $companyId, int $months = 6): array {
        $rows = Database::fetchAll(
            'SELECT * FROM monthly_kpi_snapshots
             WHERE company_id = ?
             ORDER BY year DESC, month DESC
             LIMIT ' . (int)$months,
            [$companyId]
        );
        return array_reverse($rows);
    }

    /** Get the latest snapshot for a company. */
    public static function getLatest(int $companyId): ?array {
        return Database::fetchOne(
            'SELECT * FROM monthly_kpi_snapshots WHERE company_id = ? ORDER BY year DESC, month DESC LIMIT 1',
            [$companyId]
        );
    }

    /** Calculate month-over-month change for overall score. */
    public static function getMoMChange(int $companyId): ?float {
        $rows = Database::fetchAll(
            'SELECT overall_score FROM monthly_kpi_snapshots WHERE company_id = ? ORDER BY year DESC, month DESC LIMIT 2',
            [$companyId]
        );
        if (count($rows) < 2) return null;
        return round($rows[0]['overall_score'] - $rows[1]['overall_score'], 1);
    }

    /** Build Chart.js-ready labels and datasets from trend rows. */
    public static function chartData(array $rows): array {
        $labels   = [];
        $overall  = [];
        $eScores  = [];
        $sScores  = [];
        $gScores  = [];
        $carbon   = [];
        foreach ($rows as $r) {
            $labels[]  = date('M y', mktime(0, 0, 0, (int)$r['month'], 1, (int)$r['year']));
            $overall[] = (float)$r['overall_score'];
            $eScores[] = (float)$r['e_score'];
            $sScores[] = (float)$r['s_score'];
            $gScores[] = (float)$r['g_score'];
            $carbon[]  = round((float)$r['carbon_scope1'] + (float)$r['carbon_scope2'] + (float)$r['carbon_scope3'], 2);
        }
        return compact('labels', 'overall', 'eScores', 'sScores', 'gScores', 'carbon');
    }
}
