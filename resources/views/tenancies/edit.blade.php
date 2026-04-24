@extends('layouts.app')

@section('title', 'Edit Tenancy')
@section('page-title', 'Edit Tenancy')
@section('page-subtitle', $tenancy->tenant_name)

@section('content')
<div class="row justify-content-center">
<div class="col-lg-8">
<div class="stat-card">
<form action="{{ route('tenancies.update', $tenancy) }}" method="POST">
@csrf @method('PUT')
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Full Name</label>
        <input type="text" name="tenant_name" class="form-control" value="{{ old('tenant_name', $tenancy->tenant_name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Phone</label>
        <input type="text" name="tenant_phone" class="form-control" value="{{ old('tenant_phone', $tenancy->tenant_phone) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" name="tenant_email" class="form-control" value="{{ old('tenant_email', $tenancy->tenant_email) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">IC / Passport</label>
        <input type="text" name="tenant_ic" class="form-control" value="{{ old('tenant_ic', $tenancy->tenant_ic) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Type</label>
        <select name="type" class="form-select" required>
            @foreach(['STR','MID_TERM','SUBLET','CORPORATE'] as $t)
            <option value="{{ $t }}" {{ old('type', $tenancy->type) === $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Status</label>
        <select name="status" class="form-select" required>
            @foreach(['active','pending','expired','terminated'] as $s)
            <option value="{{ $s }}" {{ old('status', $tenancy->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Property</label>
        <select name="property_id" class="form-select" required>
            @foreach($properties as $p)
            <option value="{{ $p->id }}" {{ old('property_id', $tenancy->property_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Start Date</label>
        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $tenancy->start_date->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">End Date</label>
        <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $tenancy->end_date->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Monthly Rent (RM)</label>
        <input type="number" name="monthly_rent" class="form-control" value="{{ old('monthly_rent', $tenancy->monthly_rent) }}" step="50" min="0" required>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Deposit (RM)</label>
        <input type="number" name="deposit" class="form-control" value="{{ old('deposit', $tenancy->deposit) }}" step="50" min="0">
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $tenancy->notes) }}</textarea>
    </div>
    <div class="col-12 d-flex gap-2 justify-content-end">
        <a href="{{ route('tenancies.show', $tenancy) }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary px-4">Save Changes</button>
    </div>
</div>
</form>
</div>
</div>
</div>
@endsection
