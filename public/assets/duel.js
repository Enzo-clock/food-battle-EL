// Raccourcis clavier de la page duel : ← vote A, → vote B, Espace passe, Entrée duel suivant.
// Amélioration progressive : sans JavaScript, les boutons et liens fonctionnent normalement.
(function () {
  var pickA = document.querySelector('[data-duel-pick="a"]');
  var pickB = document.querySelector('[data-duel-pick="b"]');
  var skip = document.querySelector('[data-duel-skip]');
  var next = document.querySelector('[data-duel-next]');
  var sent = false;

  document.addEventListener('keydown', function (event) {
    if (sent || event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) return;
    var tag = (event.target.tagName || '').toLowerCase();
    if (tag === 'input' || tag === 'textarea' || tag === 'select' || event.target.isContentEditable) return;

    var target = null;
    if (event.key === 'ArrowLeft') target = pickA;
    else if (event.key === 'ArrowRight') target = pickB;
    else if (event.key === ' ' && tag !== 'button' && tag !== 'a') target = skip;
    else if (event.key === 'Enter' && tag !== 'button' && tag !== 'a') target = next;
    if (!target) return;

    event.preventDefault();
    sent = true; // évite un double vote si la touche est maintenue
    target.click();
  });
})();
