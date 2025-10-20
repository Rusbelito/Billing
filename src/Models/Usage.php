<?php

namespace Rusbelito\Billing\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}
