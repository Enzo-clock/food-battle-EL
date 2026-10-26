// Création de tournoi : choix des quarts de finale et aperçu du tableau en direct.
// Amélioration progressive : sans JavaScript, les cases se cochent et le serveur vérifie tout
// (4 duels, même catégorie, 8 plats différents).
(function () {
  var form = document.querySelector('[data-tournament]');
  if (!form) return;

  var max = Number(form.dataset.quarters) || 4;
  var cards = Array.prototype.slice.call(form.querySelectorAll('[data-tour-duel]'));
  var slots = Array.prototype.slice.call(form.querySelectorAll('[data-tour-slot]'));
  var status = form.querySelector('[data-tour-status]');

  // Ordre des quarts = ordre des clics. Après une erreur, on reprend l'ordre envoyé (data-seed).
  var order = cards
    .filter(function (card) { return card.querySelector('input').checked; })
    .sort(function (a, b) {
      return Number(a.querySelector('input').dataset.seed || 0) - Number(b.querySelector('input').dataset.seed || 0);
    });

  function dishesOf(card) {
    return card.dataset.dishes.split(',');
  }

  function update() {
    var taken = {};
    order.forEach(function (card) {
      dishesOf(card).forEach(function (id) { taken[id] = true; });
    });

    cards.forEach(function (card) {
      var input = card.querySelector('input');
      var index = order.indexOf(card);
      var picked = index !== -1;
      var conflict = !picked && dishesOf(card).some(function (id) { return taken[id]; });
      var full = !picked && order.length >= max;

      input.checked = picked;
      input.disabled = conflict || full;
      card.classList.toggle('is-picked', picked);
      card.classList.toggle('is-conflict', conflict);
      card.classList.toggle('is-full', full && !conflict);
      card.querySelector('[data-tour-seed]').textContent = picked ? 'Q' + (index + 1) : '';
    });

    slots.forEach(function (slot, i) {
      var card = order[i];
      slot.classList.toggle('is-filled', !!card);
      slot.querySelector('[data-tour-slot-name]').textContent = card ? card.dataset.label : 'À choisir';
    });

    if (status) {
      status.textContent = order.length + ' / ' + max + ' duels choisis';
      status.classList.toggle('is-complete', order.length === max);
    }
  }

  cards.forEach(function (card) {
    card.querySelector('input').addEventListener('change', function (event) {
      if (event.target.checked) {
        order.push(card);
      } else {
        order.splice(order.indexOf(card), 1);
      }
      update();
    });
  });

  // Calendrier des quarts, mis à jour quand la date change
  var dateInput = form.querySelector('[data-tour-date]');
  var schedule = form.querySelector('[data-tour-schedule]');
  var months = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

  function formatDate(date) {
    return date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
  }

  if (dateInput && schedule) {
    dateInput.addEventListener('change', function () {
      var parts = dateInput.value.split('-').map(Number);
      if (parts.length !== 3 || !parts[0]) {
        schedule.textContent = 'Choisis la date du premier match.';
        return;
      }
      var start = new Date(parts[0], parts[1] - 1, parts[2]);
      var end = new Date(parts[0], parts[1] - 1, parts[2] + max - 1);
      schedule.textContent = 'Quarts de finale : un match par jour, du ' + formatDate(start) + ' au ' + formatDate(end) + '.';
    });
  }

  // Envoi : en phase de capture, avant le verrouillage du bouton par form.js.
  document.addEventListener('submit', function (event) {
    if (event.target !== form) return;

    if (order.length !== max) {
      event.preventDefault();
      event.stopPropagation(); // le bouton n'est pas verrouillé : on peut corriger et renvoyer
      if (status) {
        status.textContent = 'Choisis encore ' + (max - order.length) + ' duel' + (max - order.length > 1 ? 's' : '') + ' pour compléter les quarts.';
        status.classList.add('is-error');
        status.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      return;
    }

    // Les cases partent dans l'ordre du HTML : on envoie à la place l'ordre des clics.
    cards.forEach(function (card) { card.querySelector('input').removeAttribute('name'); });
    order.forEach(function (card) {
      var hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'battle_ids[]';
      hidden.value = card.querySelector('input').value;
      form.appendChild(hidden);
    });
  }, true);

  form.addEventListener('change', function () {
    if (status) status.classList.remove('is-error');
  });

  update();
})();
