<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\RevenueEntry;
use App\Models\Tenancy;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = app('tenant_id');
        $period   = now()->format('Y-m');

        $properties = Property::where('tenant_id', $tenantId)->get();

        $totalRevenue = RevenueEntry::where('tenant_id', $tenantId)
            ->where('period', $period)
            ->where('type', 'income')
            ->sum('amount');

        $totalExpenses = RevenueEntry::where('tenant_id', $tenantId)
            ->where('period', $period)
            ->where('type', 'expense')
            ->sum('amount');

        $netProfit = $totalRevenue - $totalExpenses;

        $activeProperties = $properties->where('listing_status', 'active')->count();

        $expiringLeases = Tenancy::where('tenant_id', $tenantId)
            ->expiringSoon(30)
            ->with('property')
            ->get();

        $complianceSummary = [
            'green' => $properties->where('compliance_status', 'green')->count(),
            'amber' => $properties->where('compliance_status', 'amber')->count(),
            'red'   => $properties->where('compliance_status', 'red')->count(),
        ];

        $recentEntries = RevenueEntry::where('tenant_id', $tenantId)
            ->with('property')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $monthlyRevenue = $this->last6MonthsRevenue($tenantId);

        return view('dashboard', compact(
            'properties',
            'totalRevenue',
            'totalExpenses',
            'netProfit',
            'activeProperties',
            'expiringLeases',
            'complianceSummary',
            'recentEntries',
            'monthlyRevenue',
            'period',
        ));
    }

    private function last6MonthsRevenue(int $tenantId): array
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $period = now()->subMonths($i)->format('Y-m');
            $label  = now()->subMonths($i)->format('M Y');

            $income  = (float) RevenueEntry::where('tenant_id', $tenantId)->where('period', $period)->where('type', 'income')->sum('amount');
            $expense = (float) RevenueEntry::where('tenant_id', $tenantId)->where('period', $period)->where('type', 'expense')->sum('amount');

            $data[] = ['period' => $period, 'label' => $label, 'income' => $income, 'expense' => $expense, 'profit' => $income - $expense];
        }
        return $data;
    }
}
