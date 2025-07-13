<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockAmountDishes extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $guarded = ['id'];   
}
