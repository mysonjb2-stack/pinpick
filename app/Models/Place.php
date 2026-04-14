<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Place extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'category_id', 'name', 'phone', 'address', 'road_address',
        'lat', 'lng', 'memo', 'status', 'visited_at',
        'naver_place_id', 'kakao_place_id', 'is_overseas', 'thumbnail', 'sort_order', 'is_visible',
    ];

    protected $casts = [
        'visited_at' => 'date',
        'is_visible' => 'boolean',
        'is_overseas' => 'boolean',
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function images(): HasMany { return $this->hasMany(PlaceImage::class)->orderBy('sort_order'); }
}
