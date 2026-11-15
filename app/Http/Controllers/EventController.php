<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $events = Event::published()->orderByDesc('starts_at')->get();

        return view('events.index', [
            'upcoming' => $events->filter(fn ($e) => ($e->ends_at ?? $e->starts_at)->isFuture())->sortBy('starts_at'),
            'past' => $events->reject(fn ($e) => ($e->ends_at ?? $e->starts_at)->isFuture()),
        ]);
    }

    public function show(Event $event): View
    {
        abort_unless($event->is_published || auth()->check(), 404);

        $event->load(['ticketTypes' => fn ($q) => $q->where('is_visible', true), 'tournaments']);

        return view('events.show', ['event' => $event]);
    }

    /** Old WordPress address /lanparty → next LAN. */
    public function legacyLan(): RedirectResponse
    {
        $event = Event::upcoming();

        return $event ? redirect()->route('events.show', $event, 301) : redirect()->route('events.index', status: 301);
    }
}
