<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CommissionLog;
use App\Models\Property;
use App\Models\RevenueEntry;
use Illuminate\Http\Request;

class RevenueController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = app('tenant_id');
        $period   = $request->get('period', now()->format('Y-m'));

        $properties = Property::where('tenant_id', $tenantId)->get();

        $query = RevenueEntry::where('tenant_id', $tenantId)
            ->where('period', $period)
            ->with('property');

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $entries = $query->orderByDesc('created_at')->paginate(20);

        $summary = [
            'income'  => RevenueEntry::where('tenant_id', $tenantId)->where('period', $period)->where('type', 'income')->sum('amount'),
            'expense' => RevenueEntry::where('tenant_id', $tenantId)->where('period', $period)->where('type', 'expense')->sum('amount'),
        ];
        $summary['profit'] = $summary['income'] - $summary['expense'];

        return view('revenue.index', compact('entries', 'properties', 'period', 'summary'));
    }

    public function create(Request $request)
    {
        $tenantId   = app('tenant_id');
        $properties = Property::where('tenant_id', $tenantId)->where('listing_status', 'active')->get();

        return view('revenue.create', compact('properties'));
    }

    public function store(Request $request)
    {
        $tenantId = app('tenant_id');

        $data = $request->validate([
            'property_id'  => 'required|exists:properties,id',
            'period'       => 'required|date_format:Y-m',
            'type'         => 'required|in:income,expense',
            'category'     => 'required|in:rental,cleaning,utilities,maintenance,platform_fee,commission,insurance,assessment,management_fee,other',
            'amount'       => 'required|numeric|min:0.01',
            'description'  => 'nullable|string|max:255',
            'payment_date' => 'nullable|date',
            'payment_ref'  => 'nullable|string|max:100',
        ]);

        $data['tenant_id'] = $tenantId;

        $entry = RevenueEntry::create($data);

        // Auto-create commission log if this is rental income and property has an agent
        if ($data['type'] === 'income' && $data['category'] === 'rental') {
            $property = Property::find($data['property_id']);
            if ($property->agent_id) {
                $agent = $property->agent;
                $rate  = $agent->commissionRate();
                $platformShare = (float) config('strhub.platform_revenue_share', 0.15);
                $platformGross = $data['amount'] * $platformShare;
                $commission    = $platformGross * $rate;
                $platformNet   = $platformGross - $commission;

                CommissionLog::create([
                    'tenant_id'       => $tenantId,
                    'agent_id'        => $agent->id,
                    'property_id'     => $property->id,
                    'revenue_entry_id'=> $entry->id,
                    'revenue_amount'  => $data['amount'],
                    'commission_rate' => $rate * 100,
                    'commission_amount'=> $commission,
                    'platform_net'    => $platformNet,
                    'status'          => 'pending',
                ]);
            }
        }

        ActivityLog::record('revenue.created', "Revenue entry added: RM {$data['amount']} ({$data['category']})", $entry);

        return redirect()->route('revenue.index', ['period' => $data['period']])
            ->with('success', 'Entry recorded successfully.');
    }

    public function destroy(RevenueEntry $revenueEntry)
    {
        if ($revenueEntry->tenant_id !== app('tenant_id')) {
            abort(403);
        }

        $revenueEntry->delete();

        return back()->with('success', 'Entry removed.');
    }

    public function report(Request $request)
    {
        $tenantId = app('tenant_id');
        $year     = $request->get('year', now()->year);

        $properties = Property::where('tenant_id', $tenantId)->get();

        $report = [];
        for ($m = 1; $m <= 12; $m++) {
            $period = sprintf('%d-%02d', $year, $m);

            $income  = (float) RevenueEntry::where('tenant_id', $tenantId)->where('period', $period)->where('type', 'income')->sum('amount');
            $expense = (float) RevenueEntry::where('tenant_id', $tenantId)->where('period', $period)->where('type', 'expense')->sum('amount');

            $report[$period] = [
                'month'   => date('M', mktime(0, 0, 0, $m, 1)),
                'income'  => $income,
                'expense' => $expense,
                'profit'  => $income - $expense,
            ];
        }

        $annualIncome  = collect($report)->sum('income');
        $annualExpense = collect($report)->sum('expense');
        $annualProfit  = $annualIncome - $annualExpense;

        return view('revenue.report', compact('report', 'year', 'annualIncome', 'annualExpense', 'annualProfit', 'properties'));
    }
}
