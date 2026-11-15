<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * HelloAsso API v5 client (client credentials). Reads tier stats of an event form.
 */
class HelloAsso
{
    public const BASE = 'https://api.helloasso.com';

    public function configured(): bool
    {
        return filled(config('services.helloasso.client_id'))
            && filled(config('services.helloasso.client_secret'))
            && filled(config('services.helloasso.organization'));
    }

    /**
     * GET /v5/organizations/{org}/forms/{type}/{slug}/stats
     *
     * @return array<int, array{id:int, label:?string, entriesTaken:int, maxEntries:?int, price:?int}>
     */
    public function tierStats(string $formType, string $formSlug): array
    {
        $org = config('services.helloasso.organization');
        $response = Http::withToken($this->token())
            ->acceptJson()
            ->timeout(10)
            ->get(self::BASE."/v5/organizations/{$org}/forms/{$formType}/{$formSlug}/stats")
            ->throw();

        return collect($response->json('unGroupedTiers', []))
            ->map(fn ($t) => [
                'id' => (int) $t['id'],
                'label' => $t['label'] ?? null,
                'entriesTaken' => (int) ($t['entriesTaken'] ?? 0),
                'maxEntries' => isset($t['maxEntries']) ? (int) $t['maxEntries'] : null,
                'price' => isset($t['price']) ? (int) $t['price'] : null,
            ])
            ->keyBy('id')
            ->all();
    }

    /**
     * Copy sold counts / capacities / prices from HelloAsso onto the event's linked tickets.
     */
    public function syncEvent(Event $event): int
    {
        if (! $event->helloasso_form_slug) {
            return 0;
        }

        $stats = $this->tierStats($event->helloasso_form_type ?: 'Event', $event->helloasso_form_slug);
        $updated = 0;
        foreach ($event->ticketTypes()->whereNotNull('helloasso_tier_id')->get() as $ticket) {
            $tier = $stats[$ticket->helloasso_tier_id] ?? null;
            if (! $tier) {
                continue;
            }
            $ticket->fill([
                'entries_taken' => $tier['entriesTaken'],
                'max_entries' => $tier['maxEntries'] ?: null,
            ]);
            if ($tier['price'] !== null && ! $ticket->price_label) {
                $ticket->price_cents = $tier['price'];
            }
            $ticket->save();
            $updated++;
        }
        $event->forceFill(['helloasso_synced_at' => now()])->save();

        return $updated;
    }

    protected function token(): string
    {
        return Cache::remember('helloasso.token', now()->addMinutes(25), function () {
            $response = Http::asForm()->timeout(10)->post(self::BASE.'/oauth2/token', [
                'client_id' => config('services.helloasso.client_id'),
                'client_secret' => config('services.helloasso.client_secret'),
                'grant_type' => 'client_credentials',
            ])->throw();

            return (string) $response->json('access_token');
        });
    }
}
