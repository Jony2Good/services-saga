<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = "orders";

    protected $guarded = ['id'];

    public function orderDishes(): HasMany
    {
        return $this->hasMany(OrderDishes::class, 'order_id');
    }

    public function updateStatus(int $status): void
    {
        $this->status = $status;
        $this->save();
    }
}
