<?php

namespace App\Services;

use App\Models\Investment;
use App\Models\Property;
use App\Models\RevenueEntry;

class ROIEngine
{
    /**
     * Calculate full ROI report for a property.
     */
    public function calculate(Property $property, int $months = 12): array
    {
        $investment = $property->investment;

        if (!$investment) {
            return $this->emptyResult('No investment data recorded for this property.');
        }

        $totalInvestment = $investment->totalInvestment();

        if ($totalInvestment <= 0) {
            return $this->emptyResult('Total investment is zero.');
        }

        // Gather revenue/expense over the period
        $periods = $this->lastNPeriods($months);

        $totalIncome   = 0;
        $totalExpenses = 0;
        $monthly       = [];

        foreach ($periods as $period) {
            $income = (float) RevenueEntry::where('property_id', $property->id)
                ->where('period', $period)
                ->where('type', 'income')
                ->sum('amount');

            $expense = (float) RevenueEntry::where('property_id', $property->id)
                ->where('period', $period)
                ->where('type', 'expense')
                ->sum('amount');

            $totalIncome   += $income;
            $totalExpenses += $expense;

            $monthly[$period] = [
                'income'   => $income,
                'expenses' => $expense,
                'profit'   => $income - $expense,
            ];
        }

        $totalProfit     = $totalIncome - $totalExpenses;
        $avgMonthlyProfit = $months > 0 ? $totalProfit / $months : 0;
        $annualProfit     = $avgMonthlyProfit * 12;

        $monthlyROI = $totalInvestment > 0 ? ($avgMonthlyProfit / $totalInvestment) * 100 : 0;
        $annualROI  = $totalInvestment > 0 ? ($annualProfit / $totalInvestment) * 100 : 0;
        $payback    = $annualProfit > 0 ? $totalInvestment / $annualProfit : null;

        return [
            'total_investment'  => round($totalInvestment, 2),
            'total_income'      => round($totalIncome, 2),
            'total_expenses'    => round($totalExpenses, 2),
            'total_profit'      => round($totalProfit, 2),
            'avg_monthly_profit'=> round($avgMonthlyProfit, 2),
            'annual_profit'     => round($annualProfit, 2),
            'monthly_roi_pct'   => round($monthlyROI, 2),
            'annual_roi_pct'    => round($annualROI, 2),
            'payback_years'     => $payback !== null ? round($payback, 1) : null,
            'payback_months'    => $payback !== null ? round($payback * 12, 0) : null,
            'monthly_breakdown' => $monthly,
            'period_months'     => $months,
            'rating'            => $this->rateROI($annualROI),
        ];
    }

    public function quickEstimate(float $investment, float $monthlyProfit): array
    {
        $annualProfit = $monthlyProfit * 12;
        $monthlyROI   = $investment > 0 ? ($monthlyProfit / $investment) * 100 : 0;
        $annualROI    = $investment > 0 ? ($annualProfit / $investment) * 100 : 0;
        $payback      = $annualProfit > 0 ? $investment / $annualProfit : null;

        return [
            'monthly_roi_pct' => round($monthlyROI, 2),
            'annual_roi_pct'  => round($annualROI, 2),
            'payback_years'   => $payback !== null ? round($payback, 1) : null,
            'rating'          => $this->rateROI($annualROI),
        ];
    }

    private function rateROI(float $annualROIPct): string
    {
        return match (true) {
            $annualROIPct >= 15 => 'Excellent',
            $annualROIPct >= 10 => 'Good',
            $annualROIPct >= 6  => 'Moderate',
            $annualROIPct >= 3  => 'Low',
            default             => 'Poor',
        };
    }

    private function lastNPeriods(int $months): array
    {
        $periods = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $periods[] = now()->subMonths($i)->format('Y-m');
        }
        return $periods;
    }

    private function emptyResult(string $reason): array
    {
        return [
            'total_investment'  => 0,
            'total_income'      => 0,
            'total_expenses'    => 0,
            'total_profit'      => 0,
            'avg_monthly_profit'=> 0,
            'annual_profit'     => 0,
            'monthly_roi_pct'   => 0,
            'annual_roi_pct'    => 0,
            'payback_years'     => null,
            'payback_months'    => null,
            'monthly_breakdown' => [],
            'period_months'     => 0,
            'rating'            => 'N/A',
            'error'             => $reason,
        ];
    }
}
