@extends('layouts.app')

@section('title', 'Leaderboard')
@section('page-title', 'Agent Leaderboard')
@section('page-subtitle', 'Top performers this month')

@section('content')
<div class="stat-card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th style="font-size:.8rem;width:50px;">#</th>
                <th style="font-size:.8rem;">Agent</th>
                <th style="font-size:.8rem;">Tier</th>
                <th style="font-size:.8rem;">Properties</th>
                <th style="font-size:.8rem;">Total Earned</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($agents as $i => $agent)
            <tr {{ $i === 0 ? 'class=table-warning' : '' }}>
                <td class="fw-bold" style="font-size:.9rem;">
                    @if($i === 0) 🥇
                    @elseif($i === 1) 🥈
                    @elseif($i === 2) 🥉
                    @else {{ $i + 1 }}
                    @endif
                </td>
                <td>
                    <div class="fw-semibold" style="font-size:.875rem;">{{ $agent->name }}</div>
                    <div class="text-muted" style="font-size:.75rem;"><code>{{ $agent->agent_code }}</code></div>
                </td>
                <td><span class="badge" style="background:#ede9fe;color:#5b21b6;font-size:.7rem;">{{ $agent->commission_tier }}%</span></td>
                <td style="font-size:.875rem;">{{ $agent->property_count }}</td>
                <td class="fw-bold text-success" style="font-size:.875rem;">RM {{ number_format($agent->total_earned, 0) }}</td>
                <td><a href="{{ route('agents.show', $agent) }}" class="btn btn-sm btn-outline-primary">View</a></td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No agents yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
