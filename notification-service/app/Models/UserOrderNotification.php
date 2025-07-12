<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserOrderNotification extends Model
{
   protected $fillable = ['user_id', 'order_id', 'email', 'message', 'error'];
}
