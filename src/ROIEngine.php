<?php
class ROIEngine {
    public static function calculate(int $propertyId, int $tenantId, int $months = 12): array {
        $inv = Database::fetchOne(
            'SELECT * FROM investments WHERE property_id = ? AND tenant_id = ?',
            [$propertyId, $tenantId]
        );
        if (!$inv) return self::empty('No investment data recorded.');

        $total = self::totalInvestment($inv);
        if ($total <= 0) return self::empty('Total investment is zero.');

        $periods = self::lastNPeriods($months);
        $income = $expense = 0;
        $monthly = [];

        foreach ($periods as $period) {
            $inc = (float) (Database::fetchOne(
                'SELECT SUM(amount) AS s FROM revenue_entries WHERE tenant_id=? AND property_id=? AND period=? AND type="income"',
                [$tenantId, $propertyId, $period]
            )['s'] ?? 0);
            $exp = (float) (Database::fetchOne(
                'SELECT SUM(amount) AS s FROM revenue_entries WHERE tenant_id=? AND property_id=? AND period=? AND type="expense"',
                [$tenantId, $propertyId, $period]
            )['s'] ?? 0);
            $income  += $inc;
            $expense += $exp;
            $monthly[$period] = ['income' => $inc, 'expense' => $exp, 'profit' => $inc - $exp];
        }

        $profit   = $income - $expense;
        $avgMonth = $months > 0 ? $profit / $months : 0;
        $annual   = $avgMonth * 12;

        return [
            'total_investment'   => round($total, 2),
            'total_income'       => round($income, 2),
            'total_expenses'     => round($expense, 2),
            'total_profit'       => round($profit, 2),
            'avg_monthly_profit' => round($avgMonth, 2),
            'annual_profit'      => round($annual, 2),
            'monthly_roi_pct'    => $total > 0 ? round(($avgMonth / $total) * 100, 2) : 0,
            'annual_roi_pct'     => $total > 0 ? round(($annual / $total) * 100, 2) : 0,
            'payback_years'      => $annual > 0 ? round($total / $annual, 1) : null,
            'monthly_breakdown'  => $monthly,
            'rating'             => self::rate($total > 0 ? round(($annual / $total) * 100, 2) : 0),
            'error'              => null,
        ];
    }

    public static function quickEstimate(float $investment, float $monthlyProfit): array {
        $annual     = $monthlyProfit * 12;
        $monthlyRoi = $investment > 0 ? round(($monthlyProfit / $investment) * 100, 2) : 0;
        $annualRoi  = $investment > 0 ? round(($annual / $investment) * 100, 2) : 0;
        return [
            'monthly_roi_pct' => $monthlyRoi,
            'annual_roi_pct'  => $annualRoi,
            'payback_years'   => $annual > 0 ? round($investment / $annual, 1) : null,
            'rating'          => self::rate($annualRoi),
        ];
    }

    public static function totalInvestment(array $inv): float {
        return array_sum(array_map(
            fn($k) => (float)($inv[$k] ?? 0),
            ['purchase_price','renovation_cost','setup_cost','furnishing_cost','deposit_paid','legal_fees','stamp_duty','other_costs']
        ));
    }

    public static function rate(float $pct): string {
        return match (true) {
            $pct >= 15 => 'Excellent',
            $pct >= 10 => 'Good',
            $pct >= 6  => 'Moderate',
            $pct >= 3  => 'Low',
            default    => 'Poor',
        };
    }

    private static function lastNPeriods(int $n): array {
        $p = [];
        for ($i = $n - 1; $i >= 0; $i--) {
            $p[] = date('Y-m', strtotime("-$i months"));
        }
        return $p;
    }

    private static function empty(string $reason): array {
        return ['total_investment'=>0,'total_income'=>0,'total_expenses'=>0,'total_profit'=>0,
                'avg_monthly_profit'=>0,'annual_profit'=>0,'monthly_roi_pct'=>0,'annual_roi_pct'=>0,
                'payback_years'=>null,'monthly_breakdown'=>[],'rating'=>'N/A','error'=>$reason];
    }
}
