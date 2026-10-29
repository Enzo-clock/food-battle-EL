// filepath: public/assets/draw.js
// « Mon plat du jour… ? » : mise en scène du tirage.
//
// Le serveur a DÉJÀ choisi le plat (random_int, côté PHP) : ce script ne fait que l'animer.
//   1. précharger les images (sinon la photo du gagnant arrive en retard sur mobile)
//   2. phase 1 : faire défiler d'autres plats en lice, de plus en plus lentement
//   3. phase 2 : révéler le gagnant (pop avec rebond + contenu en cascade, en CSS)
// « Retenter ma chance » demande un nouveau tirage à /api/tirage, sans recharger la page.
// Tout le texte passe par textContent : un nom de plat ne peut jamais injecter de HTML.
(function () {
  var root = document.getElementById('draw');
  var dataEl = document.getElementById('draw-data');
  if (!root || !dataEl) return;

  var card = root.querySelector('[data-card]');
  var statusEl = root.querySelector('[data-status]');
  var errorEl = root.querySelector('[data-error]');
  var retry = root.querySelector('[data-retry]');
  var fields = {};
  root.querySelectorAll('[data-field]').forEach(function (el) {
    fields[el.getAttribute('data-field')] = el;
  });

  var PLACEHOLDER = '/assets/images/placeholder.svg';
  var FRAMES = 14;          // nombre d'à-coups pendant le défilement
  var FIRST_DELAY = 55;     // ms entre les premiers changements (rapide)
  var LAST_DELAY = 290;     // ms entre les derniers (lent : on voit les derniers plats passer)
  var IMAGE_TIMEOUT = 2500; // on n'attend jamais une image plus longtemps que ça
  var FETCH_TIMEOUT = 8000; // au-delà, le tirage est considéré comme raté
  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var busy = false;

  var initial;
  try {
    initial = JSON.parse(dataEl.textContent);
  } catch (e) {
    root.dataset.state = 'result'; // données illisibles : on garde l'affichage du serveur
    return;
  }

  // --- Outils ---------------------------------------------------------------------

  function wait(ms) {
    return new Promise(function (resolve) { setTimeout(resolve, ms); });
  }

  /** Charge une image en mémoire ; résout même en cas d'erreur ou de lenteur. */
  function preload(src) {
    return new Promise(function (resolve) {
      var img = new Image();
      var done = function () { resolve(); };
      img.onload = done;
      img.onerror = done;
      setTimeout(done, IMAGE_TIMEOUT);
      img.src = src;
    });
  }

  function setPicture(src) {
    fields.picture.onerror = function () {
      this.onerror = null;
      this.src = PLACEHOLDER;
    };
    fields.picture.src = src || PLACEHOLDER;
  }

  /** Une image du défilement : seulement la photo et le nom, avec un à-coup. */
  function showFrame(dish) {
    setPicture(dish.picture);
    fields.name.textContent = dish.name;
    card.classList.remove('is-tick');
    void card.offsetWidth; // force le navigateur à relancer l'animation de l'à-coup
    card.classList.add('is-tick');
  }

  /** Le gagnant, avec toutes ses informations. */
  function showWinner(dish) {
    setPicture(dish.picture);
    fields.name.textContent = dish.name;
    fields.category.textContent = dish.category;
    fields.category.className = 'tag tone-' + dish.tone + ' draw-card__reveal';
    fields.place.textContent = dish.place;
    fields.description.textContent = dish.description;
    fields.price.textContent = dish.price;
    card.classList.remove('is-tick');
  }

  /**
   * Délais du défilement : courts au début, longs à la fin (décélération).
   * La puissance 2,2 garde la vitesse longtemps puis freine nettement sur les derniers plats.
   */
  function delays(count) {
    var list = [];
    for (var i = 0; i < count; i++) {
      var t = count > 1 ? i / (count - 1) : 1;
      list.push(Math.round(FIRST_DELAY + Math.pow(t, 2.2) * (LAST_DELAY - FIRST_DELAY)));
    }
    return list;
  }

  /** Les plats du défilement, répétés si besoin pour remplir les FRAMES à-coups. */
  function frames(reel) {
    var list = [];
    for (var i = 0; reel.length && list.length < FRAMES; i++) {
      list.push(reel[i % reel.length]);
    }
    return list;
  }

  function setBusy(on) {
    busy = on;
    if (on) {
      root.dataset.busy = '';
      retry.setAttribute('aria-disabled', 'true');
    } else {
      delete root.dataset.busy;
      retry.removeAttribute('aria-disabled');
    }
  }

  function showError(message) {
    errorEl.textContent = message;
    errorEl.hidden = false;
  }

  // --- Le tirage animé ---------------------------------------------------------------

  async function play(result) {
    var reel = frames(result.reel || []);
    // Images d'abord : défilement et révélation ne doivent jamais attendre le réseau.
    await Promise.all([result.winner].concat(reel).map(function (d) { return preload(d.picture); }));

    // Un seul plat en lice, ou mouvements réduits : pas de faux suspense, on révèle directement.
    if (reel.length && !reducedMotion) {
      root.dataset.state = 'spinning';
      statusEl.textContent = 'Le destin réfléchit…';
      var steps = delays(reel.length);
      for (var i = 0; i < reel.length; i++) {
        showFrame(reel[i]);
        await wait(steps[i]);
      }
    }

    showWinner(result.winner);
    root.dataset.state = 'revealed';
    // Le statut est lu par les lecteurs d'écran (role="status") : on y annonce le plat gagnant.
    statusEl.textContent = result.total > 1
      ? 'Le destin a choisi ' + result.winner.name + ', parmi ' + result.total + ' plats.'
      : 'Un seul plat en lice : le destin n’avait pas le choix !';
  }

  /** Nouveau tirage demandé au serveur, puis animé. */
  async function redraw() {
    if (busy) return;
    setBusy(true);
    errorEl.hidden = true;
    statusEl.textContent = 'Tirage en cours…';

    var controller = new AbortController();
    var timer = setTimeout(function () { controller.abort(); }, FETCH_TIMEOUT);
    var result;
    try {
      var response = await fetch(root.dataset.api, {
        headers: { Accept: 'application/json' },
        cache: 'no-store',
        signal: controller.signal
      });
      var data = await response.json().catch(function () { return null; });
      if (!response.ok || !data || !data.winner) {
        throw new Error((data && data.message) || 'Le tirage n’a pas pu être fait. Réessaie dans un instant.');
      }
      result = data;
    } catch (e) {
      // Réseau coupé, délai dépassé, erreur serveur, plus aucun plat : la carte précédente reste.
      showError(e.name === 'AbortError'
        ? 'Le serveur met trop de temps à répondre. Réessaie dans un instant.'
        : (e.message && e.message !== 'Failed to fetch' ? e.message : 'Connexion impossible. Vérifie ta connexion puis réessaie.'));
      statusEl.textContent = '';
      setBusy(false);
      return;
    } finally {
      clearTimeout(timer);
    }

    await play(result);
    setBusy(false);
  }

  // Le lien garde son adresse : sans JavaScript (ou clic molette), il recharge la page = nouveau tirage.
  retry.addEventListener('click', function (event) {
    if (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;
    event.preventDefault();
    redraw();
  });

  // Premier tirage : déjà fait par le serveur, il ne reste qu'à le mettre en scène.
  setBusy(true);
  statusEl.textContent = 'Le destin réfléchit…';
  play(initial).then(function () { setBusy(false); });
})();
