<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\StreamStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StreamStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.twitch.client_id' => 'id', 'services.twitch.client_secret' => 'secret']);
        Setting::put(['twitch_channel' => 'minefold_webtv', 'fallback_hls_url' => 'https://tv.example/live.m3u8', 'player_mode' => 'auto']);
    }

    private function fakeTwitch(array|int $streams): void
    {
        Http::fake([
            'id.twitch.tv/*' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            'api.twitch.tv/*' => is_int($streams) ? Http::response([], $streams) : Http::response(['data' => $streams]),
        ]);
    }

    public function test_twitch_live_uses_twitch(): void
    {
        $this->fakeTwitch([['type' => 'live', 'title' => 'Soirée Minecraft', 'game_name' => 'Minecraft', 'viewer_count' => 12]]);

        $status = app(StreamStatus::class)->resolve();

        $this->assertSame('twitch', $status['source']);
        $this->assertSame('live', $status['twitch']['state']);
        $this->assertSame('Soirée Minecraft', $status['now']);
    }

    public function test_twitch_offline_falls_back_to_minefold_stream(): void
    {
        $this->fakeTwitch([]);

        $status = app(StreamStatus::class)->resolve();

        $this->assertSame('fallback', $status['source']);
        $this->assertSame('twitch_offline', $status['reason']);
    }

    public function test_twitch_outage_falls_back(): void
    {
        $this->fakeTwitch(503);

        $status = app(StreamStatus::class)->resolve();

        $this->assertSame('fallback', $status['source']);
        $this->assertSame('twitch_unreachable', $status['reason']);
    }

    public function test_forced_mode_wins(): void
    {
        $this->fakeTwitch([['type' => 'live', 'title' => 'x']]);
        Setting::put(['player_mode' => 'fallback']);

        $this->assertSame('fallback', app(StreamStatus::class)->resolve()['source']);
    }

    public function test_without_twitch_credentials_twitch_stays_primary(): void
    {
        config(['services.twitch.client_id' => null]);
        Http::fake();

        $status = app(StreamStatus::class)->resolve();

        $this->assertSame('twitch', $status['source']);
        $this->assertSame('unknown', $status['twitch']['state']);
        Http::assertNothingSent();
    }

    public function test_content_is_live_when_title_says_so(): void
    {
        $this->fakeTwitch([['type' => 'live', 'title' => 'EN DIRECT — tournoi CS2']]);

        $this->assertSame('live', app(StreamStatus::class)->resolve()['content']['state']);
    }

    public function test_content_is_rerun_by_default(): void
    {
        $this->fakeTwitch([['type' => 'live', 'title' => 'Rediffusions - Minefold.fr']]);

        $this->assertSame('rerun', app(StreamStatus::class)->resolve()['content']['state']);
    }

    public function test_manual_live_toggle_forces_live(): void
    {
        $this->fakeTwitch([['type' => 'live', 'title' => 'Rediffusions - Minefold.fr']]);
        Setting::put(['live_now' => '1']);

        $this->assertSame('live', app(StreamStatus::class)->resolve()['content']['state']);
    }

    public function test_status_endpoint(): void
    {
        $this->fakeTwitch([]);

        $this->getJson('/api/stream-status')->assertOk()->assertJsonPath('source', 'fallback');
    }
}
