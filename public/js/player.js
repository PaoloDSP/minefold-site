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
    Array.prototype.forEach.call(screen.querySelectorAll('iframe, video, .placeholder'), function (el) { el.remove(); });
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

  function showFallback() {
    var url = status.fallback && status.fallback.url;
    if (!url) { placeholder('Le flux de secours n’est pas configuré.'); return; }
    var video = document.createElement('video');
    video.controls = true;
    video.autoplay = true;
    video.muted = true;
    video.playsInline = true;
    video.setAttribute('aria-label', 'Lecteur ' + (status.fallback.label || 'Minefold TV'));
    screen.appendChild(video);

    if (video.canPlayType('application/vnd.apple.mpegurl')) {
      video.src = url;
    } else if (window.Hls && window.Hls.isSupported()) {
      hls = new window.Hls({ liveDurationInfinity: true });
      hls.loadSource(url);
      hls.attachMedia(video);
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
    // L'étiquette reflète le contenu (direct réel ou rediffusion), pas la source du flux.
    var content = status.content || { state: 'rerun', label: 'REDIFFUSION' };
    if (liveTag) {
      liveTag.hidden = false;
      liveTag.textContent = content.label;
      liveTag.className = 'tag ' + (content.state === 'live' ? 'tag-live' : 'tag-rerun');
    }
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
