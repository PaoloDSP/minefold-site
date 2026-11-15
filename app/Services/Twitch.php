<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Minimal Twitch Helix client (app access token).
 */
class Twitch
{
    public function configured(): bool
    {
        return filled(config('services.twitch.client_id')) && filled(config('services.twitch.client_secret'));
    }

    /**
     * Live stream info for a channel, or null when offline / banned / unknown.
     *
     * @return array{title:string, game:?string, viewers:int}|null
     *
     * @throws \RuntimeException when Twitch cannot be reached
     */
    public function liveStream(string $login): ?array
    {
        $response = Http::withToken($this->token())
            ->withHeaders(['Client-Id' => config('services.twitch.client_id')])
            ->timeout(5)
            ->get('https://api.twitch.tv/helix/streams', ['user_login' => $login]);

        if ($response->status() === 401) {
            Cache::forget('twitch.token');
        }
        if (! $response->successful()) {
            throw new \RuntimeException('Twitch API error '.$response->status());
        }

        $stream = $response->json('data.0');
        if (! $stream || ($stream['type'] ?? '') !== 'live') {
            return null;
        }

        return [
            'title' => (string) ($stream['title'] ?? ''),
            'game' => $stream['game_name'] ?? null,
            'viewers' => (int) ($stream['viewer_count'] ?? 0),
        ];
    }

    protected function token(): string
    {
        return Cache::remember('twitch.token', now()->addHours(12), function () {
            try {
                $response = Http::asForm()->timeout(5)->post('https://id.twitch.tv/oauth2/token', [
                    'client_id' => config('services.twitch.client_id'),
                    'client_secret' => config('services.twitch.client_secret'),
                    'grant_type' => 'client_credentials',
                ])->throw();
            } catch (Throwable $e) {
                Log::warning('Twitch token request failed: '.$e->getMessage());
                throw new \RuntimeException('Twitch token unavailable', previous: $e);
            }

            return (string) $response->json('access_token');
        });
    }
}
