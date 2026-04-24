@extends('layouts.app')

@section('title', 'Investment — ' . $property->name)
@section('page-title', 'Investment Tracking')
@section('page-subtitle', $property->name)

@section('content')
<div class="row g-3">
    <div class="col-lg-6">
        <div class="stat-card">
            <h6 class="fw-semibold mb-3">Investment Costs</h6>
            <form action="{{ route('investments.store', $property) }}" method="POST">
                @csrf
                <div class="row g-3">
                    @foreach([
                        ['purchase_price','Purchase Price'],
                        ['renovation_cost','Renovation Cost'],
                        ['setup_cost','Setup Cost'],
                        ['furnishing_cost','Furnishing Cost'],
                        ['deposit_paid','Deposit Paid'],
                        ['legal_fees','Legal Fees'],
                        ['stamp_duty','Stamp Duty'],
                        ['other_costs','Other Costs'],
                    ] as [$field, $label])
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:.85rem;">{{ $label }} (RM)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">RM</span>
                            <input type="number" name="{{ $field }}" class="form-control" value="{{ old($field, $investment->$field ?? 0) }}" step="100" min="0">
                        </div>
                    </div>
                    @endforeach
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:.85rem;">Investment Date</label>
                        <input type="date" name="investment_date" class="form-control form-control-sm" value="{{ old('investment_date', $investment->investment_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size:.85rem;">Notes</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2">{{ old('notes', $investment->notes) }}</textarea>
                    </div>
                    <div class="col-12 d-flex gap-2 justify-content-end">
                        <a href="{{ route('properties.show', $property) }}" class="btn btn-sm btn-outline-secondary">Back</a>
                        <button type="submit" class="btn btn-sm btn-primary px-4">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-6">
        @if($roi)
        <div class="stat-card mb-3">
            <h6 class="fw-semibold mb-3">ROI Analysis (Last 12 Months)</h6>
            <div class="row g-2 text-center mb-3">
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3">
                        <div class="text-muted" style="font-size:.72rem;">Total Invested</div>
                        <div class="fw-bold" style="font-size:1.1rem;">RM {{ number_format($roi['total_investment'], 0) }}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3">
                        <div class="text-muted" style="font-size:.72rem;">Annual Profit</div>
                        <div class="fw-bold text-success" style="font-size:1.1rem;">RM {{ number_format($roi['annual_profit'], 0) }}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3">
                        <div class="text-muted" style="font-size:.72rem;">Annual ROI</div>
                        <div class="fw-bold {{ $roi['annual_roi_pct'] >= 10 ? 'text-success' : ($roi['annual_roi_pct'] >= 5 ? 'text-warning' : 'text-danger') }}" style="font-size:1.4rem;">
                            {{ $roi['annual_roi_pct'] }}%
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3">
                        <div class="text-muted" style="font-size:.72rem;">Payback Period</div>
                        <div class="fw-bold" style="font-size:1.1rem;">
                            {{ $roi['payback_years'] ? $roi['payback_years'] . ' years' : '—' }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center">
                <span class="badge" style="font-size:.85rem;padding:.5rem 1rem;background:{{ match($roi['rating']) { 'Excellent'=>'#dcfce7','Good'=>'#dbeafe','Moderate'=>'#fef9c3','Low'=>'#fed7aa',default=>'#fee2e2' } }};color:{{ match($roi['rating']) { 'Excellent'=>'#15803d','Good'=>'#1e40af','Moderate'=>'#b45309','Low'=>'#c2410c',default=>'#b91c1c' } }};">
                    {{ $roi['rating'] }} ROI
                </span>
            </div>
        </div>
        @else
        <div class="stat-card">
            <div class="text-center py-4 text-muted">
                <i class="bi bi-graph-up" style="font-size:2rem;"></i>
                <p class="mt-2 mb-0">Save investment data to see ROI analysis.</p>
            </div>
        </div>
        @endif

        <div class="stat-card mt-3">
            <h6 class="fw-semibold mb-2">Quick ROI Calculator</h6>
            <p class="text-muted mb-2" style="font-size:.8rem;">Estimate ROI before committing to a deal.</p>
            <a href="{{ route('roi.calculator') }}" class="btn btn-outline-primary btn-sm w-100">
                <i class="bi bi-calculator me-1"></i> Open ROI Calculator
            </a>
        </div>
    </div>
</div>
@endsection
