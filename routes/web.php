<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TenancyController;
use Illuminate\Support\Facades\Route;

// --- Auth ---
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// --- Billplz Webhook (no auth, no tenant) ---
Route::post('/billplz/callback',  [SubscriptionController::class, 'callback'])->name('billplz.callback');
Route::get('/billplz/redirect',   [SubscriptionController::class, 'redirectCallback'])->name('billplz.redirect');

// --- Authenticated + Tenant-scoped ---
Route::middleware(['auth', 'tenant'])->group(function () {

    // Dashboard
    Route::get('/',          [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Properties
    Route::resource('properties', PropertyController::class);
    Route::post('/properties/{property}/apply-strategy', [PropertyController::class, 'applyStrategy'])
        ->name('properties.apply-strategy');

    // Investments
    Route::get('/properties/{property}/investment',  [InvestmentController::class, 'show'])->name('investments.show');
    Route::post('/properties/{property}/investment', [InvestmentController::class, 'store'])->name('investments.store');
    Route::get('/roi-calculator', [InvestmentController::class, 'roiCalculator'])->name('roi.calculator');
    Route::post('/roi-calculator', [InvestmentController::class, 'roiCalculator']);

    // Revenue & Expenses
    Route::get('/revenue',                    [RevenueController::class, 'index'])->name('revenue.index');
    Route::get('/revenue/create',             [RevenueController::class, 'create'])->name('revenue.create');
    Route::post('/revenue',                   [RevenueController::class, 'store'])->name('revenue.store');
    Route::delete('/revenue/{revenueEntry}',  [RevenueController::class, 'destroy'])->name('revenue.destroy');
    Route::get('/revenue/report',             [RevenueController::class, 'report'])->name('revenue.report');

    // Tenancies
    Route::resource('tenancies', TenancyController::class);

    // Agents
    Route::get('/agents',                 [AgentController::class, 'index'])->name('agents.index');
    Route::get('/agents/create',          [AgentController::class, 'create'])->name('agents.create');
    Route::post('/agents',                [AgentController::class, 'store'])->name('agents.store');
    Route::get('/agents/leaderboard',     [AgentController::class, 'leaderboard'])->name('agents.leaderboard');
    Route::get('/agents/{agent}',         [AgentController::class, 'show'])->name('agents.show');
    Route::post('/agents/{agent}/pay',    [AgentController::class, 'payCommission'])->name('agents.pay');

    // Subscription
    Route::get('/subscription',           [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::get('/subscription/callback',  [SubscriptionController::class, 'redirectCallback'])->name('subscription.callback');
});
