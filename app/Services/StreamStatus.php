<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Decides which source the player should show: Twitch or the Minefold (ffplayout) HLS stream.
 */
class StreamStatus
{
    public const CACHE_KEY = 'stream.status';

    public function __construct(protected Twitch $twitch) {}

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    public function current(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addSeconds(45), fn () => $this->resolve());
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(): array
    {
        $mode = Setting::get('player_mode', 'auto');
        $channel = Setting::get('twitch_channel');
        $fallbackUrl = Setting::get('fallback_hls_url');

        $twitchState = 'unknown';
        $live = null;
        if ($channel && $this->twitch->configured()) {
            try {
                $live = $this->twitch->liveStream($channel);
                $twitchState = $live ? 'live' : 'offline';
            } catch (Throwable) {
                $twitchState = 'error';
            }
        }

        $source = match ($mode) {
            'twitch' => 'twitch',
            'fallback' => 'fallback',
            default => $this->autoSource($twitchState, (bool) $channel, (bool) $fallbackUrl),
        };

        $reason = null;
        if ($source === 'fallback' && $mode === 'auto') {
            $reason = $twitchState === 'error' ? 'twitch_unreachable' : 'twitch_offline';
        } elseif ($mode !== 'auto') {
            $reason = 'forced';
        }

        $now = $live['title'] ?? Setting::get('now_title');

        return [
            'mode' => $mode,
            'source' => $source,
            'reason' => $reason,
            'twitch' => [
                'channel' => $channel,
                'state' => $twitchState,
                'title' => $live['title'] ?? null,
                'game' => $live['game'] ?? null,
            ],
            'fallback' => [
                'url' => $fallbackUrl,
                'label' => Setting::get('fallback_label') ?: 'Minefold TV',
            ],
            'now' => $now,
            'content' => $this->contentState($now),
            'next' => ['title' => Setting::get('next_title'), 'time' => Setting::get('next_time')],
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Direct réel ou rediffusion, déduit du titre du programme (le vrai direct est marqué dans le titre Twitch).
     *
     * @return array{state: string, label: string}
     */
    protected function contentState(?string $title): array
    {
        $t = mb_strtolower((string) $title);
        $isLive = (str_contains($t, 'direct') || str_contains($t, 'live')) && ! str_contains($t, 'rediff');

        return $isLive
            ? ['state' => 'live', 'label' => 'EN DIRECT']
            : ['state' => 'rerun', 'label' => 'REDIFFUSION'];
    }

    protected function autoSource(string $twitchState, bool $hasChannel, bool $hasFallback): string
    {
        if (! $hasFallback) {
            return 'twitch';
        }
        if (! $hasChannel) {
            return 'fallback';
        }

        // Unknown = Twitch API not configured: we cannot tell, keep Twitch as the primary.
        return in_array($twitchState, ['live', 'unknown'], true) ? 'twitch' : 'fallback';
    }
}
