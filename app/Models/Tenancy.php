<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenancy extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'property_id',
        'tenant_name', 'tenant_phone', 'tenant_email', 'tenant_ic', 'tenant_company',
        'start_date', 'end_date', 'monthly_rent', 'deposit', 'deposit_paid',
        'type', 'status', 'renewal_notified_at', 'agent_id', 'agreement_path', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_rent' => 'decimal:2',
        'deposit' => 'decimal:2',
        'deposit_paid' => 'boolean',
        'renewal_notified_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function daysUntilExpiry(): int
    {
        return (int) now()->diffInDays($this->end_date, false);
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        $daysLeft = $this->daysUntilExpiry();
        return $daysLeft >= 0 && $daysLeft <= $days;
    }

    public function totalLeaseDuration(): int
    {
        return (int) $this->start_date->diffInMonths($this->end_date);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }
}
