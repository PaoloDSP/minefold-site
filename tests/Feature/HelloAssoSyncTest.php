<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HelloAssoSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_updates_linked_tickets(): void
    {
        config(['services.helloasso.client_id' => 'id', 'services.helloasso.client_secret' => 's', 'services.helloasso.organization' => 'minefold']);
        $event = Event::factory()->create(['helloasso_form_slug' => 'geek-area-party']);
        $linked = $event->ticketTypes()->create(['name' => 'Joueur', 'helloasso_tier_id' => 111]);
        $manual = $event->ticketTypes()->create(['name' => 'Visiteur', 'capacity' => 30]);

        Http::fake([
            'api.helloasso.com/oauth2/token' => Http::response(['access_token' => 'tok']),
            'api.helloasso.com/v5/organizations/minefold/forms/Event/geek-area-party/stats' => Http::response([
                'totalParticipant' => 42,
                'unGroupedTiers' => [['id' => 111, 'label' => 'Joueur', 'entriesTaken' => 42, 'maxEntries' => 50, 'price' => 1500]],
            ]),
        ]);

        $this->artisan('helloasso:sync')->assertSuccessful();

        $linked->refresh();
        $this->assertSame(42, $linked->entries_taken);
        $this->assertSame(8, $linked->remaining());
        $this->assertSame('15 €', $linked->priceText());
        $this->assertSame('8 places restantes', $linked->availabilityText());
        $this->assertNull($manual->refresh()->entries_taken);
        $this->assertNotNull($event->refresh()->helloasso_synced_at);
    }

    public function test_sync_is_noop_without_credentials(): void
    {
        Http::fake();
        $this->artisan('helloasso:sync')->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_tiers_command_lists_form_tiers(): void
    {
        config(['services.helloasso.client_id' => 'id', 'services.helloasso.client_secret' => 's', 'services.helloasso.organization' => 'minefold']);
        Event::factory()->create(['helloasso_form_slug' => 'geek-area-party']);
        Http::fake([
            'api.helloasso.com/oauth2/token' => Http::response(['access_token' => 'tok']),
            'api.helloasso.com/v5/*' => Http::response(['unGroupedTiers' => [['id' => 111, 'label' => 'Joueur libre', 'entriesTaken' => 3, 'maxEntries' => 50, 'price' => 1500]]]),
        ]);

        $this->artisan('helloasso:tiers')
            ->expectsOutputToContain('Joueur libre')
            ->assertSuccessful();
    }
}
