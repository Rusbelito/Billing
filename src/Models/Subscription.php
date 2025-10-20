<?php

namespace Rusbelito\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Subscription extends Model
{

    protected $fillable = [
        'user_id',
        'plan_id',
        'billing_mode',
        'status',
        'starts_at',
        'ends_at',
        'meta',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
