@extends('layouts.app')

@section('title', 'Subscription')
@section('page-title', 'Subscription')
@section('page-subtitle', 'Manage your STRHub AI plan')

@section('content')

{{-- Current Status --}}
<div class="stat-card mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <div class="text-muted" style="font-size:.8rem;">Current Plan</div>
            <div class="fw-bold" style="font-size:1.4rem;">{{ ucfirst($tenant->plan) }}</div>
            <div class="d-flex gap-2 mt-1">
                @php $sc = match($tenant->status) { 'active'=>'success','trial'=>'info','suspended'=>'danger','cancelled'=>'secondary',default=>'secondary' }; @endphp
                <span class="badge bg-{{ $sc }} bg-opacity-15 text-{{ $sc }}">{{ ucfirst($tenant->status) }}</span>
                @if($tenant->status === 'trial' && $tenant->trial_ends_at)
                    <span class="badge bg-warning bg-opacity-15 text-warning">Trial ends {{ $tenant->trial_ends_at->format('d M Y') }}</span>
                @endif
                @if($tenant->subscription_ends_at)
                    <span class="text-muted" style="font-size:.75rem;">Renews {{ $tenant->subscription_ends_at->format('d M Y') }}</span>
                @endif
            </div>
        </div>
        <div class="text-end">
            <div class="text-muted" style="font-size:.75rem;">Properties Used</div>
            <div class="fw-bold" style="font-size:1.2rem;">{{ $tenant->properties()->count() }} / {{ $tenant->max_properties === 9999 ? '∞' : $tenant->max_properties }}</div>
        </div>
    </div>
</div>

{{-- Plan Cards --}}
<h6 class="fw-semibold mb-3">Upgrade Your Plan</h6>
<div class="row g-3 mb-4">
    @foreach($plans as $key => $plan)
    <div class="col-md-4">
        <div class="stat-card card-hover h-100 {{ $tenant->plan === $key ? 'border-primary' : '' }}" style="{{ $tenant->plan === $key ? 'border:2px solid #6366f1;' : '' }}">
            @if($key === 'growth')
            <div class="text-center mb-2">
                <span class="badge" style="background:#6366f1;color:#fff;font-size:.7rem;">Most Popular</span>
            </div>
            @endif

            <div class="text-center mb-3">
                <h5 class="fw-bold mb-1">{{ $plan['name'] }}</h5>
                <div class="text-muted" style="font-size:.8rem;">Up to {{ $plan['properties'] === 9999 ? 'Unlimited' : $plan['properties'] }} properties</div>
            </div>

            <div class="text-center mb-4">
                <div class="fw-bold" style="font-size:2rem;">RM {{ number_format($plan['monthly']) }}</div>
                <div class="text-muted" style="font-size:.75rem;">/month</div>
                <div class="text-muted mt-1" style="font-size:.75rem;">or RM {{ number_format($plan['annually']) }}/year (save {{ round((1 - $plan['annually'] / ($plan['monthly'] * 12)) * 100) }}%)</div>
            </div>

            <ul class="list-unstyled mb-4" style="font-size:.85rem;">
                @foreach($plan['features'] as $feature)
                <li class="mb-1"><i class="bi bi-check-lg text-success me-2"></i>{{ $feature }}</li>
                @endforeach
            </ul>

            @if($tenant->plan === $key && $tenant->status === 'active')
                <button class="btn btn-outline-secondary w-100" disabled>Current Plan</button>
            @else
                <form action="{{ route('subscription.checkout') }}" method="POST">
                    @csrf
                    <input type="hidden" name="plan" value="{{ $key }}">
                    <div class="d-flex gap-2 mb-2">
                        <label class="flex-grow-1 border rounded-3 px-2 py-1 text-center" style="cursor:pointer;font-size:.78rem;">
                            <input type="radio" name="billing_cycle" value="monthly" checked class="me-1"> Monthly
                        </label>
                        <label class="flex-grow-1 border rounded-3 px-2 py-1 text-center" style="cursor:pointer;font-size:.78rem;">
                            <input type="radio" name="billing_cycle" value="annually" class="me-1"> Annual
                        </label>
                    </div>
                    <button type="submit" class="btn w-100 {{ $key === 'growth' ? 'btn-primary' : 'btn-outline-primary' }}">
                        <i class="bi bi-credit-card me-1"></i>Subscribe via FPX
                    </button>
                </form>
            @endif
        </div>
    </div>
    @endforeach
</div>

{{-- Payment History --}}
@if($history->isNotEmpty())
<div class="stat-card">
    <h6 class="fw-semibold mb-3">Payment History</h6>
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th style="font-size:.8rem;">Plan</th>
                <th style="font-size:.8rem;">Amount</th>
                <th style="font-size:.8rem;">Cycle</th>
                <th style="font-size:.8rem;">Status</th>
                <th style="font-size:.8rem;">Date</th>
                <th style="font-size:.8rem;"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($history as $sub)
            <tr>
                <td style="font-size:.875rem;">{{ ucfirst($sub->plan) }}</td>
                <td style="font-size:.875rem;">RM {{ number_format($sub->amount, 2) }}</td>
                <td class="text-muted" style="font-size:.8rem;">{{ ucfirst($sub->billing_cycle) }}</td>
                <td>
                    @php $sc = match($sub->status) { 'active'=>'success','pending'=>'warning','expired'=>'secondary','cancelled'=>'danger',default=>'secondary' }; @endphp
                    <span class="badge bg-{{ $sc }} bg-opacity-15 text-{{ $sc }}" style="font-size:.7rem;">{{ ucfirst($sub->status) }}</span>
                </td>
                <td class="text-muted" style="font-size:.78rem;">{{ $sub->created_at->format('d M Y') }}</td>
                <td>
                    @if($sub->billplz_url && $sub->status === 'pending')
                        <a href="{{ $sub->billplz_url }}" target="_blank" class="btn btn-xs btn-outline-primary btn-sm" style="font-size:.7rem;">Pay Now</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="mt-4 p-3 rounded-3 border" style="background:#f8fafc;font-size:.8rem;color:#64748b;">
    <i class="bi bi-shield-lock me-2"></i>
    Payments are processed securely via <strong>Billplz FPX</strong>. STRHub AI does not store your banking credentials.
    For billing enquiries, contact <strong>support@strhub.my</strong>.
</div>

@endsection
