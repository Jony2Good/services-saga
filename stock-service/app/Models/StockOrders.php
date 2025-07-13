<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOrders extends Model
{    
    protected $guarded = ['id'];
    
     public function orderDishes(): HasMany
    {
        return $this->hasMany(StockOrderDishes::class,  'stock_order_id');
    }
}
