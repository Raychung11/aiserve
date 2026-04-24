@extends('layouts.app')

@section('title', 'Add Entry')
@section('page-title', 'Add Revenue / Expense')
@section('page-subtitle', 'Record income or expense for a property')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-7">
<div class="stat-card">
<form action="{{ route('revenue.store') }}" method="POST">
@csrf
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Property</label>
        <select name="property_id" class="form-select" required>
            <option value="">Select property</option>
            @foreach($properties as $p)
            <option value="{{ $p->id }}" {{ old('property_id', request('property_id')) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Period</label>
        <input type="month" name="period" class="form-control" value="{{ old('period', now()->format('Y-m')) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Type</label>
        <div class="d-flex gap-2">
            <label class="border rounded-3 px-3 py-2 d-flex align-items-center gap-2 flex-grow-1" style="cursor:pointer;">
                <input type="radio" name="type" value="income" {{ old('type', 'income') === 'income' ? 'checked' : '' }} required> Income
            </label>
            <label class="border rounded-3 px-3 py-2 d-flex align-items-center gap-2 flex-grow-1" style="cursor:pointer;">
                <input type="radio" name="type" value="expense" {{ old('type') === 'expense' ? 'checked' : '' }}> Expense
            </label>
        </div>
    </div>
    <div class="col-md-8">
        <label class="form-label fw-semibold">Category</label>
        <select name="category" class="form-select" required>
            <optgroup label="Income">
                <option value="rental"         {{ old('category') === 'rental'         ? 'selected' : '' }}>Rental Income</option>
            </optgroup>
            <optgroup label="Expenses">
                <option value="cleaning"       {{ old('category') === 'cleaning'       ? 'selected' : '' }}>Cleaning</option>
                <option value="utilities"      {{ old('category') === 'utilities'      ? 'selected' : '' }}>Utilities</option>
                <option value="maintenance"    {{ old('category') === 'maintenance'    ? 'selected' : '' }}>Maintenance</option>
                <option value="platform_fee"   {{ old('category') === 'platform_fee'   ? 'selected' : '' }}>Platform Fee</option>
                <option value="commission"     {{ old('category') === 'commission'     ? 'selected' : '' }}>Commission</option>
                <option value="insurance"      {{ old('category') === 'insurance'      ? 'selected' : '' }}>Insurance</option>
                <option value="assessment"     {{ old('category') === 'assessment'     ? 'selected' : '' }}>Assessment/Quit Rent</option>
                <option value="management_fee" {{ old('category') === 'management_fee' ? 'selected' : '' }}>Management Fee</option>
                <option value="other"          {{ old('category') === 'other'          ? 'selected' : '' }}>Other</option>
            </optgroup>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Amount (RM)</label>
        <div class="input-group">
            <span class="input-group-text">RM</span>
            <input type="number" name="amount" class="form-control" value="{{ old('amount') }}" step="0.01" min="0.01" required>
        </div>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Payment Date</label>
        <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', now()->format('Y-m-d')) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Description</label>
        <input type="text" name="description" class="form-control" value="{{ old('description') }}" placeholder="Optional note">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Reference / Receipt No.</label>
        <input type="text" name="payment_ref" class="form-control" value="{{ old('payment_ref') }}">
    </div>
    <div class="col-12 d-flex gap-2 justify-content-end">
        <a href="{{ route('revenue.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary px-4">Save Entry</button>
    </div>
</div>
</form>
</div>
</div>
</div>
@endsection
