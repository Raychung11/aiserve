@extends('layouts.app')

@section('title', 'Revenue & Expenses')
@section('page-title', 'Revenue & Expenses')
@section('page-subtitle', 'Period: ' . $period)

@section('content')

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Total Income</div>
            <div class="stat-value text-success">RM {{ number_format($summary['income'], 0) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Total Expenses</div>
            <div class="stat-value text-danger">RM {{ number_format($summary['expense'], 0) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Net Profit</div>
            <div class="stat-value {{ $summary['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                RM {{ number_format(abs($summary['profit']), 0) }}
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 align-items-center justify-content-between mb-3 flex-wrap">
    <form class="d-flex gap-2 flex-wrap" method="GET">
        <input type="month" name="period" class="form-control form-control-sm" value="{{ $period }}" onchange="this.form.submit()" style="width:auto;">
        <select name="property_id" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Properties</option>
            @foreach($properties as $p)
            <option value="{{ $p->id }}" {{ request('property_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
        <select name="type" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Types</option>
            <option value="income"  {{ request('type') === 'income'  ? 'selected' : '' }}>Income</option>
            <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Expenses</option>
        </select>
    </form>
    <div class="d-flex gap-2">
        <a href="{{ route('revenue.report') }}" class="btn btn-sm btn-outline-secondary">Annual Report</a>
        <a href="{{ route('revenue.create') }}" class="btn btn-sm btn-primary">+ Add Entry</a>
    </div>
</div>

<div class="stat-card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th style="font-size:.8rem;">Property</th>
                <th style="font-size:.8rem;">Category</th>
                <th style="font-size:.8rem;">Type</th>
                <th style="font-size:.8rem;">Amount</th>
                <th style="font-size:.8rem;">Date</th>
                <th style="font-size:.8rem;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
            <tr>
                <td style="font-size:.85rem;">{{ $entry->property->name ?? '—' }}</td>
                <td style="font-size:.85rem;">{{ ucfirst(str_replace('_',' ',$entry->category)) }}</td>
                <td>
                    <span class="badge {{ $entry->type === 'income' ? 'bg-success' : 'bg-danger' }} bg-opacity-10 text-{{ $entry->type === 'income' ? 'success' : 'danger' }}" style="font-size:.7rem;">
                        {{ ucfirst($entry->type) }}
                    </span>
                </td>
                <td class="{{ $entry->type === 'income' ? 'text-success' : 'text-danger' }} fw-semibold" style="font-size:.875rem;">
                    {{ $entry->type === 'income' ? '+' : '-' }}RM {{ number_format($entry->amount, 2) }}
                </td>
                <td class="text-muted" style="font-size:.78rem;">{{ $entry->payment_date?->format('d M Y') ?? '—' }}</td>
                <td>
                    <form action="{{ route('revenue.destroy', $entry) }}" method="POST" onsubmit="return confirm('Remove this entry?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No entries for this period.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-3">{{ $entries->withQueryString()->links() }}</div>
</div>

@endsection
