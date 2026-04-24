@extends('layouts.app')

@section('title', 'Add Agent')
@section('page-title', 'Add Agent')
@section('page-subtitle', 'Invite a new agent to your network')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-6">
<div class="stat-card">
<form action="{{ route('agents.store') }}" method="POST">
@csrf
<div class="row g-3">
    <div class="col-12">
        <label class="form-label fw-semibold">Full Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Commission Tier</label>
        <div class="row g-2">
            @foreach([['5','5%','Base tier for new agents'],['7','7%','Mid tier – 10+ properties'],['10','10%','Top tier – high performers']] as [$val,$label,$desc])
            <div class="col-md-4">
                <label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2" style="cursor:pointer;">
                    <input type="radio" name="commission_tier" value="{{ $val }}" {{ old('commission_tier', '5') === $val ? 'checked' : '' }}>
                    <div>
                        <div class="fw-semibold">{{ $label }}</div>
                        <div class="text-muted" style="font-size:.7rem;">{{ $desc }}</div>
                    </div>
                </label>
            </div>
            @endforeach
        </div>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Password</label>
        <input type="password" name="password" class="form-control" required minlength="8">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Confirm Password</label>
        <input type="password" name="password_confirmation" class="form-control" required>
    </div>
    <div class="col-12 d-flex gap-2 justify-content-end">
        <a href="{{ route('agents.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary px-4">Create Agent</button>
    </div>
</div>
</form>
</div>
</div>
</div>
@endsection
