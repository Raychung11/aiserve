<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'tenant_id', 'user_id', 'action', 'model_type', 'model_id',
        'description', 'meta', 'ip_address',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        string $action,
        string $description = '',
        ?Model $model = null,
        array $meta = []
    ): self {
        return self::create([
            'tenant_id'   => auth()->user()?->tenant_id,
            'user_id'     => auth()->id(),
            'action'      => $action,
            'model_type'  => $model ? get_class($model) : null,
            'model_id'    => $model?->getKey(),
            'description' => $description,
            'meta'        => $meta,
            'ip_address'  => request()->ip(),
        ]);
    }
}
