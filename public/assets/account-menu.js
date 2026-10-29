// Menu du compte (header) : complète le <details> natif, qui s'ouvre et se ferme déjà sans script.
// Ajoute ce qu'on attend d'un menu : fermeture au clic en dehors et à la touche Échap
// (le focus revient alors sur le bouton du compte, pour les utilisateurs du clavier).
(function () {
  var menu = document.querySelector('[data-account-menu]');
  if (!menu) return;
  var toggle = menu.querySelector('summary');

  document.addEventListener('click', function (event) {
    if (menu.open && !menu.contains(event.target)) {
      menu.open = false;
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && menu.open) {
      menu.open = false;
      toggle.focus();
    }
  });
})();
