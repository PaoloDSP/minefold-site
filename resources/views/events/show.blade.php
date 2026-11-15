@extends('layouts.site')

@section('title', $event->title)
@section('description', $event->tagline ?: $event->title.' — '.$event->datesLabel())

@section('content')
<section class="ev-hero" aria-label="Présentation">
    <div class="wrap top">
        <div>
            @if($event->badges)
                <div class="badges">
                    @foreach($event->badges as $i => $badge)<span class="tag {{ $i === 0 ? 'tag-brand' : 'tag-navy' }}">{{ $badge }}</span>@endforeach
                </div>
            @endif
            <h1>{{ $event->title }}</h1>
            @if($event->tagline)<p class="lead">{{ $event->tagline }}</p>@endif
        </div>
        <div class="ctas">
            @if($event->ticketTypes->isNotEmpty())<a class="btn btn-lg" href="#billetterie">Billetterie</a>@endif
            @if($event->program)<a class="btn btn-lg btn-ghost" href="#programme">Programme</a>@endif
        </div>
    </div>
    <div class="wrap">
        <div class="facts">
            <div><span class="kicker">Dates</span><strong>{{ $event->datesLabel() }}</strong></div>
            @if($event->hours_label)<div><span class="kicker">Horaires</span><strong>{{ $event->hours_label }}</strong></div>@endif
            @if($event->venue)<div><span class="kicker">Lieu</span><strong>{{ $event->venue }}</strong></div>@endif
            @if($event->registration_label)<div><span class="kicker">Ouverture des inscriptions</span><strong>{{ $event->registration_label }}</strong></div>@endif
        </div>
    </div>
</section>

<nav class="subnav" aria-label="Sections de l’événement">
    <div class="wrap">
        @if($event->ticketTypes->isNotEmpty())<a href="#billetterie">Billetterie</a>@endif
        @if($event->tournaments->isNotEmpty())<a href="#tournois">Tournois</a>@endif
        @if($event->challenge_title)<a href="#challenge">Challenge</a>@endif
        @if($event->program)<a href="#programme">Programme</a>@endif
        @if($event->byoc_items)<a href="#materiel">Matériel</a>@endif
        @if($event->rewards)<a href="#recompenses">Récompenses</a>@endif
        <a href="#infos">Infos pratiques</a>
    </div>
</nav>

@if($event->intro)
<section class="wrap ev-section"><p class="intro">{{ $event->intro }}</p></section>
@endif

@if($event->ticketTypes->isNotEmpty())
@php($first = $event->ticketTypes->first(fn ($t) => ! $t->isSoldOut()))
<section id="billetterie" class="wrap ev-section" aria-labelledby="t-bil" data-tickets>
    <div class="section-head">
        <h2 id="t-bil" class="ev-title" style="margin:0">Billetterie</h2>
        <span class="secure">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            Paiement sécurisé via HelloAsso
        </span>
    </div>
    <fieldset style="border:0;margin:0;padding:0">
        <legend class="sr-only">Choisissez votre billet</legend>
        <div class="tickets">
            @foreach($event->ticketTypes as $ticket)
                <div class="ticket">
                    <div class="row">
                        <span class="kicker">{{ $ticket->tag }}</span>
                        @if($ticket->availabilityText())<span class="avail @if($ticket->isSoldOut()) out @endif">{{ $ticket->availabilityText() }}</span>@endif
                    </div>
                    <h3>{{ $ticket->name }}</h3>
                    <div class="price">{{ $ticket->priceText() }}</div>
                    @if($ticket->perks)
                        <ul>@foreach($ticket->perks as $perk)<li>{{ $perk }}</li>@endforeach</ul>
                    @else
                        <div style="flex:1"></div>
                    @endif
                    <input class="sr-only" type="radio" name="ticket" id="ticket-{{ $ticket->id }}" value="{{ $ticket->id }}"
                        data-label="{{ $ticket->name }} · {{ $ticket->priceText() }}" data-url="{{ $ticket->checkoutUrl() }}"
                        @checked($first && $first->is($ticket)) @disabled($ticket->isSoldOut())>
                    <label class="choose" for="ticket-{{ $ticket->id }}">{{ $ticket->isSoldOut() ? 'Complet' : 'Choisir' }}</label>
                </div>
            @endforeach
        </div>
    </fieldset>
    <div class="card checkout" role="status">
        <div><span class="kicker">Votre sélection</span><strong data-selection>{{ $first ? $first->name.' · '.$first->priceText() : 'Tous les billets sont partis' }}</strong></div>
        @php($url = $first?->checkoutUrl())
        <a class="btn btn-lg" data-checkout href="{{ $url ?: '#billetterie' }}" @if(! $url) aria-disabled="true" @endif>{{ $url ? 'Continuer vers le paiement' : 'Billetterie bientôt ouverte' }}</a>
    </div>
    <p class="muted" style="font-size:14px;margin:10px 0 0">Le paiement se fait sur HelloAsso, la plateforme de paiement des associations.@if($event->helloasso_synced_at) Places mises à jour {{ $event->helloasso_synced_at->locale('fr')->diffForHumans() }}.@endif</p>
</section>
@endif

