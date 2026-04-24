<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\Property;
use App\Services\ROIEngine;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    public function __construct(private ROIEngine $roi) {}

    public function show(Property $property)
    {
        $this->authorizeProperty($property);

        $investment = $property->investment ?? new Investment(['property_id' => $property->id]);
        $roi        = $property->investment ? $this->roi->calculate($property, 12) : null;

        return view('investments.show', compact('property', 'investment', 'roi'));
    }

    public function store(Request $request, Property $property)
    {
        $this->authorizeProperty($property);

        $data = $request->validate([
            'purchase_price'   => 'required|numeric|min:0',
            'renovation_cost'  => 'nullable|numeric|min:0',
            'setup_cost'       => 'nullable|numeric|min:0',
            'furnishing_cost'  => 'nullable|numeric|min:0',
            'deposit_paid'     => 'nullable|numeric|min:0',
            'legal_fees'       => 'nullable|numeric|min:0',
            'stamp_duty'       => 'nullable|numeric|min:0',
            'other_costs'      => 'nullable|numeric|min:0',
            'investment_date'  => 'nullable|date',
            'notes'            => 'nullable|string',
        ]);

        $data['tenant_id']   = app('tenant_id');
        $data['property_id'] = $property->id;

        Investment::updateOrCreate(
            ['property_id' => $property->id],
            $data
        );

        return redirect()->route('investments.show', $property)
            ->with('success', 'Investment data saved.');
    }

    public function roiCalculator(Request $request)
    {
        $tenantId = app('tenant_id');

        $properties = Property::where('tenant_id', $tenantId)->get();

        $result = null;
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'investment' => 'required|numeric|min:1',
                'monthly_profit' => 'required|numeric',
            ]);
            $result = $this->roi->quickEstimate($data['investment'], $data['monthly_profit']);
        }

        return view('investments.calculator', compact('properties', 'result'));
    }

    private function authorizeProperty(Property $property): void
    {
        if ($property->tenant_id !== app('tenant_id')) {
            abort(403);
        }
    }
}
