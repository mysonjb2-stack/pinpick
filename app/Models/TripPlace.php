<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripPlace extends Model
{
    protected $fillable = ['trip_id', 'place_id', 'day_number', 'sort_order'];
}
