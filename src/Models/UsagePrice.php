<?php

namespace Rusbelito\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Rusbelito\Billing\Models\Plan;

class UsagePrice extends Model
{
    protected $fillable = [
        'plan_id',
        'action_key',
        'unit_count',
        'unit_price',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
