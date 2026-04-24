@extends('layouts.app')

@section('title', $property->name)
@section('page-title', $property->name)
@section('page-subtitle', $property->address . ', ' . $property->city)

@section('content')

<div class="row g-3 mb-4">
    {{-- Compliance Card --}}
    <div class="col-md-4">
        <div class="stat-card h-100">
            <h6 class="fw-semibold mb-3">Compliance Status</h6>
            @php $cs = $complianceResult['status']; @endphp
            <div class="text-center py-2">
                <div style="font-size:2.5rem;">
                    {{ $cs === 'green' ? '🟢' : ($cs === 'amber' ? '🟡' : '🔴') }}
                </div>
                <div class="fw-bold mt-1 compliance-{{ $cs }}">
                    {{ strtoupper($cs) }}
                </div>
                <div class="text-muted mt-1" style="font-size:.8rem;">{{ $complianceResult['recommendation'] }}</div>
            </div>
            @if($complianceResult['flags'])
            <ul class="mt-3 ps-3 mb-0" style="font-size:.78rem;color:#64748b;">
                @foreach($complianceResult['flags'] as $flag)
                <li>{{ $flag }}</li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>

    {{-- Strategy Card --}}
    <div class="col-md-4">
        <div class="stat-card h-100">
            <h6 class="fw-semibold mb-3">Strategy Recommendation</h6>
            <div class="text-center py-2">
                <span class="badge badge-{{ strtolower(str_replace('_','-',$strategyResult['recommended'])) }}" style="font-size:1rem;padding:.5rem 1rem;">
                    {{ $strategyResult['recommended'] }}
                </span>
                <div class="text-muted mt-2" style="font-size:.8rem;">{{ $strategyResult['description'] }}</div>
            </div>
            @if($strategyResult['reasons'])
            <ul class="mt-3 ps-3 mb-3" style="font-size:.78rem;color:#64748b;">
                @foreach($strategyResult['reasons'] as $reason)
                <li>{{ $reason }}</li>
                @endforeach
            </ul>
            @endif
            <form action="{{ route('properties.apply-strategy', $property) }}" method="POST">
                @csrf
                <button class="btn btn-sm btn-outline-primary w-100">Apply Recommendation</button>
            </form>
        </div>
    </div>

    {{-- Revenue Card --}}
    <div class="col-md-4">
        <div class="stat-card h-100">
            <h6 class="fw-semibold mb-3">This Month ({{ $period }})</h6>
            <div class="mb-3">
                <div class="text-muted" style="font-size:.75rem;">Revenue</div>
                <div class="fw-bold text-success" style="font-size:1.4rem;">RM {{ number_format($currentRevenue, 0) }}</div>
            </div>
            <div class="mb-3">
                <div class="text-muted" style="font-size:.75rem;">Expenses</div>
                <div class="fw-bold text-danger" style="font-size:1.4rem;">RM {{ number_format($currentExpenses, 0) }}</div>
            </div>
            <div class="pt-2 border-top">
                <div class="text-muted" style="font-size:.75rem;">Net Profit</div>
                <div class="fw-bold {{ ($currentRevenue - $currentExpenses) >= 0 ? 'text-success' : 'text-danger' }}" style="font-size:1.4rem;">
                    RM {{ number_format($currentRevenue - $currentExpenses, 0) }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Property Info --}}
    <div class="col-lg-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-semibold mb-0">Property Details</h6>
                <a href="{{ route('properties.edit', $property) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
            </div>
            <table class="table table-sm table-borderless mb-0">
                <tbody>
                    <tr><td class="text-muted" style="font-size:.8rem;width:40%">Type</td><td style="font-size:.875rem;">{{ str_replace('_',' ',ucfirst($property->property_type)) }}</td></tr>
                    <tr><td class="text-muted" style="font-size:.8rem;">Bedrooms</td><td style="font-size:.875rem;">{{ $property->bedrooms }}</td></tr>
                    <tr><td class="text-muted" style="font-size:.8rem;">Bathrooms</td><td style="font-size:.875rem;">{{ $property->bathrooms }}</td></tr>
                    <tr><td class="text-muted" style="font-size:.8rem;">Area</td><td style="font-size:.875rem;">{{ $property->area_sqft ? number_format($property->area_sqft, 0) . ' sqft' : '—' }}</td></tr>
                    <tr><td class="text-muted" style="font-size:.8rem;">Owner</td><td style="font-size:.875rem;">{{ $property->owner_name ?? '—' }}</td></tr>
                    <tr><td class="text-muted" style="font-size:.8rem;">Agent</td><td style="font-size:.875rem;">{{ $property->agent->name ?? '—' }}</td></tr>
                    <tr><td class="text-muted" style="font-size:.8rem;">Monthly Target</td><td style="font-size:.875rem;">RM {{ number_format($property->monthly_target, 0) }}</td></tr>
                    @if($property->airbnb_url)<tr><td class="text-muted" style="font-size:.8rem;">Airbnb</td><td><a href="{{ $property->airbnb_url }}" target="_blank" style="font-size:.8rem;">View listing</a></td></tr>@endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- Investment & Quick Links --}}
    <div class="col-lg-6">
        <div class="stat-card mb-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-semibold mb-0">Investment</h6>
                <a href="{{ route('investments.show', $property) }}" class="btn btn-sm btn-outline-primary">Manage</a>
            </div>
            @if($property->investment)
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-muted" style="font-size:.75rem;">Total Invested</div>
                        <div class="fw-bold" style="font-size:1.2rem;">RM {{ number_format($property->investment->totalInvestment(), 0) }}</div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted" style="font-size:.75rem;">Purchase Price</div>
                        <div class="fw-semibold">RM {{ number_format($property->investment->purchase_price, 0) }}</div>
                    </div>
                </div>
            @else
                <p class="text-muted mb-2" style="font-size:.875rem;">No investment data. Add purchase details to enable ROI tracking.</p>
                <a href="{{ route('investments.show', $property) }}" class="btn btn-sm btn-primary">Add Investment Data</a>
            @endif
        </div>

        {{-- Active Tenancy --}}
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-semibold mb-0">Active Tenancy</h6>
                <a href="{{ route('tenancies.create', ['property_id' => $property->id]) }}" class="btn btn-sm btn-outline-primary">+ Add</a>
            </div>
            @if($property->activeTenancy)
                @php $t = $property->activeTenancy; @endphp
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-semibold">{{ $t->tenant_name }}</div>
                        <div class="text-muted" style="font-size:.78rem;">{{ $t->tenant_phone }}</div>
                        <div class="text-muted" style="font-size:.78rem;">{{ $t->start_date->format('d M Y') }} → {{ $t->end_date->format('d M Y') }}</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-success">RM {{ number_format($t->monthly_rent, 0) }}/mo</div>
                        <span class="badge badge-{{ $t->isExpiringSoon() ? 'amber' : 'green' }}" style="font-size:.7rem;">
                            {{ $t->daysUntilExpiry() }}d left
                        </span>
                    </div>
                </div>
            @else
                <p class="text-muted mb-0" style="font-size:.875rem;">No active tenancy.</p>
            @endif
        </div>
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <a href="{{ route('revenue.create') }}?property_id={{ $property->id }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> Add Revenue Entry
    </a>
    <form action="{{ route('properties.destroy', $property) }}" method="POST" onsubmit="return confirm('Remove this property?')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i> Remove Property</button>
    </form>
</div>

@endsection
