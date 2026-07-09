<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SharedCollection extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'title',
        'source_category_id',
        'name_display_mode',
        'view_count',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'view_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'source_category_id');
    }

    public function places(): HasMany
    {
        return $this->hasMany(SharedPlace::class)->orderBy('sort_order');
    }
}
