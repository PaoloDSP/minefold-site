@extends('layouts.site')

@section('title', 'Événements')

@section('content')
<section class="wrap ev-section">
    <h1 class="ev-title">Événements</h1>
    @if($upcoming->isNotEmpty())
        <div class="ev-list">
            @foreach($upcoming as $event)
                <a class="card ev-item" href="{{ route('events.show', $event) }}">
                    <span class="kicker" style="color:var(--link)">À venir</span>
                    <h2>{{ $event->title }}</h2>
                    <span class="muted">{{ collect([$event->datesLabel(), $event->venue])->filter()->implode(' · ') }}</span>
                    @if($event->tagline)<span>{{ $event->tagline }}</span>@endif
                </a>
            @endforeach
        </div>
    @else
        <p class="muted">Aucun événement annoncé pour le moment. Suivez-nous sur Twitch pour les prochaines annonces.</p>
    @endif

    @if($past->isNotEmpty())
        <h2 class="section-title" style="margin:40px 0 16px">Éditions passées</h2>
        <div class="ev-list">
            @foreach($past as $event)
                <a class="card ev-item" href="{{ route('events.show', $event) }}">
                    <h2 style="font-size:20px">{{ $event->title }}</h2>
                    <span class="muted">{{ $event->datesLabel() }}</span>
                </a>
            @endforeach
        </div>
    @endif
</section>
@endsection
