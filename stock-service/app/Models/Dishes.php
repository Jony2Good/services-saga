<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dishes extends Model
{
    protected $table = "dishes";

    public $timestamps = false;
    protected $guarded = ['id'];    
}
