@extends('layouts.app')

@section('title', $tenancy->tenant_name)
@section('page-title', $tenancy->tenant_name)
@section('page-subtitle', $tenancy->property->name ?? '')

@section('content')
<div class="row g-3">
    <div class="col-lg-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-semibold mb-0">Lease Details</h6>
                <a href="{{ route('tenancies.edit', $tenancy) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
            </div>
            <table class="table table-sm table-borderless mb-0">
                <tbody>
                    <tr><td class="text-muted" style="font-size:.8rem;width:40%">Tenant</td><td style="font-size:.875rem;">{{ $tenancy->tenant_name }}</td></tr>
                    <tr><td class="text-muted" style="font-size:.8rem;">IC/Passport</td><td style="font-size:.875rem;">{{ $tenancy->tenant_ic ?? '—' }}</td></tr>
                    <tr><td class="text-muted" style="font-size:.8rem;">Phone</td><td style="font-size:.875rem;">{{ $tenancy->tenant_phone ?? '—' }}</td></tr>
                    <tr><td class="text-muted" style="font-size:.8rem;">Email</td><td style="font-size:.875rem;">{{ $tenancy->tenant_email ?? '—' }}</td></tr>
                    <tr><td class="text-muted" style="font-size:.8rem;">Company</td><td style="font-size:.875rem;">{{ $tenancy->tenant_company ?? '—' }}</td></tr>
                    <tr><td class="text-muted" style="font-size:.8rem;">Type</td><td><span class="badge badge-{{ strtolower($tenancy->type) }}" style="font-size:.75rem;">{{ $tenancy->type }}</span></td></tr>
                    <tr><td class="text-muted" style="font-size:.8rem;">Property</td><td style="font-size:.875rem;">{{ $tenancy->property->name ?? '—' }}</td></tr>
                    <tr><td class="text-muted" style="font-size:.8rem;">Agent</td><td style="font-size:.875rem;">{{ $tenancy->agent->name ?? '—' }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="stat-card mb-3">
            <h6 class="fw-semibold mb-3">Financial Terms</h6>
            <div class="row g-3">
                <div class="col-6">
                    <div class="text-muted" style="font-size:.75rem;">Monthly Rent</div>
                    <div class="fw-bold text-success" style="font-size:1.3rem;">RM {{ number_format($tenancy->monthly_rent, 0) }}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted" style="font-size:.75rem;">Deposit</div>
                    <div class="fw-bold" style="font-size:1.3rem;">RM {{ number_format($tenancy->deposit, 0) }}</div>
                    <span class="badge {{ $tenancy->deposit_paid ? 'bg-success' : 'bg-warning text-dark' }}" style="font-size:.65rem;">
                        {{ $tenancy->deposit_paid ? 'Paid' : 'Unpaid' }}
                    </span>
                </div>
                <div class="col-6">
                    <div class="text-muted" style="font-size:.75rem;">Start Date</div>
                    <div class="fw-semibold">{{ $tenancy->start_date->format('d M Y') }}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted" style="font-size:.75rem;">End Date</div>
                    <div class="fw-semibold">{{ $tenancy->end_date->format('d M Y') }}</div>
                    @if($tenancy->status === 'active' && $tenancy->isExpiringSoon())
                    <span class="badge badge-amber" style="font-size:.65rem;">{{ $tenancy->daysUntilExpiry() }} days left</span>
                    @endif
                </div>
                <div class="col-6">
                    <div class="text-muted" style="font-size:.75rem;">Duration</div>
                    <div class="fw-semibold">{{ $tenancy->totalLeaseDuration() }} months</div>
                </div>
                <div class="col-6">
                    <div class="text-muted" style="font-size:.75rem;">Status</div>
                    @php $sc = match($tenancy->status) { 'active'=>'success','pending'=>'warning','expired'=>'secondary','terminated'=>'danger',default=>'secondary' }; @endphp
                    <span class="badge bg-{{ $sc }}" style="font-size:.75rem;">{{ ucfirst($tenancy->status) }}</span>
                </div>
            </div>
        </div>

        @if($tenancy->notes)
        <div class="stat-card">
            <h6 class="fw-semibold mb-2">Notes</h6>
            <p class="mb-0 text-muted" style="font-size:.875rem;">{{ $tenancy->notes }}</p>
        </div>
        @endif
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <a href="{{ route('tenancies.index') }}" class="btn btn-outline-secondary btn-sm">← Back</a>
    <form action="{{ route('tenancies.destroy', $tenancy) }}" method="POST" onsubmit="return confirm('Remove this tenancy record?')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
    </form>
</div>
@endsection
