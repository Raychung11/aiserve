<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'Your account is disabled.']);
        }

        ActivityLog::record('auth.login', 'User logged in');

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'phone'        => 'required|string|max:20',
            'password'     => 'required|string|min:8|confirmed',
            'plan'         => 'required|in:starter,growth,enterprise',
        ]);

        $tenant = Tenant::create([
            'name'          => $data['company_name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'],
            'plan'          => $data['plan'],
            'status'        => 'trial',
            'max_properties'=> $this->planLimit($data['plan']),
            'trial_ends_at' => now()->addDays(14),
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'],
            'password'  => $data['password'],
            'role'      => 'admin',
        ]);

        Auth::login($user);

        ActivityLog::record('auth.register', "New tenant registered: {$tenant->name}");

        return redirect()->route('dashboard')
            ->with('success', "Welcome to STRHub AI! Your 14-day trial has started.");
    }

    public function logout(Request $request)
    {
        ActivityLog::record('auth.logout', 'User logged out');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function planLimit(string $plan): int
    {
        return match ($plan) {
            'starter'    => 5,
            'growth'     => 20,
            'enterprise' => 9999,
            default      => 5,
        };
    }
}
