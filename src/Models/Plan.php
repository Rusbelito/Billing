<?php

namespace Rusbelito\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'price',
        'is_active',
        'is_visible',
    ];


    public function usagePrices()
    {
        return $this->hasMany(UsagePrice::class);
    }


}
