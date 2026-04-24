<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Investment extends Model
{
    protected $fillable = [
        'tenant_id', 'property_id',
        'purchase_price', 'renovation_cost', 'setup_cost',
        'furnishing_cost', 'deposit_paid', 'legal_fees',
        'stamp_duty', 'other_costs', 'investment_date', 'notes',
    ];

    protected $casts = [
        'investment_date' => 'date',
        'purchase_price' => 'decimal:2',
        'renovation_cost' => 'decimal:2',
        'setup_cost' => 'decimal:2',
        'furnishing_cost' => 'decimal:2',
        'deposit_paid' => 'decimal:2',
        'legal_fees' => 'decimal:2',
        'stamp_duty' => 'decimal:2',
        'other_costs' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function totalInvestment(): float
    {
        return (float) (
            $this->purchase_price +
            $this->renovation_cost +
            $this->setup_cost +
            $this->furnishing_cost +
            $this->deposit_paid +
            $this->legal_fees +
            $this->stamp_duty +
            $this->other_costs
        );
    }
}
