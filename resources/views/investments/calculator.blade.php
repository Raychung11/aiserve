@extends('layouts.app')

@section('title', 'ROI Calculator')
@section('page-title', 'ROI Calculator')
@section('page-subtitle', 'Estimate returns before committing to a deal')

@section('content')
<div class="row g-3">
    <div class="col-lg-5">
        <div class="stat-card">
            <h6 class="fw-semibold mb-4">Quick Estimate</h6>
            <form action="{{ route('roi.calculator') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Total Investment (RM)</label>
                    <div class="input-group">
                        <span class="input-group-text">RM</span>
                        <input type="number" name="investment" class="form-control" value="{{ old('investment', request('investment')) }}" step="1000" min="1" placeholder="e.g. 350000" required>
                    </div>
                    <div class="form-text">Purchase + reno + setup + legal fees</div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Expected Monthly Profit (RM)</label>
                    <div class="input-group">
                        <span class="input-group-text">RM</span>
                        <input type="number" name="monthly_profit" class="form-control" value="{{ old('monthly_profit', request('monthly_profit')) }}" step="100" placeholder="e.g. 2500" required>
                    </div>
                    <div class="form-text">Revenue minus all monthly costs</div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Calculate ROI</button>
            </form>

            <hr class="my-4">

            <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                <div class="fw-semibold mb-2" style="font-size:.85rem;">STRHub Formula</div>
                <div class="text-muted" style="font-size:.78rem;line-height:1.8;">
                    Monthly ROI = Monthly Profit ÷ Total Investment × 100<br>
                    Annual ROI = Monthly ROI × 12<br>
                    Payback = Total Investment ÷ Annual Profit
                </div>
            </div>

            <div class="mt-3 p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                <div class="fw-semibold mb-2" style="font-size:.85rem;">STRHub Benchmarks</div>
                <div class="d-flex flex-column gap-1" style="font-size:.78rem;">
                    <div class="d-flex justify-content-between"><span class="text-muted">Excellent</span><span class="text-success fw-semibold">≥ 15% annual</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Good</span><span class="text-primary fw-semibold">10–14%</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Moderate</span><span class="text-warning fw-semibold">6–9%</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Low</span><span class="text-danger fw-semibold">3–5%</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Poor</span><span class="text-danger fw-semibold">< 3%</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        @if($result)
        <div class="stat-card mb-3">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h6 class="fw-semibold mb-0">Result</h6>
                <span class="badge" style="font-size:.9rem;padding:.5rem 1.25rem;background:{{ match($result['rating']) { 'Excellent'=>'#dcfce7','Good'=>'#dbeafe','Moderate'=>'#fef9c3','Low'=>'#fed7aa',default=>'#fee2e2' } }};color:{{ match($result['rating']) { 'Excellent'=>'#15803d','Good'=>'#1e40af','Moderate'=>'#b45309','Low'=>'#c2410c',default=>'#b91c1c' } }};">
                    {{ $result['rating'] }}
                </span>
            </div>

            <div class="row g-3 text-center mb-4">
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-3" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                        <div class="text-muted" style="font-size:.7rem;">Monthly ROI</div>
                        <div class="fw-bold text-success" style="font-size:1.5rem;">{{ $result['monthly_roi_pct'] }}%</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-3" style="background:#eff6ff;border:1px solid #bfdbfe;">
                        <div class="text-muted" style="font-size:.7rem;">Annual ROI</div>
                        <div class="fw-bold text-primary" style="font-size:1.5rem;">{{ $result['annual_roi_pct'] }}%</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-3" style="background:#faf5ff;border:1px solid #e9d5ff;">
                        <div class="text-muted" style="font-size:.7rem;">Payback Period</div>
                        <div class="fw-bold" style="font-size:1.3rem;color:#7c3aed;">
                            {{ $result['payback_years'] ? $result['payback_years'] . ' yrs' : '—' }}
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-3" style="background:#fff7ed;border:1px solid #fed7aa;">
                        <div class="text-muted" style="font-size:.7rem;">Monthly Profit</div>
                        <div class="fw-bold text-orange" style="font-size:1.3rem;color:#c2410c;">
                            RM {{ number_format(request('monthly_profit'), 0) }}
                        </div>
                    </div>
                </div>
            </div>

            <canvas id="roiChart" height="120"></canvas>
        </div>

        <div class="stat-card">
            <h6 class="fw-semibold mb-3">Strategy Recommendation</h6>
            @php $annualRoi = $result['annual_roi_pct']; @endphp
            @if($annualRoi >= 15)
                <div class="alert alert-success mb-0"><i class="bi bi-check-circle-fill me-2"></i> <strong>Strong deal.</strong> This property meets or exceeds STRHub's Excellent ROI threshold. Proceed with acquisition and prioritise STR listing.</div>
            @elseif($annualRoi >= 10)
                <div class="alert alert-info mb-0"><i class="bi bi-info-circle-fill me-2"></i> <strong>Good investment.</strong> Consider STR or Mid-Term strategy to push ROI above 15%. Review expense optimisation opportunities.</div>
            @elseif($annualRoi >= 6)
                <div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Moderate return.</strong> Switch to a higher-yield strategy (STR or SUBLET). Renegotiate purchase price or reduce setup costs if possible.</div>
            @else
                <div class="alert alert-danger mb-0"><i class="bi bi-x-circle-fill me-2"></i> <strong>Below threshold.</strong> This deal does not meet minimum ROI targets. Reconsider the purchase price or find alternative revenue sources.</div>
            @endif
        </div>
        @else
        <div class="stat-card d-flex flex-column align-items-center justify-content-center py-5 text-center">
            <i class="bi bi-calculator" style="font-size:4rem;color:#c7d2fe;"></i>
            <h5 class="mt-3 fw-semibold text-muted">Enter figures to calculate ROI</h5>
            <p class="text-muted mb-0" style="font-size:.875rem;">Fill in the investment amount and expected monthly profit on the left to see your returns.</p>
        </div>
        @endif
    </div>
</div>

@endsection

@if($result)
@push('scripts')
<script>
const years = Array.from({length: 10}, (_, i) => 'Year ' + (i + 1));
const cumulative = years.map((_, i) => {
    const profit = {{ request('monthly_profit') }} * 12 * (i + 1);
    return Math.round(profit);
});
const investmentLine = Array(10).fill({{ request('investment') }});

new Chart(document.getElementById('roiChart'), {
    type: 'line',
    data: {
        labels: years,
        datasets: [
            {
                label: 'Cumulative Profit',
                data: cumulative,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99,102,241,.08)',
                fill: true,
                tension: 0.3,
                borderWidth: 2,
            },
            {
                label: 'Total Investment',
                data: investmentLine,
                borderColor: '#e2e8f0',
                borderDash: [6, 3],
                borderWidth: 2,
                pointRadius: 0,
            }
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
@endif
