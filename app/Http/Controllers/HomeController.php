<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\ProgramSlot;
use App\Models\Replay;
use App\Services\StreamStatus;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(StreamStatus $stream): View
    {
        return view('home', [
            'status' => $stream->current(),
            'event' => Event::upcoming(),
            'week' => ProgramSlot::week(),
            'replays' => Replay::where('is_visible', true)->orderBy('sort')->orderByDesc('published_on')->limit(8)->get(),
        ]);
    }
}
