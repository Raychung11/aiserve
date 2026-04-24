@extends('layouts.app')

@section('title', $agent->name)
@section('page-title', $agent->name)
@section('page-subtitle', 'Agent Profile & Commission Wallet')

@section('content')

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold mx-auto mb-2" style="width:56px;height:56px;background:#6366f1;font-size:1.3rem;">
                {{ strtoupper(substr($agent->name, 0, 1)) }}
            </div>
            <div class="fw-semibold">{{ $agent->name }}</div>
            <div class="text-muted" style="font-size:.78rem;">{{ $agent->email }}</div>
            <div class="text-muted" style="font-size:.78rem;">{{ $agent->phone }}</div>
            <div class="mt-2">
                <span class="badge" style="background:#ede9fe;color:#5b21b6;font-size:.75rem;">{{ $agent->commission_tier }}% commission</span>
            </div>
            <div class="mt-2 p-2 bg-light rounded-3">
                <div class="text-muted" style="font-size:.7rem;">Agent Code</div>
                <code class="fw-bold" style="font-size:.85rem;">{{ $agent->agent_code }}</code>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Properties</div>
                    <div class="stat-value">{{ $stats['total_properties'] }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Pending Commission</div>
                    <div class="stat-value text-warning">RM {{ number_format($stats['pending'], 0) }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Total Earned</div>
                    <div class="stat-value text-success">RM {{ number_format($stats['paid'], 0) }}</div>
                </div>
            </div>
        </div>

        @if($stats['pending'] > 0)
        <div class="stat-card mt-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="fw-semibold">Pending Payout: RM {{ number_format($stats['pending'], 2) }}</div>
                    <div class="text-muted" style="font-size:.8rem;">Mark all pending commissions as paid</div>
                </div>
                <form action="{{ route('agents.pay', $agent) }}" method="POST">
                    @csrf
                    <input type="text" name="reference" class="form-control form-control-sm me-2 d-inline" style="width:140px;" placeholder="Reference no.">
                    <button type="submit" class="btn btn-success btn-sm">Pay Now</button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="stat-card">
    <h6 class="fw-semibold mb-3">Commission History</h6>
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th style="font-size:.8rem;">Property</th>
                <th style="font-size:.8rem;">Revenue</th>
                <th style="font-size:.8rem;">Rate</th>
                <th style="font-size:.8rem;">Commission</th>
                <th style="font-size:.8rem;">Status</th>
                <th style="font-size:.8rem;">Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($commissions as $log)
            <tr>
                <td style="font-size:.85rem;">{{ $log->property->name ?? '—' }}</td>
                <td style="font-size:.85rem;">RM {{ number_format($log->revenue_amount, 0) }}</td>
                <td style="font-size:.85rem;">{{ $log->commission_rate }}%</td>
                <td class="fw-semibold text-success" style="font-size:.875rem;">RM {{ number_format($log->commission_amount, 2) }}</td>
                <td>
                    @php $sc = match($log->status) { 'paid'=>'success','pending'=>'warning','cancelled'=>'secondary',default=>'secondary' }; @endphp
                    <span class="badge bg-{{ $sc }} bg-opacity-15 text-{{ $sc }}" style="font-size:.7rem;">{{ ucfirst($log->status) }}</span>
                </td>
                <td class="text-muted" style="font-size:.78rem;">{{ $log->paid_at?->format('d M Y') ?? $log->created_at->format('d M Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No commission records yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-3">{{ $commissions->links() }}</div>
</div>

@endsection
