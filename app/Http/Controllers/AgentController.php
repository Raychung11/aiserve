<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CommissionLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = app('tenant_id');

        $agents = User::where('tenant_id', $tenantId)
            ->where('role', 'agent')
            ->withCount('properties')
            ->get()
            ->map(function ($agent) {
                $agent->pending_commission = $agent->pendingCommission();
                $agent->total_earned       = CommissionLog::where('agent_id', $agent->id)
                    ->where('status', 'paid')
                    ->sum('commission_amount');
                return $agent;
            });

        return view('agents.index', compact('agents'));
    }

    public function create()
    {
        return view('agents.create');
    }

    public function store(Request $request)
    {
        $tenantId = app('tenant_id');

        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|unique:users,email',
            'phone'            => 'required|string|max:20',
            'commission_tier'  => 'required|in:5,7,10',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $agent = User::create([
            'tenant_id'       => $tenantId,
            'name'            => $data['name'],
            'email'           => $data['email'],
            'phone'           => $data['phone'],
            'password'        => $data['password'],
            'role'            => 'agent',
            'commission_tier' => $data['commission_tier'],
            'agent_code'      => strtoupper(Str::random(8)),
        ]);

        ActivityLog::record('agent.created', "Agent added: {$agent->name}", $agent);

        return redirect()->route('agents.index')
            ->with('success', "Agent {$agent->name} created. Code: {$agent->agent_code}");
    }

    public function show(User $agent)
    {
        $this->authorizeAgent($agent);

        $commissions = CommissionLog::where('agent_id', $agent->id)
            ->with('property')
            ->orderByDesc('created_at')
            ->paginate(15);

        $stats = [
            'total_properties' => $agent->properties()->count(),
            'pending'          => CommissionLog::where('agent_id', $agent->id)->where('status', 'pending')->sum('commission_amount'),
            'paid'             => CommissionLog::where('agent_id', $agent->id)->where('status', 'paid')->sum('commission_amount'),
            'total'            => CommissionLog::where('agent_id', $agent->id)->sum('commission_amount'),
        ];

        return view('agents.show', compact('agent', 'commissions', 'stats'));
    }

    public function leaderboard()
    {
        $tenantId = app('tenant_id');

        $agents = User::where('tenant_id', $tenantId)
            ->where('role', 'agent')
            ->where('is_active', true)
            ->get()
            ->map(function ($agent) {
                $agent->total_earned = (float) CommissionLog::where('agent_id', $agent->id)
                    ->where('status', 'paid')
                    ->sum('commission_amount');
                $agent->property_count = $agent->properties()->count();
                return $agent;
            })
            ->sortByDesc('total_earned')
            ->values();

        return view('agents.leaderboard', compact('agents'));
    }

    public function payCommission(Request $request, User $agent)
    {
        $this->authorizeAgent($agent);

        $request->validate(['reference' => 'nullable|string|max:100']);

        $updated = CommissionLog::where('agent_id', $agent->id)
            ->where('status', 'pending')
            ->update([
                'status'    => 'paid',
                'paid_at'   => now(),
                'reference' => $request->reference,
            ]);

        // Update agent wallet
        $agent->commission_wallet = 0;
        $agent->save();

        ActivityLog::record('commission.paid', "Commission paid to {$agent->name} ({$updated} records)");

        return back()->with('success', "Commission paid. {$updated} records settled.");
    }

    private function authorizeAgent(User $agent): void
    {
        if ($agent->tenant_id !== app('tenant_id') || $agent->role !== 'agent') {
            abort(403);
        }
    }
}
