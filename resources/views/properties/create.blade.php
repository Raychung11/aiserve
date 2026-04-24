@extends('layouts.app')

@section('title', 'Add Property')
@section('page-title', 'Add Property')
@section('page-subtitle', 'Register a new unit in your portfolio')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-8">
<div class="stat-card">
<form action="{{ route('properties.store') }}" method="POST">
@csrf

<h6 class="fw-semibold mb-3 pb-2 border-bottom">Basic Information</h6>
<div class="row g-3 mb-4">
    <div class="col-12">
        <label class="form-label fw-semibold">Property Name / Unit</label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Verve Suites KL South Unit 12A" required>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Address</label>
        <input type="text" name="address" class="form-control" value="{{ old('address') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">City</label>
        <input type="text" name="city" class="form-control" value="{{ old('city') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">State</label>
        <select name="state" class="form-select" required>
            <option value="">Select State</option>
            @foreach(['Kuala Lumpur','Selangor','Penang','Johor','Sabah','Sarawak','Melaka','Negeri Sembilan','Perak','Kedah','Kelantan','Terengganu','Pahang','Perlis','Putrajaya','Labuan'] as $state)
            <option value="{{ $state }}" {{ old('state') === $state ? 'selected' : '' }}>{{ $state }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Postcode</label>
        <input type="text" name="postcode" class="form-control" value="{{ old('postcode') }}" maxlength="10" required>
    </div>
</div>

<h6 class="fw-semibold mb-3 pb-2 border-bottom">Property Specs</h6>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <label class="form-label fw-semibold">Property Type</label>
        <select name="property_type" class="form-select" required>
            @foreach(['condo'=>'Condominium','serviced_apartment'=>'Serviced Apartment','landed'=>'Landed','commercial'=>'Commercial','soho'=>'SOHO','sofo'=>'SOFO'] as $val=>$label)
            <option value="{{ $val }}" {{ old('property_type') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Bedrooms</label>
        <input type="number" name="bedrooms" class="form-control" value="{{ old('bedrooms', 1) }}" min="0" required>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Bathrooms</label>
        <input type="number" name="bathrooms" class="form-control" value="{{ old('bathrooms', 1) }}" min="0" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Building / Strata Name</label>
        <input type="text" name="strata_building" class="form-control" value="{{ old('strata_building') }}" placeholder="e.g. Verve Suites">
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Area (sqft)</label>
        <input type="number" name="area_sqft" class="form-control" value="{{ old('area_sqft') }}" step="0.01" min="0">
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_strata" id="is_strata" value="1" {{ old('is_strata', '1') ? 'checked' : '' }}>
            <label class="form-check-label" for="is_strata">Strata Title</label>
        </div>
    </div>
</div>

<h6 class="fw-semibold mb-3 pb-2 border-bottom">Strategy & Owner</h6>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <label class="form-label fw-semibold">Strategy Mode</label>
        <select name="strategy_mode" class="form-select" required>
            <option value="STR"       {{ old('strategy_mode') === 'STR'       ? 'selected' : '' }}>STR (Short-Term)</option>
            <option value="MID_TERM"  {{ old('strategy_mode') === 'MID_TERM'  ? 'selected' : '' }}>Mid-Term (30–90d)</option>
            <option value="SUBLET"    {{ old('strategy_mode') === 'SUBLET'    ? 'selected' : '' }}>Room Sublet</option>
            <option value="CORPORATE" {{ old('strategy_mode') === 'CORPORATE' ? 'selected' : '' }}>Corporate Lease</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Assigned Agent</label>
        <select name="agent_id" class="form-select">
            <option value="">No agent</option>
            @foreach($agents as $agent)
            <option value="{{ $agent->id }}" {{ old('agent_id') == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Monthly Revenue Target (RM)</label>
        <input type="number" name="monthly_target" class="form-control" value="{{ old('monthly_target') }}" min="0" step="50">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Owner Name</label>
        <input type="text" name="owner_name" class="form-control" value="{{ old('owner_name') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Owner Phone</label>
        <input type="text" name="owner_phone" class="form-control" value="{{ old('owner_phone') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Owner Email</label>
        <input type="email" name="owner_email" class="form-control" value="{{ old('owner_email') }}">
    </div>
</div>

<div class="d-flex gap-2 justify-content-end">
    <a href="{{ route('properties.index') }}" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary px-4">Save Property</button>
</div>

</form>
</div>
</div>
</div>
@endsection
