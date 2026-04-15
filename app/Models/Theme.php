<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Theme extends Model
{
    protected $fillable = ['name', 'slug', 'sort_order'];

    public function places(): BelongsToMany
    {
        return $this->belongsToMany(Place::class, 'place_themes');
    }
}
