@extends('layouts.app')

@section('title', 'New Tenancy')
@section('page-title', 'New Tenancy')
@section('page-subtitle', 'Create a lease or rental record')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-8">
<div class="stat-card">
<form action="{{ route('tenancies.store') }}" method="POST">
@csrf
<h6 class="fw-semibold mb-3 pb-2 border-bottom">Tenant Details</h6>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Full Name</label>
        <input type="text" name="tenant_name" class="form-control" value="{{ old('tenant_name') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">IC / Passport No.</label>
        <input type="text" name="tenant_ic" class="form-control" value="{{ old('tenant_ic') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Phone</label>
        <input type="text" name="tenant_phone" class="form-control" value="{{ old('tenant_phone') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" name="tenant_email" class="form-control" value="{{ old('tenant_email') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Company (for Corporate)</label>
        <input type="text" name="tenant_company" class="form-control" value="{{ old('tenant_company') }}">
    </div>
</div>

<h6 class="fw-semibold mb-3 pb-2 border-bottom">Lease Details</h6>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Property</label>
        <select name="property_id" class="form-select" required>
            <option value="">Select property</option>
            @foreach($properties as $p)
            <option value="{{ $p->id }}" {{ old('property_id', $propertyId) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Type</label>
        <select name="type" class="form-select" required>
            <option value="STR"       {{ old('type') === 'STR'       ? 'selected' : '' }}>STR</option>
            <option value="MID_TERM"  {{ old('type', 'MID_TERM') === 'MID_TERM'  ? 'selected' : '' }}>Mid-Term</option>
            <option value="SUBLET"    {{ old('type') === 'SUBLET'    ? 'selected' : '' }}>Sublet</option>
            <option value="CORPORATE" {{ old('type') === 'CORPORATE' ? 'selected' : '' }}>Corporate</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Status</label>
        <select name="status" class="form-select" required>
            <option value="active"  {{ old('status', 'active') === 'active'  ? 'selected' : '' }}>Active</option>
            <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Start Date</label>
        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', now()->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">End Date</label>
        <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Monthly Rent (RM)</label>
        <div class="input-group">
            <span class="input-group-text">RM</span>
            <input type="number" name="monthly_rent" class="form-control" value="{{ old('monthly_rent') }}" step="50" min="0" required>
        </div>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Deposit (RM)</label>
        <div class="input-group">
            <span class="input-group-text">RM</span>
            <input type="number" name="deposit" class="form-control" value="{{ old('deposit') }}" step="50" min="0">
        </div>
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="deposit_paid" id="deposit_paid" value="1" {{ old('deposit_paid') ? 'checked' : '' }}>
            <label class="form-check-label" for="deposit_paid">Deposit Paid</label>
        </div>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Assigned Agent</label>
        <select name="agent_id" class="form-select">
            <option value="">No agent</option>
            @foreach($agents as $agent)
            <option value="{{ $agent->id }}" {{ old('agent_id') == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
    </div>
</div>

<div class="d-flex gap-2 justify-content-end">
    <a href="{{ route('tenancies.index') }}" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary px-4">Create Tenancy</button>
</div>
</form>
</div>
</div>
</div>
@endsection
