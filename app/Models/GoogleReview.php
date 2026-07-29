<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleReview extends Model
{
    protected $fillable = [
        'google_place_id',
        'author_name',
        'profile_photo_url',
        'rating',
        'text',
        'review_time',
    ];

    protected $casts = [
        'review_time' => 'datetime',
    ];
}
