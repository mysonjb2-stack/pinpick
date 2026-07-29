<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GooglePlaceCache extends Model
{
    protected $table = 'google_place_cache';

    protected $fillable = [
        'google_place_id',
        'rating',
        'review_count',
        'fetched_at',
    ];

    protected $casts = [
        'fetched_at' => 'datetime',
    ];

    public function reviews(): HasMany
    {
        return $this->hasMany(GoogleReview::class, 'google_place_id', 'google_place_id');
    }
}
