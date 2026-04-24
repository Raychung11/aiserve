<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->tenant_id) {
            abort(403, 'No tenant context.');
        }

        $tenant = $user->tenant;

        if (!$tenant || !$tenant->isActive()) {
            return redirect()->route('subscription.index')
                ->with('error', 'Your subscription is inactive. Please renew to continue.');
        }

        app()->instance('tenant', $tenant);
        app()->instance('tenant_id', $tenant->id);

        return $next($request);
    }
}
