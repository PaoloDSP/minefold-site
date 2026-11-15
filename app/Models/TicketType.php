<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketType extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'perks' => 'array',
            'is_visible' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function priceText(): string
    {
        if ($this->price_label) {
            return $this->price_label;
        }
        if ($this->price_cents === null) {
            return 'Prix à venir';
        }
        if ($this->price_cents === 0) {
            return 'Gratuit';
        }

        return number_format($this->price_cents / 100, $this->price_cents % 100 ? 2 : 0, ',', ' ').' €';
    }

    /** Places left: HelloAsso figures when synced, else null (unknown). */
    public function remaining(): ?int
    {
        $max = $this->max_entries ?: $this->capacity;
        if (! $max) {
            return null;
        }

        return max(0, $max - (int) $this->entries_taken);
    }

    public function isSoldOut(): bool
    {
        return $this->remaining() === 0;
    }

    public function availabilityText(): ?string
    {
        $left = $this->remaining();
        if ($left === null) {
            return $this->capacity_label;
        }
        if ($left === 0) {
            return 'Complet';
        }

        return $left.' '.($left > 1 ? 'places restantes' : 'place restante');
    }

    public function checkoutUrl(): ?string
    {
        return $this->checkout_url ?: $this->event?->checkoutUrl();
    }
}
