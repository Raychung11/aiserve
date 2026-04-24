@extends('layouts.app')

@section('title', 'Agents')
@section('page-title', 'Agent Network')
@section('page-subtitle', 'Manage your referral team and commissions')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex gap-2">
        <a href="{{ route('agents.leaderboard') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-trophy me-1"></i>Leaderboard</a>
    </div>
    <a href="{{ route('agents.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Agent</a>
</div>

<div class="row g-3">
    @forelse($agents as $agent)
    <div class="col-md-6 col-xl-4">
        <div class="stat-card card-hover">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width:40px;height:40px;background:#6366f1;font-size:.9rem;">
                        {{ strtoupper(substr($agent->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="fw-semibold">{{ $agent->name }}</div>
                        <div class="text-muted" style="font-size:.75rem;">{{ $agent->email }}</div>
                    </div>
                </div>
                <span class="badge bg-indigo-100 text-indigo-700 border" style="font-size:.7rem;background:#ede9fe;color:#5b21b6;">{{ $agent->commission_tier }}%</span>
            </div>

            <div class="row g-2 text-center mb-3">
                <div class="col-4">
                    <div class="text-muted" style="font-size:.7rem;">Properties</div>
                    <div class="fw-bold">{{ $agent->properties_count }}</div>
                </div>
                <div class="col-4">
                    <div class="text-muted" style="font-size:.7rem;">Pending</div>
                    <div class="fw-bold text-warning">RM {{ number_format($agent->pending_commission, 0) }}</div>
                </div>
                <div class="col-4">
                    <div class="text-muted" style="font-size:.7rem;">Earned</div>
                    <div class="fw-bold text-success">RM {{ number_format($agent->total_earned, 0) }}</div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <div class="text-muted" style="font-size:.75rem;"><i class="bi bi-qr-code"></i> {{ $agent->agent_code }}</div>
                <a href="{{ route('agents.show', $agent) }}" class="btn btn-sm btn-outline-primary ms-auto">View</a>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="text-center py-5 stat-card">
            <i class="bi bi-person-badge" style="font-size:3rem;color:#cbd5e1;"></i>
            <h5 class="mt-3 text-muted">No agents yet</h5>
            <a href="{{ route('agents.create') }}" class="btn btn-primary mt-2">Add your first agent</a>
        </div>
    </div>
    @endforelse
</div>

@endsection
