<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingAccount extends Model
{
    protected $guarded = ['id'];

    public function orderStatus(): HasMany
    {
        return $this->hasMany(OrderStatus::class);
    }
}
