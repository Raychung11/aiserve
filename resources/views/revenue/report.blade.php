@extends('layouts.app')

@section('title', 'Annual Report')
@section('page-title', 'Annual Revenue Report')
@section('page-subtitle', 'Full year P&L summary')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <form class="d-flex gap-2" method="GET">
        <select name="year" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            @for($y = now()->year; $y >= now()->year - 3; $y--)
            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card"><div class="stat-label">Annual Income</div><div class="stat-value text-success">RM {{ number_format($annualIncome, 0) }}</div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card"><div class="stat-label">Annual Expenses</div><div class="stat-value text-danger">RM {{ number_format($annualExpense, 0) }}</div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Annual Net Profit</div>
            <div class="stat-value {{ $annualProfit >= 0 ? 'text-success' : 'text-danger' }}">RM {{ number_format(abs($annualProfit), 0) }}</div>
        </div>
    </div>
</div>

<div class="stat-card mb-4">
    <h6 class="fw-semibold mb-3">Monthly Breakdown</h6>
    <canvas id="annualChart" height="70"></canvas>
</div>

<div class="stat-card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Month</th>
                <th class="text-success">Income</th>
                <th class="text-danger">Expenses</th>
                <th>Net Profit</th>
                <th>Margin</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report as $period => $data)
            <tr>
                <td class="fw-semibold" style="font-size:.875rem;">{{ $data['month'] }} {{ $year }}</td>
                <td class="text-success">RM {{ number_format($data['income'], 0) }}</td>
                <td class="text-danger">RM {{ number_format($data['expense'], 0) }}</td>
                <td class="{{ $data['profit'] >= 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                    RM {{ number_format(abs($data['profit']), 0) }}
                </td>
                <td class="text-muted" style="font-size:.8rem;">
                    {{ $data['income'] > 0 ? number_format(($data['profit'] / $data['income']) * 100, 1) . '%' : '—' }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="table-light fw-bold">
            <tr>
                <td>Total</td>
                <td class="text-success">RM {{ number_format($annualIncome, 0) }}</td>
                <td class="text-danger">RM {{ number_format($annualExpense, 0) }}</td>
                <td class="{{ $annualProfit >= 0 ? 'text-success' : 'text-danger' }}">RM {{ number_format(abs($annualProfit), 0) }}</td>
                <td class="text-muted">{{ $annualIncome > 0 ? number_format(($annualProfit / $annualIncome) * 100, 1) . '%' : '—' }}</td>
            </tr>
        </tfoot>
    </table>
</div>

@endsection

@push('scripts')
<script>
const ctx = document.getElementById('annualChart');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: @json(collect($report)->pluck('month')),
        datasets: [
            { label: 'Income',   data: @json(collect($report)->pluck('income')),  backgroundColor: '#6366f1', borderRadius: 4 },
            { label: 'Expenses', data: @json(collect($report)->pluck('expense')), backgroundColor: '#fca5a5', borderRadius: 4 },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => 'RM ' + v.toLocaleString() } } }
    }
});
</script>
@endpush
