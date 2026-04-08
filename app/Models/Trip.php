<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Trip extends Model
{
    protected $fillable = ['user_id', 'name', 'start_date', 'end_date'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function places(): BelongsToMany
    {
        return $this->belongsToMany(Place::class, 'trip_places')
            ->withPivot(['day_number', 'sort_order'])
            ->withTimestamps();
    }
}
