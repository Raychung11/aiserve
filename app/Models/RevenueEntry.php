<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueEntry extends Model
{
    protected $fillable = [
        'tenant_id', 'property_id', 'period', 'type', 'category',
        'amount', 'description', 'payment_date', 'payment_ref', 'receipt_path',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function commissionLog(): BelongsTo
    {
        return $this->belongsTo(CommissionLog::class, 'id', 'revenue_entry_id');
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopePeriod($query, string $period)
    {
        return $query->where('period', $period);
    }
}
