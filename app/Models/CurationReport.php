<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurationReport extends Model
{
    protected $fillable = [
        'curation_id', 'reporter_user_id', 'reason', 'detail',
        'status', 'result', 'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    public function curation(): BelongsTo
    {
        return $this->belongsTo(Curation::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }
}
