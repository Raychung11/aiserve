<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'STRHub AI') — STRHub AI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --sidebar-bg: #0f172a;
            --sidebar-width: 260px;
            --accent: #6366f1;
            --accent-hover: #4f46e5;
        }
        body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; }
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            min-height: 100vh;
            position: fixed;
            top: 0; left: 0;
            z-index: 100;
            overflow-y: auto;
        }
        .sidebar-brand {
            padding: 1.5rem 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .sidebar-brand h5 { color: #fff; font-weight: 700; margin: 0; font-size: 1.1rem; }
        .sidebar-brand small { color: #94a3b8; font-size: .72rem; }
        .sidebar-nav { padding: .75rem 0; }
        .sidebar-nav .nav-section {
            color: #64748b;
            font-size: .65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .08em;
            padding: .75rem 1.25rem .25rem;
        }
        .sidebar-nav .nav-item { margin: .15rem .5rem; }
        .sidebar-nav .nav-link {
            color: #cbd5e1;
            border-radius: 8px;
            padding: .55rem .85rem;
            font-size: .875rem;
            display: flex;
            align-items: center;
            gap: .6rem;
            transition: background .15s, color .15s;
        }
        .sidebar-nav .nav-link:hover, .sidebar-nav .nav-link.active {
            background: rgba(99,102,241,.18);
            color: #fff;
        }
        .sidebar-nav .nav-link.active { color: #a5b4fc; }
        .sidebar-nav .nav-link i { font-size: 1rem; width: 1.1rem; }
        .main-wrapper { margin-left: var(--sidebar-width); min-height: 100vh; }
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: .75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .page-content { padding: 1.75rem 1.5rem; }
        .stat-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 1.25rem; }
        .stat-card .stat-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        .stat-card .stat-value { font-size: 1.6rem; font-weight: 700; color: #0f172a; }
        .stat-card .stat-label { color: #64748b; font-size: .8rem; }
        .compliance-green  { color: #16a34a; }
        .compliance-amber  { color: #d97706; }
        .compliance-red    { color: #dc2626; }
        .badge-green  { background: #dcfce7; color: #15803d; }
        .badge-amber  { background: #fef9c3; color: #b45309; }
        .badge-red    { background: #fee2e2; color: #b91c1c; }
        .badge-str    { background: #dbeafe; color: #1e40af; }
        .badge-mid    { background: #ede9fe; color: #5b21b6; }
        .badge-sub    { background: #fce7f3; color: #9d174d; }
        .badge-corp   { background: #f0fdf4; color: #166534; }
        .card-hover { transition: box-shadow .2s; }
        .card-hover:hover { box-shadow: 0 4px 20px rgba(0,0,0,.08); }
        .btn-primary { background: var(--accent); border-color: var(--accent); }
        .btn-primary:hover { background: var(--accent-hover); border-color: var(--accent-hover); }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-wrapper { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-brand">
            <h5><i class="bi bi-house-heart-fill" style="color:#6366f1"></i> STRHub AI</h5>
            <small>{{ auth()->user()->tenant->name ?? 'Platform' }}</small>
        </div>

        <ul class="sidebar-nav list-unstyled mb-0">
            <li class="nav-section">Overview</li>
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
            </li>

            <li class="nav-section">Portfolio</li>
            <li class="nav-item">
                <a href="{{ route('properties.index') }}" class="nav-link {{ request()->routeIs('properties.*') ? 'active' : '' }}">
                    <i class="bi bi-buildings"></i> Properties
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('tenancies.index') }}" class="nav-link {{ request()->routeIs('tenancies.*') ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i> Tenancies
                </a>
            </li>

            <li class="nav-section">Finance</li>
            <li class="nav-item">
                <a href="{{ route('revenue.index') }}" class="nav-link {{ request()->routeIs('revenue.*') ? 'active' : '' }}">
                    <i class="bi bi-cash-stack"></i> Revenue & Expenses
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('roi.calculator') }}" class="nav-link {{ request()->routeIs('roi.*') ? 'active' : '' }}">
                    <i class="bi bi-calculator-fill"></i> ROI Calculator
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('revenue.report') }}" class="nav-link">
                    <i class="bi bi-bar-chart-fill"></i> Annual Report
                </a>
            </li>

            <li class="nav-section">Network</li>
            <li class="nav-item">
                <a href="{{ route('agents.index') }}" class="nav-link {{ request()->routeIs('agents.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge-fill"></i> Agents
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('agents.leaderboard') }}" class="nav-link">
                    <i class="bi bi-trophy-fill"></i> Leaderboard
                </a>
            </li>

            <li class="nav-section">Account</li>
            <li class="nav-item">
                <a href="{{ route('subscription.index') }}" class="nav-link {{ request()->routeIs('subscription.*') ? 'active' : '' }}">
                    <i class="bi bi-credit-card-fill"></i> Subscription
                </a>
            </li>
        </ul>

        <div class="p-3 mt-auto border-top" style="border-color:rgba(255,255,255,.08)!important; position:absolute; bottom:0; width:100%;">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="rounded-circle bg-indigo-600 d-flex align-items-center justify-content-center text-white fw-bold" style="width:32px;height:32px;background:var(--accent);font-size:.8rem;">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div>
                    <div class="text-white" style="font-size:.8rem;line-height:1.2">{{ auth()->user()->name }}</div>
                    <div style="color:#64748b;font-size:.7rem;">{{ ucfirst(auth()->user()->role) }}</div>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm w-100" style="background:rgba(255,255,255,.06);color:#94a3b8;border:1px solid rgba(255,255,255,.1);">
                    <i class="bi bi-box-arrow-left"></i> Sign Out
                </button>
            </form>
        </div>
    </nav>

    <!-- Main -->
    <div class="main-wrapper flex-grow-1">
        <div class="topbar d-flex align-items-center justify-content-between">
            <div>
                <h6 class="mb-0 fw-semibold text-dark">@yield('page-title', 'Dashboard')</h6>
                <small class="text-muted">@yield('page-subtitle', '')</small>
            </div>
            <div class="d-flex align-items-center gap-3">
                @if(auth()->user()->tenant && auth()->user()->tenant->status === 'trial')
                    @php $daysLeft = now()->diffInDays(auth()->user()->tenant->trial_ends_at, false); @endphp
                    @if($daysLeft >= 0)
                    <a href="{{ route('subscription.index') }}" class="badge text-decoration-none" style="background:#fef3c7;color:#92400e;font-size:.75rem;padding:.4rem .75rem;border-radius:20px;">
                        <i class="bi bi-clock"></i> Trial: {{ $daysLeft }} days left
                    </a>
                    @endif
                @endif
                <span class="text-muted" style="font-size:.8rem;">{{ now()->format('d M Y') }}</span>
            </div>
        </div>

        <div class="page-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i>{{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@stack('scripts')
</body>
</html>
