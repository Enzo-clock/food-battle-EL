// Améliorations des formulaires de création (restaurant, et plus tard plat).
// Amélioration progressive : sans JavaScript, le formulaire fonctionne normalement.
(function () {
  // 1. Compteur de caractères sous les champs qui ont maxlength + data-counter
  document.querySelectorAll('[data-counter][maxlength]').forEach(function (field) {
    var max = Number(field.getAttribute('maxlength'));
    var counter = document.createElement('p');
    counter.className = 'field__counter';
    counter.setAttribute('aria-live', 'polite');
    field.insertAdjacentElement('afterend', counter);

    function update() {
      var left = max - field.value.length;
      counter.textContent = field.value.length + ' / ' + max;
      counter.classList.toggle('is-near', left <= max * 0.1);
    }
    field.addEventListener('input', update);
    update();
  });

  // 2. Aperçu de l'image choisie, avant l'envoi
  var input = document.querySelector('[data-preview-input]');
  var preview = document.querySelector('[data-preview]');
  var empty = document.querySelector('[data-preview-empty]');
  if (input && preview && empty) {
    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (preview.src) URL.revokeObjectURL(preview.src); // libère la mémoire de l'aperçu précédent
      if (!file) {
        preview.hidden = true;
        empty.hidden = false;
        return;
      }
      preview.src = URL.createObjectURL(file);
      preview.hidden = false;
      empty.hidden = true;
    });
  }

  // Surbrillance de la zone pendant un glisser-déposer
  var zone = document.querySelector('[data-dropzone]');
  if (zone) {
    ['dragenter', 'dragover'].forEach(function (type) {
      zone.addEventListener(type, function () { zone.classList.add('is-dragover'); });
    });
    ['dragleave', 'drop'].forEach(function (type) {
      zone.addEventListener(type, function () { zone.classList.remove('is-dragover'); });
    });
  }

  // Listes défilantes (catégories) : dégradé en bas tant qu'il reste des éléments cachés
  document.querySelectorAll('[data-scroll-fade]').forEach(function (list) {
    function update() {
      var scrollable = list.scrollHeight > list.clientHeight + 1;
      list.classList.toggle('is-scrollable', scrollable);
      list.classList.toggle('is-at-end', list.scrollTop + list.clientHeight >= list.scrollHeight - 2);
    }
    list.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    update();
  });

  // Compteur de cases cochées (« 2 sélectionnées »), utile quand la liste défile
  document.querySelectorAll('[data-choice-group]').forEach(function (group) {
    var counter = group.parentElement.querySelector('[data-choice-count]');
    if (!counter) return;
    function update() {
      var n = group.querySelectorAll('input[type="checkbox"]:checked').length;
      counter.textContent = n === 0 ? 'Aucune catégorie sélectionnée'
        : n + ' catégorie' + (n > 1 ? 's sélectionnées' : ' sélectionnée');
    }
    group.addEventListener('change', update);
    update();

    // Case pré-cochée hors de la zone visible (ex. catégorie qu'on vient de créer) : on la montre
    var firstChecked = group.querySelector('input[type="checkbox"]:checked');
    if (firstChecked) {
      var item = firstChecked.closest('label') || firstChecked;
      if (item.offsetTop + item.offsetHeight > group.clientHeight) {
        group.scrollTop = item.offsetTop - group.clientHeight / 2;
      }
    }
  });

  // 3. Évite le double envoi (double clic, connexion lente)
  document.querySelectorAll('form[data-lock-submit]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var button = form.querySelector('[type="submit"]');
      if (!button) return;
      button.disabled = true;
      if (button.dataset.submitLabel) button.textContent = button.dataset.submitLabel;
    });
  });

  // 4. Après une erreur, place le focus sur le message pour qu'il soit lu en premier
  var alertBox = document.querySelector('[data-autofocus]');
  if (alertBox) alertBox.focus();
})();