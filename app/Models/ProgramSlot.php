<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ProgramSlot extends Model
{
    public const DAYS = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];

    public const CATEGORIES = ['live' => 'Direct', 'special' => 'Spécial / événement', 'rerun' => 'Rediffusion'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'weekday' => 'integer'];
    }

    /** @return Collection<int, array{name:string, today:bool, slots:Collection}> */
    public static function week(): Collection
    {
        $slots = static::where('is_active', true)->orderBy('starts_at')->orderBy('sort')->get()->groupBy('weekday');
        $today = now()->dayOfWeekIso;

        return collect(self::DAYS)->map(fn ($name, $day) => [
            'name' => $name,
            'today' => $day === $today,
            'slots' => $slots->get($day, collect()),
        ]);
    }
}
