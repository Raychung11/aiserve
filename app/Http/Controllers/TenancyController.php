<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Property;
use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Http\Request;

class TenancyController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = app('tenant_id');

        $query = Tenancy::where('tenant_id', $tenantId)->with(['property', 'agent']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        $tenancies = $query->orderByDesc('start_date')->paginate(15);

        $expiringCount = Tenancy::where('tenant_id', $tenantId)->expiringSoon()->count();

        $properties = Property::where('tenant_id', $tenantId)->get();

        return view('tenancies.index', compact('tenancies', 'expiringCount', 'properties'));
    }

    public function create(Request $request)
    {
        $tenantId   = app('tenant_id');
        $properties = Property::where('tenant_id', $tenantId)->get();
        $agents     = User::where('tenant_id', $tenantId)->where('role', 'agent')->get();
        $propertyId = $request->get('property_id');

        return view('tenancies.create', compact('properties', 'agents', 'propertyId'));
    }

    public function store(Request $request)
    {
        $tenantId = app('tenant_id');

        $data = $request->validate([
            'property_id'    => 'required|exists:properties,id',
            'tenant_name'    => 'required|string|max:255',
            'tenant_phone'   => 'nullable|string|max:20',
            'tenant_email'   => 'nullable|email',
            'tenant_ic'      => 'nullable|string|max:20',
            'tenant_company' => 'nullable|string|max:255',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after:start_date',
            'monthly_rent'   => 'required|numeric|min:0',
            'deposit'        => 'nullable|numeric|min:0',
            'deposit_paid'   => 'boolean',
            'type'           => 'required|in:STR,MID_TERM,SUBLET,CORPORATE',
            'status'         => 'required|in:active,expired,terminated,pending',
            'agent_id'       => 'nullable|exists:users,id',
            'notes'          => 'nullable|string',
        ]);

        $data['tenant_id']    = $tenantId;
        $data['deposit_paid'] = $request->boolean('deposit_paid');

        $tenancy = Tenancy::create($data);

        ActivityLog::record('tenancy.created', "Tenancy created for {$tenancy->tenant_name}", $tenancy);

        return redirect()->route('tenancies.show', $tenancy)
            ->with('success', 'Tenancy record created.');
    }

    public function show(Tenancy $tenancy)
    {
        $this->authorize($tenancy);

        $tenancy->load(['property', 'agent']);

        return view('tenancies.show', compact('tenancy'));
    }

    public function edit(Tenancy $tenancy)
    {
        $this->authorize($tenancy);

        $tenantId   = app('tenant_id');
        $properties = Property::where('tenant_id', $tenantId)->get();
        $agents     = User::where('tenant_id', $tenantId)->where('role', 'agent')->get();

        return view('tenancies.edit', compact('tenancy', 'properties', 'agents'));
    }

    public function update(Request $request, Tenancy $tenancy)
    {
        $this->authorize($tenancy);

        $data = $request->validate([
            'tenant_name'    => 'required|string|max:255',
            'tenant_phone'   => 'nullable|string|max:20',
            'tenant_email'   => 'nullable|email',
            'tenant_ic'      => 'nullable|string|max:20',
            'tenant_company' => 'nullable|string|max:255',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after:start_date',
            'monthly_rent'   => 'required|numeric|min:0',
            'deposit'        => 'nullable|numeric|min:0',
            'deposit_paid'   => 'boolean',
            'type'           => 'required|in:STR,MID_TERM,SUBLET,CORPORATE',
            'status'         => 'required|in:active,expired,terminated,pending',
            'agent_id'       => 'nullable|exists:users,id',
            'notes'          => 'nullable|string',
        ]);

        $data['deposit_paid'] = $request->boolean('deposit_paid');

        $tenancy->update($data);

        ActivityLog::record('tenancy.updated', "Tenancy updated for {$tenancy->tenant_name}", $tenancy);

        return redirect()->route('tenancies.show', $tenancy)
            ->with('success', 'Tenancy updated.');
    }

    public function destroy(Tenancy $tenancy)
    {
        $this->authorize($tenancy);
        $tenancy->delete();
        return redirect()->route('tenancies.index')->with('success', 'Tenancy removed.');
    }

    private function authorize(Tenancy $tenancy): void
    {
        if ($tenancy->tenant_id !== app('tenant_id')) {
            abort(403);
        }
    }
}
