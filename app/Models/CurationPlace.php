<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurationPlace extends Model
{
    protected $fillable = [
        'curation_id', 'place_name', 'address', 'latitude', 'longitude',
        'category_label', 'thumbnail_url', 'photos', 'external_place_id', 'is_overseas',
        'source_channel', 'source_url', 'source_date', 'day_number',
        'sort_order', 'editor_note', 'phone', 'opening_hours',
        'building_name', 'naver_place_id', 'google_place_id',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'sort_order' => 'integer',
        'day_number' => 'integer',
        'is_overseas' => 'boolean',
        'opening_hours' => 'array',
        'photos' => 'array',
        'source_date' => 'date',
    ];

    public function getFirstPhotoUrlAttribute(): ?string
    {
        $photos = $this->photos;
        if (!$photos || empty($photos)) return $this->thumbnail_url;
        return asset('storage/' . $photos[0]);
    }

    public function getThumbUrlAttribute(): ?string
    {
        $photos = $this->photos;
        if (!$photos || empty($photos)) return $this->thumbnail_url;
        $thumbPath = \App\Services\ImageProcessor::thumbPathFor($photos[0]);
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($thumbPath)) {
            return asset('storage/' . $thumbPath);
        }
        return asset('storage/' . $photos[0]);
    }

    public function curation(): BelongsTo
    {
        return $this->belongsTo(Curation::class);
    }
}
