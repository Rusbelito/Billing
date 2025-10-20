<?php

namespace Rusbelito\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Usage extends Model
{
    protected $fillable = [
        'user_id',
        'action_key',
        'quantity',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'quantity' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}