<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Replay extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['published_on' => 'date', 'is_visible' => 'boolean'];
    }
}
