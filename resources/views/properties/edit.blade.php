@extends('layouts.app')

@section('title', 'Edit Property')
@section('page-title', 'Edit Property')
@section('page-subtitle', $property->name)

@section('content')
<div class="row justify-content-center">
<div class="col-lg-8">
<div class="stat-card">
<form action="{{ route('properties.update', $property) }}" method="POST">
@csrf @method('PUT')

<h6 class="fw-semibold mb-3 pb-2 border-bottom">Basic Information</h6>
<div class="row g-3 mb-4">
    <div class="col-12">
        <label class="form-label fw-semibold">Property Name / Unit</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $property->name) }}" required>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Address</label>
        <input type="text" name="address" class="form-control" value="{{ old('address', $property->address) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">City</label>
        <input type="text" name="city" class="form-control" value="{{ old('city', $property->city) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">State</label>
        <select name="state" class="form-select" required>
            @foreach(['Kuala Lumpur','Selangor','Penang','Johor','Sabah','Sarawak','Melaka','Negeri Sembilan','Perak','Kedah','Kelantan','Terengganu','Pahang','Perlis','Putrajaya','Labuan'] as $state)
            <option value="{{ $state }}" {{ old('state', $property->state) === $state ? 'selected' : '' }}>{{ $state }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Postcode</label>
        <input type="text" name="postcode" class="form-control" value="{{ old('postcode', $property->postcode) }}" maxlength="10" required>
    </div>
</div>

<h6 class="fw-semibold mb-3 pb-2 border-bottom">Property Specs</h6>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <label class="form-label fw-semibold">Property Type</label>
        <select name="property_type" class="form-select" required>
            @foreach(['condo'=>'Condominium','serviced_apartment'=>'Serviced Apartment','landed'=>'Landed','commercial'=>'Commercial','soho'=>'SOHO','sofo'=>'SOFO'] as $val=>$label)
            <option value="{{ $val }}" {{ old('property_type', $property->property_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Bedrooms</label>
        <input type="number" name="bedrooms" class="form-control" value="{{ old('bedrooms', $property->bedrooms) }}" min="0" required>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Bathrooms</label>
        <input type="number" name="bathrooms" class="form-control" value="{{ old('bathrooms', $property->bathrooms) }}" min="0" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Building / Strata Name</label>
        <input type="text" name="strata_building" class="form-control" value="{{ old('strata_building', $property->strata_building) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Area (sqft)</label>
        <input type="number" name="area_sqft" class="form-control" value="{{ old('area_sqft', $property->area_sqft) }}" step="0.01" min="0">
    </div>
</div>

<h6 class="fw-semibold mb-3 pb-2 border-bottom">Status & Strategy</h6>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <label class="form-label fw-semibold">Listing Status</label>
        <select name="listing_status" class="form-select" required>
            <option value="active"      {{ old('listing_status', $property->listing_status) === 'active'      ? 'selected' : '' }}>Active</option>
            <option value="inactive"    {{ old('listing_status', $property->listing_status) === 'inactive'    ? 'selected' : '' }}>Inactive</option>
            <option value="pending"     {{ old('listing_status', $property->listing_status) === 'pending'     ? 'selected' : '' }}>Pending</option>
            <option value="maintenance" {{ old('listing_status', $property->listing_status) === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Strategy Mode</label>
        <select name="strategy_mode" class="form-select" required>
            <option value="STR"       {{ old('strategy_mode', $property->strategy_mode) === 'STR'       ? 'selected' : '' }}>STR (Short-Term)</option>
            <option value="MID_TERM"  {{ old('strategy_mode', $property->strategy_mode) === 'MID_TERM'  ? 'selected' : '' }}>Mid-Term (30–90d)</option>
            <option value="SUBLET"    {{ old('strategy_mode', $property->strategy_mode) === 'SUBLET'    ? 'selected' : '' }}>Room Sublet</option>
            <option value="CORPORATE" {{ old('strategy_mode', $property->strategy_mode) === 'CORPORATE' ? 'selected' : '' }}>Corporate Lease</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Assigned Agent</label>
        <select name="agent_id" class="form-select">
            <option value="">No agent</option>
            @foreach($agents as $agent)
            <option value="{{ $agent->id }}" {{ old('agent_id', $property->agent_id) == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Owner Name</label>
        <input type="text" name="owner_name" class="form-control" value="{{ old('owner_name', $property->owner_name) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Monthly Target (RM)</label>
        <input type="number" name="monthly_target" class="form-control" value="{{ old('monthly_target', $property->monthly_target) }}" min="0">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Airbnb URL</label>
        <input type="url" name="airbnb_url" class="form-control" value="{{ old('airbnb_url', $property->airbnb_url) }}">
    </div>
</div>

<div class="d-flex gap-2 justify-content-end">
    <a href="{{ route('properties.show', $property) }}" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
</div>

</form>
</div>
</div>
</div>
@endsection
