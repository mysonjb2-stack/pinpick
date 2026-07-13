<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedPlace extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'shared_collection_id',
        'display_name',
        'original_place_name',
        'address',
        'building_name',
        'detail_location',
        'phone',
        'opening_hours',
        'latitude',
        'longitude',
        'category_label',
        'memo',
        'thumbnail_url',
        'external_place_id',
        'is_overseas',
        'sort_order',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'sort_order' => 'integer',
        'is_overseas' => 'boolean',
        'opening_hours' => 'array',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(SharedCollection::class, 'shared_collection_id');
    }
}
