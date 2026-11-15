<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Database\Seeders\MinefoldContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2027-01-15 12:00:00');
        $this->seed(MinefoldContentSeeder::class);
    }

    public function test_home_shows_player_and_next_event(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Minefold TV')
            ->assertSee('Geek Area Party 2027')
            ->assertSee('Grille des programmes')
            ->assertSee('data-player', false);
    }

    public function test_event_page_shows_tickets_and_tournaments(): void
    {
        $this->get('/evenements/geek-area-party-2027')->assertOk()
            ->assertSee('Billetterie')
            ->assertSee('Counter-Strike 2')
            ->assertSee('10 places restantes')
            ->assertSee('https://www.helloasso.com/associations/minefold/evenements/geek-area-party', false);
    }

    public function test_unpublished_event_is_hidden_from_visitors(): void
    {
        Event::query()->update(['is_published' => false]);

        $this->get('/evenements/geek-area-party-2027')->assertNotFound();
    }

    public function test_legacy_lanparty_redirects(): void
    {
        $this->get('/lanparty')->assertRedirect('/evenements/geek-area-party-2027')->assertStatus(301);
    }

    public function test_admin_pages_load(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin')->assertOk();
        $this->get('/admin/stream-settings')->assertOk()->assertSee('Source du lecteur');
        $this->get('/admin/events')->assertOk();
        $this->get('/admin/events/'.Event::first()->slug.'/edit')->assertOk();
        $this->get('/admin/events/create')->assertOk();
        $this->get('/admin/program-slots')->assertOk();
        $this->get('/admin/program-slots/create')->assertOk();
        $this->get('/admin/replays')->assertOk();
        $this->get('/admin/replays/create')->assertOk();
    }
}
