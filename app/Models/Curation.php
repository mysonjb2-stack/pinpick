<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Curation extends Model
{
    protected $fillable = [
        'title', 'slug', 'type', 'nights', 'days', 'category', 'description', 'cover_image',
        'region_label', 'status', 'published_at',
        'view_count', 'save_count',
        'author_type', 'author_user_id',
        'rejected_reason', 'approved_snapshot',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'view_count' => 'integer',
        'save_count' => 'integer',
        'nights' => 'integer',
        'days' => 'integer',
        'approved_snapshot' => 'array',
    ];

    public function getDurationLabelAttribute(): ?string
    {
        if ($this->days === null) return null;
        $n = $this->nights ?? 0;
        if ($n === 0 && $this->days === 1) return '당일치기';
        if ($n === 0) return "무박 {$this->days}일";
        return "{$n}박 {$this->days}일";
    }

    public function places(): HasMany
    {
        return $this->hasMany(CurationPlace::class)->orderBy('sort_order');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CurationReport::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('author_type', 'user')->where('author_user_id', $userId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function isVisiblePublicly(): bool
    {
        return $this->status === 'approved'
            || ($this->status === 'pending' && $this->approved_snapshot !== null);
    }

    public function getPublicTitle(): string
    {
        if ($this->status === 'pending' && $this->approved_snapshot) {
            return $this->approved_snapshot['title'] ?? $this->title;
        }
        return $this->title;
    }

    public function getPublicDescription(): ?string
    {
        if ($this->status === 'pending' && $this->approved_snapshot) {
            return $this->approved_snapshot['description'] ?? $this->description;
        }
        return $this->description;
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
