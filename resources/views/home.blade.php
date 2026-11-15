@extends('layouts.site')

@section('content')
<section class="wrap" style="padding-top: 28px" aria-label="Direct"
    data-player
    data-status-url="{{ route('stream.status') }}"
    data-initial='@json($status)'
    data-logo="{{ asset('images/minefold-logo.png') }}"
    data-fallback-chat="Le chat Twitch revient dès que le direct Twitch reprend. En attendant, rejoignez-nous sur nos réseaux.">
    <div class="live-head">
        <div class="title">
            <span class="tag tag-brand">À L’ANTENNE</span>
            <h1>Minefold TV</h1>
            <span class="muted" data-live-title>{{ $status['now'] }}</span>
        </div>
        <div class="source-switch" role="group" aria-label="Source du lecteur">
            <span>Source</span>
            <button type="button" data-source-btn="twitch" aria-pressed="{{ $status['source'] === 'twitch' ? 'true' : 'false' }}">Twitch</button>
            <button type="button" data-source-btn="fallback" aria-pressed="{{ $status['source'] === 'fallback' ? 'true' : 'false' }}">Serveur Minefold</button>
        </div>
    </div>

    <div class="notice" role="status" data-notice hidden>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
        <span data-notice-text></span>
    </div>

    <div class="player-row">
        <div class="player">
            <div class="screen" data-screen>
                <span class="badge" data-badge></span>
                <noscript><div class="placeholder"><p>Activez JavaScript pour regarder le direct, ou rendez-vous sur Twitch.</p></div></noscript>
            </div>
        </div>
        <aside class="card chat" aria-label="Chat">
            <div class="chat-head"><strong>Chat du direct</strong><span class="muted" data-chat-label></span></div>
            <div class="chat-body" data-chat></div>
        </aside>
    </div>

    <div class="onair">
        <div class="now"><div class="kicker">À l’antenne</div><strong data-now>{{ $status['now'] ?: 'Programme en continu' }}</strong></div>
        <div><div class="kicker">Ensuite</div><strong data-next>{{ $status['next']['title'] ? trim(($status['next']['time'] ? $status['next']['time'].' · ' : '').$status['next']['title']) : 'À suivre' }}</strong></div>
        <div class="muted">Hors direct, Minefold TV diffuse ses programmes et replays en continu.</div>
    </div>
</section>

@if($event)
<section class="wrap section" aria-label="Prochain événement">
    <div class="featured">
        <div class="info">
            <div class="kicker">Prochain événement</div>
            <h2>{{ $event->title }}</h2>
            <p>{{ collect([$event->datesLabel(), $event->hours_label, $event->venue])->filter()->implode(' · ') }}</p>
            @if($event->tagline)<p class="sub">{{ $event->tagline }}</p>@endif
        </div>
        <div class="side">
            <div class="countdown"><b>{{ $event->daysUntil() }}</b><span>{{ $event->daysUntil() > 1 ? 'jours restants' : 'jour restant' }}</span></div>
            <a class="btn btn-lg" href="{{ route('events.show', $event) }}#billetterie">Prendre ma place</a>
        </div>
    </div>
</section>
@endif

<section id="programmes" class="wrap section" aria-labelledby="titre-prog">
    <div class="section-head">
        <h2 id="titre-prog" class="section-title">Grille des programmes</h2>
        <span class="muted">Semaine type · heure de Paris</span>
    </div>
    <div class="schedule-scroll" tabindex="0" aria-label="Grille de la semaine (défilement horizontal)">
        <div class="schedule">
            @foreach($week as $day)
                <div class="day @if($day['today']) today @endif">
                    <h3>{{ $day['name'] }}@if($day['today']) · Aujourd’hui @endif</h3>
                    <div class="slots">
                        @forelse($day['slots'] as $slot)
                            <div class="slot {{ $slot->category }}"><small>{{ $slot->starts_at }}</small><span>{{ $slot->title }}</span></div>
                        @empty
                            <p class="muted" style="margin:0;font-size:14px">Programme en continu</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="legend" aria-hidden="true">
        <span><i style="background:#2185E3"></i>Direct</span>
        <span><i style="background:#E8913A"></i>Spécial / événement</span>
        <span><i style="background:#6F737B"></i>Rediffusion</span>
    </div>
</section>

@if($replays->isNotEmpty())
<section id="replays" class="wrap section" aria-labelledby="titre-rep">
    <div class="section-head"><h2 id="titre-rep" class="section-title">Replays &amp; souvenirs</h2></div>
    <div class="replays">
        @foreach($replays as $replay)
            <a class="card replay" href="{{ $replay->url ?: '#replays' }}">
                <div class="thumb @if(!$replay->thumbnail) empty @endif">
                    <img src="{{ $replay->thumbnail ? \Illuminate\Support\Facades\Storage::disk('public')->url($replay->thumbnail) : asset('images/minefold-logo.png') }}" alt="" loading="lazy">
                </div>
                <div class="meta"><strong>{{ $replay->title }}</strong><span class="muted">{{ collect([$replay->kind, $replay->published_on?->translatedFormat('F Y')])->filter()->implode(' · ') }}</span></div>
            </a>
        @endforeach
    </div>
</section>
@endif

@php($membership = \App\Models\Setting::get('membership_url'))
@php($donation = \App\Models\Setting::get('donation_url'))
@php($shop = \App\Models\Setting::get('shop_url'))
@if($membership || $donation || $shop)
<section class="wrap section support" aria-label="Soutenir Minefold">
    <div class="grid-auto">
        @if($membership)<div class="card"><h3>Adhésion</h3><p>Rejoignez l’association et participez à la vie de Minefold.</p><a href="{{ $membership }}">Adhérer via HelloAsso</a></div>@endif
        @if($donation)<div class="card"><h3>Faire un don</h3><p>Aidez-nous à financer le matériel, les lives et les LAN.</p><a href="{{ $donation }}">Faire un don</a></div>@endif
        @if($shop)<div class="card"><h3>Boutique</h3><p>Goodies et vêtements aux couleurs de Minefold.</p><a href="{{ $shop }}">Voir la boutique</a></div>@endif
    </div>
</section>
@endif
@endsection

@push('scripts')
<script src="{{ asset('vendor/hls/hls.light.min.js') }}" defer></script>
<script src="{{ asset('js/player.js') }}?v={{ filemtime(public_path('js/player.js')) }}" defer></script>
@endpush
