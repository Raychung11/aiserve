<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name', 'address', 'city', 'state', 'postcode',
        'property_type', 'strata_building', 'is_strata',
        'bedrooms', 'bathrooms', 'area_sqft',
        'compliance_status', 'compliance_notes', 'strategy_mode', 'listing_status',
        'agent_id', 'owner_name', 'owner_phone', 'owner_email',
        'monthly_target', 'airbnb_url', 'booking_url', 'notes',
    ];

    protected $casts = [
        'is_strata' => 'boolean',
        'area_sqft' => 'decimal:2',
        'monthly_target' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function investment(): HasOne
    {
        return $this->hasOne(Investment::class);
    }

    public function revenueEntries(): HasMany
    {
        return $this->hasMany(RevenueEntry::class);
    }

    public function tenancies(): HasMany
    {
        return $this->hasMany(Tenancy::class);
    }

    public function activeTenancy(): HasOne
    {
        return $this->hasOne(Tenancy::class)->where('status', 'active')->latestOfMany();
    }

    public function complianceBadgeClass(): string
    {
        return match ($this->compliance_status) {
            'green' => 'badge-success',
            'amber' => 'badge-warning',
            'red'   => 'badge-danger',
            default => 'badge-secondary',
        };
    }

    public function currentMonthRevenue(string $period = null): float
    {
        $period ??= now()->format('Y-m');
        return (float) $this->revenueEntries()
            ->where('period', $period)
            ->where('type', 'income')
            ->sum('amount');
    }

    public function currentMonthExpenses(string $period = null): float
    {
        $period ??= now()->format('Y-m');
        return (float) $this->revenueEntries()
            ->where('period', $period)
            ->where('type', 'expense')
            ->sum('amount');
    }

    public function currentMonthProfit(string $period = null): float
    {
        return $this->currentMonthRevenue($period) - $this->currentMonthExpenses($period);
    }
}
