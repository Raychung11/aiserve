@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Portfolio overview for ' . now()->format('F Y'))

@section('content')

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-label">Monthly Revenue</span>
                <div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="bi bi-cash-stack"></i></div>
            </div>
            <div class="stat-value">RM {{ number_format($totalRevenue, 0) }}</div>
            <div class="text-muted" style="font-size:.75rem;">{{ $period }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-label">Net Profit</span>
                <div class="stat-icon" style="background:{{ $netProfit >= 0 ? '#dcfce7' : '#fee2e2' }};color:{{ $netProfit >= 0 ? '#16a34a' : '#dc2626' }};"><i class="bi bi-graph-up-arrow"></i></div>
            </div>
            <div class="stat-value {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">RM {{ number_format(abs($netProfit), 0) }}</div>
            <div class="text-muted" style="font-size:.75rem;">after RM {{ number_format($totalExpenses, 0) }} expenses</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-label">Active Units</span>
                <div class="stat-icon" style="background:#ede9fe;color:#7c3aed;"><i class="bi bi-buildings"></i></div>
            </div>
            <div class="stat-value">{{ $activeProperties }}</div>
            <div class="text-muted" style="font-size:.75rem;">of {{ $properties->count() }} total properties</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-label">Lease Alerts</span>
                <div class="stat-icon" style="background:#fef9c3;color:#ca8a04;"><i class="bi bi-bell-fill"></i></div>
            </div>
            <div class="stat-value {{ $expiringLeases->count() > 0 ? 'text-warning' : '' }}">{{ $expiringLeases->count() }}</div>
            <div class="text-muted" style="font-size:.75rem;">expiring within 30 days</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- Revenue Chart --}}
    <div class="col-lg-8">
        <div class="stat-card">
            <h6 class="fw-semibold mb-3">Revenue vs Expenses (Last 6 Months)</h6>
            <canvas id="revenueChart" height="100"></canvas>
        </div>
    </div>

    {{-- Compliance Summary --}}
    <div class="col-lg-4">
        <div class="stat-card h-100">
            <h6 class="fw-semibold mb-3">Compliance Status</h6>
            @foreach([['green','SUCCESS','STR Allowed'],['amber','WARNING','Verify Required'],['red','DANGER','Not Suitable']] as [$s,$c,$l])
            <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                <span class="compliance-{{ $s }}"><i class="bi bi-circle-fill" style="font-size:.55rem"></i> {{ $l }}</span>
                <span class="fw-bold">{{ $complianceSummary[$s] }}</span>
            </div>
            @endforeach
            <div class="d-flex align-items-center justify-content-between py-2">
                <span class="text-muted">Total</span>
                <span class="fw-bold">{{ $properties->count() }}</span>
            </div>
            <a href="{{ route('properties.index') }}" class="btn btn-sm btn-outline-secondary w-100 mt-2">View All Properties</a>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Expiring Leases --}}
    <div class="col-lg-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-semibold mb-0">Expiring Leases</h6>
                <a href="{{ route('tenancies.index', ['status' => 'active']) }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            @forelse($expiringLeases as $lease)
            <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                <div>
                    <div class="fw-semibold" style="font-size:.875rem;">{{ $lease->tenant_name }}</div>
                    <div class="text-muted" style="font-size:.75rem;">{{ $lease->property->name ?? '—' }}</div>
                </div>
                <div class="text-end">
                    <span class="badge badge-amber">{{ $lease->daysUntilExpiry() }}d left</span>
                    <div class="text-muted" style="font-size:.7rem;">{{ $lease->end_date->format('d M Y') }}</div>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-4">
                <i class="bi bi-check-circle" style="font-size:1.5rem;"></i>
                <div class="mt-1" style="font-size:.85rem;">No leases expiring soon</div>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Recent Entries --}}
    <div class="col-lg-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-semibold mb-0">Recent Transactions</h6>
                <a href="{{ route('revenue.create') }}" class="btn btn-sm btn-primary">+ Add Entry</a>
            </div>
            @forelse($recentEntries as $entry)
            <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                <div>
                    <div class="fw-semibold" style="font-size:.875rem;">{{ ucfirst($entry->category) }}</div>
                    <div class="text-muted" style="font-size:.75rem;">{{ $entry->property->name ?? '—' }} · {{ $entry->period }}</div>
                </div>
                <span class="{{ $entry->type === 'income' ? 'text-success' : 'text-danger' }} fw-semibold">
                    {{ $entry->type === 'income' ? '+' : '-' }}RM {{ number_format($entry->amount, 0) }}
                </span>
            </div>
            @empty
            <div class="text-center text-muted py-4">
                <i class="bi bi-inbox" style="font-size:1.5rem;"></i>
                <div class="mt-1" style="font-size:.85rem;">No entries yet</div>
            </div>
            @endforelse
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const ctx = document.getElementById('revenueChart');
const labels = @json(collect($monthlyRevenue)->pluck('label'));
const income  = @json(collect($monthlyRevenue)->pluck('income'));
const expense = @json(collect($monthlyRevenue)->pluck('expense'));

new Chart(ctx, {
    type: 'bar',
    data: {
        labels,
        datasets: [
            { label: 'Income',   data: income,  backgroundColor: '#6366f1', borderRadius: 4 },
            { label: 'Expenses', data: expense, backgroundColor: '#e2e8f0', borderRadius: 4 },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => 'RM ' + v.toLocaleString() } }
        }
    }
});
</script>
@endpush
