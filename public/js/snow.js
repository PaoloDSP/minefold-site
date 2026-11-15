/* Neige du thème de Noël — légère, respecte « animations réduites ». */
(function () {
  'use strict';
  var box = document.querySelector('.snow');
  if (!box) return;
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  var glyphs = ['❄', '❅', '❆', '•'];
  var count = 44;
  for (var i = 0; i < count; i++) {
    var flake = document.createElement('i');
    flake.textContent = glyphs[i % glyphs.length];
    flake.style.left = (Math.random() * 100) + 'vw';
    flake.style.fontSize = (Math.random() * 14 + 8) + 'px';
    flake.style.opacity = (Math.random() * 0.5 + 0.3).toFixed(2);
    flake.style.animationDuration = (Math.random() * 8 + 7) + 's';
    flake.style.animationDelay = (-Math.random() * 15).toFixed(1) + 's';
    box.appendChild(flake);
  }
})();