@if($event->tournaments->isNotEmpty())
<section id="tournois" class="wrap ev-section" aria-labelledby="t-tour">
    <h2 id="t-tour" class="ev-title">Tournois officiels</h2>
    <div class="tournaments">
        @foreach($event->tournaments as $t)
            <article class="card tournament">
                <header>
                    <div><h3>{{ $t->game }}</h3>@if($t->format)<p>{{ $t->format }}</p>@endif</div>
                    @if($t->capacity_value)<div class="cap"><b>{{ $t->capacity_value }}</b><span>{{ $t->capacity_label }}</span></div>@endif
                </header>
                @if($t->phases)
                    <div class="phases">
                        @foreach($t->phases as $phase)
                            <div><div class="kicker">{{ $phase['label'] ?? '' }}</div><strong>{{ $phase['title'] ?? '' }}</strong>@if(!empty($phase['text']))<p>{{ $phase['text'] }}</p>@endif</div>
                        @endforeach
                    </div>
                @endif
                <footer>
                    @if($t->rules_url)<a href="{{ $t->rules_url }}">Lire le règlement</a>@endif
                    <a href="#billetterie">{{ $t->cta_label ?: 'S’inscrire' }}</a>
                </footer>
            </article>
        @endforeach
    </div>
</section>
@endif

@if($event->challenge_title)
<section id="challenge" class="wrap ev-section" aria-labelledby="t-ch">
    <div class="card challenge">
        <div>
            <h2 id="t-ch" class="ev-title" style="margin-bottom:8px">{{ $event->challenge_title }}</h2>
            @if($event->challenge_text)<p style="margin:0 0 12px;color:var(--text-2)">{{ $event->challenge_text }}</p>@endif
            @if($event->challenge_games)<p class="muted" style="margin:0;font-size:15px">Jeux envisagés : {{ $event->challenge_games }}</p>@endif
        </div>
        @if($event->challenge_modes)
            <div class="modes">
                @foreach($event->challenge_modes as $mode)
                    <div><strong>{{ $mode['title'] ?? '' }}</strong>@if(!empty($mode['text']))<p>{{ $mode['text'] }}</p>@endif</div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endif

@if($event->program)
<section id="programme" class="wrap ev-section" aria-labelledby="t-prog">
    <h2 id="t-prog" class="ev-title">Programme</h2>
    <div class="days">
        @foreach($event->program as $day)
            <div class="card">
                <div class="head"><h3>{{ $day['day'] ?? '' }}</h3>@if(!empty($day['subtitle']))<span class="muted">{{ $day['subtitle'] }}</span>@endif</div>
                <ol>
                    @foreach($day['items'] ?? [] as $item)
                        <li>@if(!empty($item['time']))<b>{{ $item['time'] }}</b>@endif<span>{{ $item['label'] ?? '' }}</span></li>
                    @endforeach
                </ol>
            </div>
        @endforeach
    </div>
    @if($event->program_note)<p class="muted" style="font-size:14px;margin:10px 0 0">{{ $event->program_note }}</p>@endif
</section>
@endif

@if($event->byoc_items || $event->rewards)
<section class="wrap ev-section">
    <div class="grid-auto">
        @if($event->byoc_items)
            <div id="materiel" class="card" style="padding:24px;border-radius:12px">
                <h2 class="ev-title" style="font-size:28px;margin-bottom:6px">Apportez votre matériel</h2>
                @if($event->byoc_intro)<p class="muted" style="margin:0 0 14px">{{ $event->byoc_intro }}</p>@endif
                <ul class="checklist">
                    @foreach($event->byoc_items as $item)
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#5AA8F0" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if($event->rewards)
            <div id="recompenses" class="card" style="padding:24px;border-radius:12px">
                <h2 class="ev-title" style="font-size:28px;margin-bottom:6px">Récompenses</h2>
                @if($event->rewards_intro)<p class="muted" style="margin:0 0 14px">{{ $event->rewards_intro }}</p>@endif
                <ul style="margin:0;padding-left:18px;color:var(--text-2);display:flex;flex-direction:column;gap:6px">
                    @foreach($event->rewards as $reward)<li>{{ $reward }}</li>@endforeach
                </ul>
            </div>
        @endif
    </div>
</section>
@endif

<section id="infos" class="wrap ev-section" aria-labelledby="t-inf">
    <div class="practical">
        <div style="flex:1 1 420px">
            <h2 id="t-inf" class="ev-title" style="font-size:28px;margin-bottom:10px">Infos pratiques</h2>
            <dl>
                @if($event->venue)<dt>Lieu</dt><dd>{{ $event->venue }}@if($event->address)<br><span style="font-weight:400">{{ $event->address }}</span>@endif</dd>@endif
                <dt>Dates</dt><dd>{{ $event->datesLabel() }}@if($event->hours_label), {{ $event->hours_label }}@endif</dd>
                @if($event->contact_email)<dt>Contact</dt><dd><a href="mailto:{{ $event->contact_email }}">{{ $event->contact_email }}</a></dd>@endif
            </dl>
        </div>
        @if($event->map_url)<a class="btn btn-lg btn-ghost" href="{{ $event->map_url }}">Voir l’accès sur la carte</a>@endif
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    var box = document.querySelector('[data-tickets]');
    if (!box) return;
    var label = box.querySelector('[data-selection]');
    var link = box.querySelector('[data-checkout]');
    box.addEventListener('change', function (e) {
        if (e.target.name !== 'ticket') return;
        label.textContent = e.target.getAttribute('data-label');
        var url = e.target.getAttribute('data-url');
        if (url) { link.href = url; link.removeAttribute('aria-disabled'); link.textContent = 'Continuer vers le paiement'; }
        else { link.href = '#billetterie'; link.setAttribute('aria-disabled', 'true'); link.textContent = 'Billetterie bientôt ouverte'; }
    });
})();
</script>
@endpush
