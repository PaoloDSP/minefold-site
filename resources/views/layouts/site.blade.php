@php($links = ['membership' => \App\Models\Setting::get('membership_url'), 'donation' => \App\Models\Setting::get('donation_url'), 'shop' => \App\Models\Setting::get('shop_url'), 'discord' => \App\Models\Setting::get('discord_url'), 'twitch' => \App\Models\Setting::get('twitch_channel')])
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') — @endif Minefold</title>
    <meta name="description" content="@yield('description', 'Minefold, association gaming de Bourg-Saint-Andéol : la chaîne Minefold TV en direct et les LAN parties.')">
    <link rel="icon" type="image/png" href="{{ asset('favicon-64.png') }}">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}">
    @stack('head')
</head>
<body>
<a class="skip" href="#contenu">Aller au contenu</a>
<header class="site-header">
    <div class="bar"></div>
    <div class="wrap">
        <a class="logo" href="{{ route('home') }}" aria-label="Minefold — accueil"><img src="{{ asset('images/minefold-logo.png') }}" alt="Minefold" width="142" height="44"></a>
        <nav class="main-nav" aria-label="Menu principal">
            <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif><span class="dot" aria-hidden="true"></span>Direct</a>
            <a href="{{ route('home') }}#programmes">Programmes</a>
            <a href="{{ route('home') }}#replays">Replays</a>
            <a href="{{ route('events.index') }}" @if(request()->routeIs('events.*')) aria-current="page" @endif>Événements</a>
            @if($links['shop'])<a href="{{ $links['shop'] }}">Boutique</a>@endif
            @if($links['membership'])<a href="{{ $links['membership'] }}">Adhésion</a>@endif
        </nav>
        @if($links['donation'])<a class="btn" href="{{ $links['donation'] }}">Faire un don</a>@endif
    </div>
</header>

<main id="contenu">
    @yield('content')
</main>

<footer class="site-footer">
    <div class="wrap">
        <p class="legal">
            <img src="{{ asset('images/minefold-logo.png') }}" alt="Minefold" width="168" height="52">
            Association déclarée sous le nom W072003180<br>
            SIRET 829 592 674 00024 · TVA non applicable<br>
            Allée des Lavandières, 07700 Bourg-Saint-Andéol
        </p>
        <nav aria-label="Liens du pied de page">
            <div>
                <strong>La chaîne</strong>
                <a href="{{ route('home') }}">Direct</a>
                <a href="{{ route('home') }}#programmes">Programmes</a>
                <a href="{{ route('home') }}#replays">Replays</a>
                @if($links['twitch'])<a href="https://www.twitch.tv/{{ $links['twitch'] }}">Twitch</a>@endif
                @if($links['discord'])<a href="{{ $links['discord'] }}">Discord</a>@endif
            </div>
            <div>
                <strong>L’association</strong>
                <a href="{{ route('events.index') }}">Événements</a>
                @if($links['membership'])<a href="{{ $links['membership'] }}">Adhésion</a>@endif
                @if($links['donation'])<a href="{{ $links['donation'] }}">Faire un don</a>@endif
                <a href="mailto:contact@minefold.fr">contact@minefold.fr</a>
            </div>
        </nav>
    </div>
    <div class="copy">© {{ now()->year }} Minefold — Tous droits réservés</div>
</footer>
@stack('scripts')
</body>
</html>
