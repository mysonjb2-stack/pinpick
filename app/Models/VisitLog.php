<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'ip', 'path', 'user_agent', 'visited_date'];

    protected function casts(): array
    {
        return [
            'visited_date' => 'date',
            'created_at' => 'datetime',
        ];
    }
}
