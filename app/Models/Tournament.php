<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tournament extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['phases' => 'array'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
