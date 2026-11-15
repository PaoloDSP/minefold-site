<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'badges' => 'array',
            'challenge_modes' => 'array',
            'program' => 'array',
            'byoc_items' => 'array',
            'rewards' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'helloasso_synced_at' => 'datetime',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class)->orderBy('sort');
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class)->orderBy('sort');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** Next published event, featured first. */
    public static function upcoming(): ?self
    {
        return static::published()
            ->where(fn ($q) => $q->where('ends_at', '>=', now())->orWhere(fn ($q) => $q->whereNull('ends_at')->where('starts_at', '>=', now()->startOfDay())))
            ->orderByDesc('is_featured')
            ->orderBy('starts_at')
            ->first();
    }

    public function daysUntil(): int
    {
        return max(0, (int) ceil(now()->diffInSeconds($this->starts_at, false) / 86400));
    }

    public function datesLabel(): string
    {
        $start = $this->starts_at->locale('fr');
        if (! $this->ends_at || $this->ends_at->isSameDay($this->starts_at)) {
            return $start->translatedFormat('j F Y');
        }
        $end = $this->ends_at->locale('fr');
        if ($start->isSameMonth($end)) {
            return $start->format('j').' et '.$end->translatedFormat('j F Y');
        }

        return $start->translatedFormat('j F').' – '.$end->translatedFormat('j F Y');
    }

    public function checkoutUrl(): ?string
    {
        if ($this->checkout_url) {
            return $this->checkout_url;
        }
        $org = config('services.helloasso.organization');
        if ($org && $this->helloasso_form_slug) {
            return 'https://www.helloasso.com/associations/'.$org.'/evenements/'.$this->helloasso_form_slug;
        }

        return null;
    }
}
