<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'company_reg', 'logo_path',
        'plan', 'status', 'max_properties',
        'trial_ends_at', 'subscription_ends_at', 'settings',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'settings' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->subscriptions()->where('status', 'active')->latest()->first();
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial']);
    }

    public function canAddProperty(): bool
    {
        return $this->properties()->count() < $this->max_properties;
    }

    public function planLimits(): array
    {
        return match ($this->plan) {
            'starter'    => ['properties' => 5,   'agents' => 2,   'price' => 500],
            'growth'     => ['properties' => 20,  'agents' => 10,  'price' => 1500],
            'enterprise' => ['properties' => 9999,'agents' => 9999,'price' => 4000],
            default      => ['properties' => 5,   'agents' => 2,   'price' => 500],
        };
    }
}
