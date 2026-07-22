<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Curation extends Model
{
    protected $fillable = [
        'title', 'slug', 'type', 'category', 'description', 'cover_image',
        'region_label', 'status', 'published_at',
        'view_count', 'save_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'view_count' => 'integer',
        'save_count' => 'integer',
    ];

    public function places(): HasMany
    {
        return $this->hasMany(CurationPlace::class)->orderBy('sort_order');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public static function generateSlug(string $title): string
    {
        $base = Str::slug($title) ?: Str::random(8);
        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
