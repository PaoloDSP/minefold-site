<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);
        $start = fake()->dateTimeBetween('+1 week', '+6 months');

        return [
            'title' => ucfirst($title),
            'slug' => Str::slug($title),
            'starts_at' => $start,
            'ends_at' => (clone $start)->modify('+1 day'),
            'venue' => fake()->city(),
            'is_published' => true,
        ];
    }
}
