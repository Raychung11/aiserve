<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Property;
use App\Models\User;
use App\Services\ComplianceEngine;
use App\Services\StrategyEngine;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function __construct(
        private ComplianceEngine $compliance,
        private StrategyEngine $strategy,
    ) {}

    public function index(Request $request)
    {
        $tenantId = app('tenant_id');

        $query = Property::where('tenant_id', $tenantId)->with(['agent', 'activeTenancy']);

        if ($request->filled('status')) {
            $query->where('listing_status', $request->status);
        }

        if ($request->filled('compliance')) {
            $query->where('compliance_status', $request->compliance);
        }

        if ($request->filled('strategy')) {
            $query->where('strategy_mode', $request->strategy);
        }

        $properties = $query->latest()->paginate(15);

        return view('properties.index', compact('properties'));
    }

    public function create()
    {
        $tenant = app('tenant');

        if (!$tenant->canAddProperty()) {
            return redirect()->route('properties.index')
                ->with('error', "You've reached the property limit for your plan ({$tenant->max_properties}). Upgrade to add more.");
        }

        $agents = User::where('tenant_id', $tenant->id)
            ->where('role', 'agent')
            ->where('is_active', true)
            ->get();

        return view('properties.create', compact('agents'));
    }

    public function store(Request $request)
    {
        $tenantId = app('tenant_id');
        $tenant   = app('tenant');

        if (!$tenant->canAddProperty()) {
            return back()->with('error', 'Property limit reached. Upgrade your plan.');
        }

        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'address'          => 'required|string',
            'city'             => 'required|string|max:100',
            'state'            => 'required|string|max:100',
            'postcode'         => 'required|string|max:10',
            'property_type'    => 'required|in:condo,serviced_apartment,landed,commercial,soho,sofo',
            'strata_building'  => 'nullable|string|max:255',
            'is_strata'        => 'boolean',
            'bedrooms'         => 'required|integer|min:0',
            'bathrooms'        => 'required|integer|min:0',
            'area_sqft'        => 'nullable|numeric|min:0',
            'strategy_mode'    => 'required|in:STR,MID_TERM,SUBLET,CORPORATE',
            'agent_id'         => 'nullable|exists:users,id',
            'owner_name'       => 'nullable|string|max:255',
            'owner_phone'      => 'nullable|string|max:20',
            'owner_email'      => 'nullable|email',
            'monthly_target'   => 'nullable|numeric|min:0',
            'airbnb_url'       => 'nullable|url',
            'booking_url'      => 'nullable|url',
            'notes'            => 'nullable|string',
        ]);

        $data['tenant_id']  = $tenantId;
        $data['is_strata']  = $request->boolean('is_strata');

        $property = Property::create($data);

        // Auto-classify compliance
        $this->compliance->classify($property);

        ActivityLog::record('property.created', "Property created: {$property->name}", $property);

        return redirect()->route('properties.show', $property)
            ->with('success', 'Property added successfully.');
    }

    public function show(Property $property)
    {
        $this->authorizeProperty($property);

        $property->load(['agent', 'investment', 'activeTenancy', 'tenancies' => fn($q) => $q->latest()->limit(5)]);

        $complianceResult = $this->compliance->evaluate($property);
        $strategyResult   = $this->strategy->recommend($property);

        $period = now()->format('Y-m');
        $currentRevenue  = $property->currentMonthRevenue($period);
        $currentExpenses = $property->currentMonthExpenses($period);

        return view('properties.show', compact(
            'property',
            'complianceResult',
            'strategyResult',
            'currentRevenue',
            'currentExpenses',
            'period',
        ));
    }

    public function edit(Property $property)
    {
        $this->authorizeProperty($property);

        $agents = User::where('tenant_id', app('tenant_id'))
            ->where('role', 'agent')
            ->where('is_active', true)
            ->get();

        return view('properties.edit', compact('property', 'agents'));
    }

    public function update(Request $request, Property $property)
    {
        $this->authorizeProperty($property);

        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'address'          => 'required|string',
            'city'             => 'required|string|max:100',
            'state'            => 'required|string|max:100',
            'postcode'         => 'required|string|max:10',
            'property_type'    => 'required|in:condo,serviced_apartment,landed,commercial,soho,sofo',
            'strata_building'  => 'nullable|string|max:255',
            'is_strata'        => 'boolean',
            'bedrooms'         => 'required|integer|min:0',
            'bathrooms'        => 'required|integer|min:0',
            'area_sqft'        => 'nullable|numeric|min:0',
            'strategy_mode'    => 'required|in:STR,MID_TERM,SUBLET,CORPORATE',
            'listing_status'   => 'required|in:active,inactive,pending,maintenance',
            'agent_id'         => 'nullable|exists:users,id',
            'owner_name'       => 'nullable|string|max:255',
            'owner_phone'      => 'nullable|string|max:20',
            'owner_email'      => 'nullable|email',
            'monthly_target'   => 'nullable|numeric|min:0',
            'airbnb_url'       => 'nullable|url',
            'booking_url'      => 'nullable|url',
            'notes'            => 'nullable|string',
        ]);

        $data['is_strata'] = $request->boolean('is_strata');

        $property->update($data);
        $this->compliance->classify($property);

        ActivityLog::record('property.updated', "Property updated: {$property->name}", $property);

        return redirect()->route('properties.show', $property)
            ->with('success', 'Property updated successfully.');
    }

    public function destroy(Property $property)
    {
        $this->authorizeProperty($property);

        $property->delete();

        ActivityLog::record('property.deleted', "Property deleted: {$property->name}");

        return redirect()->route('properties.index')
            ->with('success', 'Property removed.');
    }

    public function applyStrategy(Property $property)
    {
        $this->authorizeProperty($property);

        $this->strategy->applyRecommendation($property);

        return back()->with('success', 'Strategy recommendation applied.');
    }

    private function authorizeProperty(Property $property): void
    {
        if ($property->tenant_id !== app('tenant_id')) {
            abort(403);
        }
    }
}
