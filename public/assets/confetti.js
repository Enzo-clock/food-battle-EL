(function () {
  // On ne lance la fête que sur l'écran du champion, juste après le vote (#resultat dans l'adresse)
  var champion = document.querySelector('[data-confetti]');
  if (!champion || location.hash !== '#resultat') return;
  // Pas d'animation si l'utilisateur a demandé à les réduire dans son système
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  var colors = ['--yellow', '--teal', '--orange', '--lilac', '--pink', '--sky'];
  var layer = document.createElement('div');        // un calque par-dessus la page
  layer.className = 'confetti';
  layer.setAttribute('aria-hidden', 'true');        // ignoré par les lecteurs d'écran

  for (var i = 0; i < 40; i++) {                     // 40 confettis
    var piece = document.createElement('span');
    piece.className = 'confetti__piece' + (i % 3 === 0 ? ' confetti__piece--round' : '');
    piece.style.left = Math.random() * 100 + '%';                           // position de départ
    piece.style.background = 'var(' + colors[i % colors.length] + ')';     // couleur de la charte
    piece.style.setProperty('--drift', (Math.random() * 160 - 80) + 'px'); // dérive gauche/droite
    piece.style.setProperty('--spin', (Math.random() * 720 - 360) + 'deg');// rotation
    piece.style.animationDelay = Math.random() * 0.6 + 's';
    piece.style.animationDuration = 1.8 + Math.random() * 1.2 + 's';
    layer.appendChild(piece);
  }

  document.body.appendChild(layer);
  setTimeout(function () { layer.remove(); }, 3800);  // nettoyage après la chute

  // On retire #resultat de l'adresse : un rechargement ne relance pas les confettis
  history.replaceState(null, '', location.pathname + location.search);
})();