/*
 * Minefold TV — lecteur avec bascule Twitch / flux de secours (HLS ffplayout).
 * Le serveur décide de la source (/api/stream-status) ; le visiteur peut forcer
 * une source avec les boutons, son choix est gardé pour la session.
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-player]');
  if (!root) return;

  var screen = root.querySelector('[data-screen]');
  var badge = root.querySelector('[data-badge]');
  var notice = root.querySelector('[data-notice]');
  var noticeText = root.querySelector('[data-notice-text]');
  var chatBody = root.querySelector('[data-chat]');
  var chatLabel = root.querySelector('[data-chat-label]');
  var liveTag = root.querySelector('[data-live-tag]');
  var liveTitle = root.querySelector('[data-live-title]');
  var nowEl = document.querySelector('[data-now]');
  var nextEl = document.querySelector('[data-next]');
  var buttons = root.querySelectorAll('[data-source-btn]');
  var statusUrl = root.getAttribute('data-status-url');
  var host = window.location.hostname;

  var status = JSON.parse(root.getAttribute('data-initial') || '{}');
  var current = null;
  var hls = null;
  var STORAGE_KEY = 'minefold.player.source';

  function viewerChoice() {
    try { return sessionStorage.getItem(STORAGE_KEY); } catch (e) { return null; }
  }
  function setViewerChoice(value) {
    try {
      if (value) sessionStorage.setItem(STORAGE_KEY, value); else sessionStorage.removeItem(STORAGE_KEY);
    } catch (e) { /* stockage indisponible : choix non mémorisé */ }
  }

  function wantedSource() {
    var choice = viewerChoice();
    if (choice === 'twitch' && status.twitch && status.twitch.channel) return 'twitch';
    if (choice === 'fallback' && status.fallback && status.fallback.url) return 'fallback';
    return status.source || 'twitch';
  }

  function clearScreen() {
    if (hls) { hls.destroy(); hls = null; }
    Array.prototype.forEach.call(screen.querySelectorAll('iframe, video, .placeholder, .vctrl, .vsound'), function (el) { el.remove(); });
  }

  function placeholder(text) {
    var div = document.createElement('div');
    div.className = 'placeholder';
    var img = document.createElement('img');
    img.src = root.getAttribute('data-logo');
    img.alt = '';
    var p = document.createElement('p');
    p.textContent = text;
    div.appendChild(img);
    div.appendChild(p);
    screen.appendChild(div);
  }

  function showTwitch() {
    var channel = status.twitch && status.twitch.channel;
    if (!channel) { placeholder('Aucune chaîne Twitch configurée.'); return; }
    var iframe = document.createElement('iframe');
    iframe.src = 'https://player.twitch.tv/?channel=' + encodeURIComponent(channel) + '&parent=' + encodeURIComponent(host) + '&autoplay=true&muted=true';
    iframe.title = 'Lecteur Twitch';
    iframe.allow = 'autoplay; fullscreen; picture-in-picture';
    screen.appendChild(iframe);
  }

  function goLive(video) {
    var target = null;
    if (hls && hls.liveSyncPosition != null) {
      target = hls.liveSyncPosition;
    } else if (video.seekable && video.seekable.length) {
      target = video.seekable.end(video.seekable.length - 1);
    }
    if (target != null) {
      try { video.currentTime = target; } catch (e) { /* position indisponible */ }
    }
    if (video.paused) {
      var p = video.play();
      if (p && p.catch) p.catch(function () {});
    }
  }

  // Barre de contrôle minimale pour le flux de secours : pas de rembobinage,
  // juste lecture/pause, volume, « direct » et plein écran.
  function buildControls(video) {
    var bar = document.createElement('div');
    bar.className = 'vctrl';

    function button(label, svg, onClick) {
      var b = document.createElement('button');
      b.type = 'button';
      b.setAttribute('aria-label', label);
      b.innerHTML = svg;
      b.addEventListener('click', onClick);
      return b;
    }

    var icoPlay = '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13l10.5-6.5z"/></svg>';
    var icoPause = '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5h3v14H7zM14 5h3v14h-3z"/></svg>';
    var icoVol = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5 6 9H2v6h4l5 4z"/><path d="M15.5 8.5a5 5 0 0 1 0 7"/></svg>';
    var icoMute = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5 6 9H2v6h4l5 4z"/><path d="m22 9-6 6M16 9l6 6"/></svg>';
    var icoFull = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>';

    var playBtn = button('Lecture / pause', icoPause, function () {
      if (video.paused) { video.play(); } else { video.pause(); }
    });
    video.addEventListener('play', function () { playBtn.innerHTML = icoPause; });
    video.addEventListener('pause', function () { playBtn.innerHTML = icoPlay; });

    function unmute() {
      if (video.volume === 0) { video.volume = 0.8; }
      video.muted = false;
    }

    var muteBtn = button('Couper / activer le son', icoMute, function () {
      if (video.muted) { unmute(); } else { video.muted = true; }
    });

    var vol = document.createElement('input');
    vol.type = 'range';
    vol.min = 0; vol.max = 1; vol.step = 0.05; vol.value = video.volume;
    vol.className = 'vvol';
    vol.setAttribute('aria-label', 'Volume');
    vol.addEventListener('input', function () {
      video.volume = parseFloat(vol.value);
      video.muted = video.volume === 0;
    });

    // Bouton bien visible tant que le son est coupé (lecture auto = muette).
    var soundBtn = document.createElement('button');
    soundBtn.type = 'button';
    soundBtn.className = 'vsound';
    soundBtn.innerHTML = icoMute + '<span>Activer le son</span>';
    soundBtn.addEventListener('click', unmute);
    screen.appendChild(soundBtn);

    function syncSound() {
      var silent = video.muted || video.volume === 0;
      muteBtn.innerHTML = silent ? icoMute : icoVol;
      vol.value = video.muted ? 0 : video.volume;
      soundBtn.hidden = !silent;
    }
    video.addEventListener('volumechange', syncSound);
    syncSound();

    var liveBtn = document.createElement('button');
    liveBtn.type = 'button';
    liveBtn.className = 'vlive';
    liveBtn.innerHTML = '<span class="vdot"></span>DIRECT';
    liveBtn.setAttribute('aria-label', 'Revenir au direct');
    liveBtn.addEventListener('click', function () { goLive(video); });

    var fullBtn = button('Plein écran', icoFull, function () {
      var el = screen;
      if (document.fullscreenElement) { document.exitFullscreen(); }
      else if (el.requestFullscreen) { el.requestFullscreen(); }
    });

    var spacer = document.createElement('span');
    spacer.className = 'vspace';

    bar.appendChild(playBtn);
    bar.appendChild(muteBtn);
    bar.appendChild(vol);
    bar.appendChild(spacer);
    bar.appendChild(liveBtn);
    bar.appendChild(fullBtn);
    screen.appendChild(bar);
  }

  function showFallback() {
    var url = status.fallback && status.fallback.url;
    if (!url) { placeholder('Le flux de secours n’est pas configuré.'); return; }
    var video = document.createElement('video');
    video.autoplay = true;
    video.muted = true;
    video.volume = 0.8;
    video.playsInline = true;
    video.setAttribute('aria-label', 'Lecteur ' + (status.fallback.label || 'Minefold TV'));
    screen.appendChild(video);
    buildControls(video);

    if (video.canPlayType('application/vnd.apple.mpegurl')) {
      video.src = url;
    } else if (window.Hls && window.Hls.isSupported()) {
      hls = new window.Hls({
        liveDurationInfinity: true,
        liveSyncDurationCount: 3,
        liveMaxLatencyDurationCount: 10,
        backBufferLength: 10,
      });
      hls.loadSource(url);
      hls.attachMedia(video);
      // Démarrer au plus près du direct plutôt qu'au début de la fenêtre HLS.
      hls.on(window.Hls.Events.MANIFEST_PARSED, function () {
        if (hls && hls.liveSyncPosition != null) {
          try { video.currentTime = hls.liveSyncPosition; } catch (e) { /* position indisponible */ }
        }
      });
      hls.on(window.Hls.Events.ERROR, function (event, data) {
        if (!data.fatal) return;
        if (data.type === window.Hls.ErrorTypes.NETWORK_ERROR) {
          setTimeout(function () { if (hls) hls.startLoad(); }, 5000);
        } else if (data.type === window.Hls.ErrorTypes.MEDIA_ERROR) {
          hls.recoverMediaError();
        } else {
          clearScreen();
          placeholder('Le flux Minefold est momentanément indisponible.');
        }
      });
    } else {
      placeholder('Votre navigateur ne sait pas lire ce flux.');
      return;
    }
    var play = video.play();
    if (play && play.catch) play.catch(function () { /* lecture auto bloquée : le visiteur clique sur play */ });
  }

  function showChat(source) {
    chatBody.innerHTML = '';
    var channel = status.twitch && status.twitch.channel;
    if (source === 'twitch' && channel) {
      var iframe = document.createElement('iframe');
      iframe.src = 'https://www.twitch.tv/embed/' + encodeURIComponent(channel) + '/chat?parent=' + encodeURIComponent(host) + '&darkpopout';
      iframe.title = 'Chat Twitch';
      chatBody.appendChild(iframe);
      chatLabel.textContent = 'Chat Twitch';
    } else {
      var p = document.createElement('p');
      p.className = 'empty';
      p.textContent = root.getAttribute('data-fallback-chat');
      chatBody.appendChild(p);
      chatLabel.textContent = status.fallback && status.fallback.label ? status.fallback.label : 'Minefold TV';
    }
  }

  function updateTexts(source) {
    if (liveTitle) liveTitle.textContent = status.now || '';
    if (badge) {
      // Le lecteur Twitch affiche déjà ses propres infos en haut à gauche.
      badge.hidden = source === 'twitch';
      badge.textContent = 'Flux Minefold · ' + ((status.fallback && status.fallback.label) || 'Minefold TV');
    }
    if (nowEl) nowEl.textContent = status.now || 'Programme en continu';
    if (nextEl) {
      var next = status.next || {};
      nextEl.textContent = next.title ? (next.time ? next.time + ' · ' : '') + next.title : 'À suivre';
    }

    var forced = viewerChoice();
    var message = null;
    if (source === 'fallback') {
      if (status.reason === 'twitch_unreachable') message = 'Twitch ne répond pas : le direct continue ici, sur le flux Minefold.';
      else if (status.reason === 'twitch_offline' && !forced) message = 'Hors direct Twitch : vous regardez la chaîne Minefold en continu.';
      else if (forced === 'fallback') message = 'Vous regardez le flux diffusé directement par le serveur Minefold.';
    }
    notice.hidden = !message;
    if (message) noticeText.textContent = message;

    Array.prototype.forEach.call(buttons, function (btn) {
      btn.setAttribute('aria-pressed', btn.getAttribute('data-source-btn') === source ? 'true' : 'false');
    });
  }

  function render(force) {
    var source = wantedSource();
    if (force || source !== current) {
      clearScreen();
      if (source === 'fallback') showFallback(); else showTwitch();
      showChat(source);
      current = source;
    }
    updateTexts(source);
  }

  Array.prototype.forEach.call(buttons, function (btn) {
    btn.addEventListener('click', function () {
      var value = btn.getAttribute('data-source-btn');
      // Revenir sur la source choisie par le serveur = repasser en automatique.
      setViewerChoice(value === status.source ? null : value);
      render(false);
    });
  });

  function poll() {
    if (!statusUrl || document.hidden) return;
    fetch(statusUrl, { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (data) { if (data) { status = data; render(false); } })
      .catch(function () { /* réseau coupé : on garde l'état actuel */ });
  }

  render(true);
  setInterval(poll, 30000);
  document.addEventListener('visibilitychange', poll);
})();
