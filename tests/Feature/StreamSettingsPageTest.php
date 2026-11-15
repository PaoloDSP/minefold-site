<?php

namespace Tests\Feature;

use App\Filament\Pages\StreamSettings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StreamSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_are_saved(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(StreamSettings::class)
            ->set('data.twitch_channel', 'minefold_webtv')
            ->set('data.player_mode', 'fallback')
            ->set('data.fallback_hls_url', 'https://tv.minefold.fr/live/stream.m3u8')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('fallback', Setting::get('player_mode'));
        $this->assertSame('https://tv.minefold.fr/live/stream.m3u8', Setting::get('fallback_hls_url'));
    }
}
