<?php

namespace App\Models;

use App\Services\ImageProcessor;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaceImage extends Model
{
    protected $fillable = ['place_id', 'path', 'sort_order'];

    public function place(): BelongsTo { return $this->belongsTo(Place::class); }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => asset('storage/' . $this->path));
    }

    protected function thumbPath(): Attribute
    {
        return Attribute::get(fn () => ImageProcessor::thumbPathFor($this->path));
    }

    protected function thumbUrl(): Attribute
    {
        return Attribute::get(fn () => asset('storage/' . ImageProcessor::thumbPathFor($this->path)));
    }
}
