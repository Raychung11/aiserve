@extends('layouts.app')

@section('title', 'Properties')
@section('page-title', 'Properties')
@section('page-subtitle', 'Manage your portfolio units')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <form class="d-flex gap-2" method="GET">
        <select name="compliance" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Compliance</option>
            <option value="green" {{ request('compliance') === 'green' ? 'selected' : '' }}>🟢 Green</option>
            <option value="amber" {{ request('compliance') === 'amber' ? 'selected' : '' }}>🟡 Amber</option>
            <option value="red"   {{ request('compliance') === 'red'   ? 'selected' : '' }}>🔴 Red</option>
        </select>
        <select name="strategy" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Strategies</option>
            <option value="STR"       {{ request('strategy') === 'STR'       ? 'selected' : '' }}>STR</option>
            <option value="MID_TERM"  {{ request('strategy') === 'MID_TERM'  ? 'selected' : '' }}>Mid-Term</option>
            <option value="SUBLET"    {{ request('strategy') === 'SUBLET'    ? 'selected' : '' }}>Sublet</option>
            <option value="CORPORATE" {{ request('strategy') === 'CORPORATE' ? 'selected' : '' }}>Corporate</option>
        </select>
        <select name="status" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
        </select>
    </form>
    <a href="{{ route('properties.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Property</a>
</div>

@if($properties->isEmpty())
<div class="text-center py-5 stat-card">
    <i class="bi bi-buildings" style="font-size:3rem;color:#cbd5e1;"></i>
    <h5 class="mt-3 text-muted">No properties yet</h5>
    <a href="{{ route('properties.create') }}" class="btn btn-primary mt-2">Add your first property</a>
</div>
@else
<div class="row g-3">
    @foreach($properties as $property)
    <div class="col-md-6 col-xl-4">
        <div class="stat-card card-hover h-100">
            <div class="d-flex align-items-start justify-content-between mb-2">
                <div>
                    <h6 class="fw-semibold mb-1">{{ $property->name }}</h6>
                    <div class="text-muted" style="font-size:.78rem;"><i class="bi bi-geo-alt"></i> {{ $property->city }}, {{ $property->state }}</div>
                </div>
                <span class="badge badge-{{ $property->compliance_status }}">
                    {{ strtoupper($property->compliance_status) }}
                </span>
            </div>

            <div class="d-flex gap-2 mb-3 flex-wrap">
                <span class="badge badge-{{ strtolower(str_replace('_','-',$property->strategy_mode)) }}" style="font-size:.7rem;">
                    {{ $property->strategy_mode }}
                </span>
                <span class="badge {{ $property->listing_status === 'active' ? 'bg-success' : 'bg-secondary' }} bg-opacity-10 text-{{ $property->listing_status === 'active' ? 'success' : 'secondary' }}" style="font-size:.7rem;">
                    {{ ucfirst($property->listing_status) }}
                </span>
                <span class="badge bg-light text-dark border" style="font-size:.7rem;">
                    {{ $property->bedrooms }}BR · {{ $property->bathrooms }}BA
                </span>
            </div>

            <div class="d-flex align-items-center justify-content-between">
                <div>
                    @if($property->agent)
                    <div style="font-size:.75rem;color:#64748b;"><i class="bi bi-person"></i> {{ $property->agent->name }}</div>
                    @endif
                    @if($property->activeTenancy)
                    <div style="font-size:.75rem;color:#16a34a;"><i class="bi bi-person-check"></i> {{ $property->activeTenancy->tenant_name }}</div>
                    @endif
                </div>
                <a href="{{ route('properties.show', $property) }}" class="btn btn-sm btn-outline-primary">View</a>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-4">{{ $properties->withQueryString()->links() }}</div>
@endif

@endsection
