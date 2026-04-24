@extends('layouts.app')

@section('title', 'Tenancies')
@section('page-title', 'Tenancies')
@section('page-subtitle', 'Lease and rental records')

@section('content')

@if($expiringCount > 0)
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <strong>{{ $expiringCount }} lease{{ $expiringCount > 1 ? 's' : '' }}</strong>&nbsp;expiring within 30 days. Review and action renewals.
</div>
@endif

<div class="d-flex gap-2 align-items-center justify-content-between mb-3">
    <form class="d-flex gap-2" method="GET">
        <select name="status" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="active"     {{ request('status') === 'active'     ? 'selected' : '' }}>Active</option>
            <option value="pending"    {{ request('status') === 'pending'    ? 'selected' : '' }}>Pending</option>
            <option value="expired"    {{ request('status') === 'expired'    ? 'selected' : '' }}>Expired</option>
            <option value="terminated" {{ request('status') === 'terminated' ? 'selected' : '' }}>Terminated</option>
        </select>
        <select name="property_id" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Properties</option>
            @foreach($properties as $p)
            <option value="{{ $p->id }}" {{ request('property_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
    </form>
    <a href="{{ route('tenancies.create') }}" class="btn btn-sm btn-primary">+ New Tenancy</a>
</div>

<div class="stat-card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th style="font-size:.8rem;">Tenant</th>
                <th style="font-size:.8rem;">Property</th>
                <th style="font-size:.8rem;">Type</th>
                <th style="font-size:.8rem;">Monthly Rent</th>
                <th style="font-size:.8rem;">End Date</th>
                <th style="font-size:.8rem;">Status</th>
                <th style="font-size:.8rem;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($tenancies as $tenancy)
            <tr>
                <td>
                    <div class="fw-semibold" style="font-size:.875rem;">{{ $tenancy->tenant_name }}</div>
                    <div class="text-muted" style="font-size:.75rem;">{{ $tenancy->tenant_phone }}</div>
                </td>
                <td style="font-size:.85rem;">{{ $tenancy->property->name ?? '—' }}</td>
                <td>
                    <span class="badge badge-{{ strtolower(str_replace('_','-',$tenancy->type)) }}" style="font-size:.7rem;">{{ $tenancy->type }}</span>
                </td>
                <td class="fw-semibold" style="font-size:.875rem;">RM {{ number_format($tenancy->monthly_rent, 0) }}</td>
                <td>
                    <div style="font-size:.8rem;">{{ $tenancy->end_date->format('d M Y') }}</div>
                    @if($tenancy->status === 'active' && $tenancy->isExpiringSoon())
                    <span class="badge badge-amber" style="font-size:.65rem;">{{ $tenancy->daysUntilExpiry() }}d left</span>
                    @endif
                </td>
                <td>
                    @php $sc = match($tenancy->status) { 'active'=>'success','pending'=>'warning','expired'=>'secondary','terminated'=>'danger',default=>'secondary' }; @endphp
                    <span class="badge bg-{{ $sc }} bg-opacity-15 text-{{ $sc }}" style="font-size:.7rem;">{{ ucfirst($tenancy->status) }}</span>
                </td>
                <td><a href="{{ route('tenancies.show', $tenancy) }}" class="btn btn-sm btn-outline-primary">View</a></td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No tenancy records found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-3">{{ $tenancies->withQueryString()->links() }}</div>
</div>

@endsection
