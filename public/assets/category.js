// Page « Ajouter une catégorie » : aperçu en direct et détection des doublons pendant la saisie.
// Amélioration progressive : le serveur refait toutes les vérifications.
(function () {
  var form = document.querySelector('[data-category-form]');
  if (!form) return;

  var nameInput = form.querySelector('[data-category-name]');
  var suggest = form.querySelector('[data-category-suggest]');
  var preview = form.querySelector('[data-category-preview]');
  var colors = form.querySelectorAll('[data-category-color]');
  var existing = [];
  try {
    existing = JSON.parse(form.dataset.existing || '[]');
  } catch (e) {
    existing = [];
  }

  // Même règle que slugify() en PHP : minuscules, sans accents, tirets.
  function slugify(text) {
    return text
      .toLowerCase()
      .replace(/&/g, '-et-')
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // retire les accents
      .replace(/œ/g, 'oe').replace(/æ/g, 'ae')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  // Même règle que category_key() en PHP : sans le pluriel de chaque mot.
  function keyOf(text) {
    return slugify(text).split('-').map(function (w) {
      return w.length > 3 ? w.replace(/[sx]$/, '') : w;
    }).join('-');
  }

  function updateSuggest() {
    var slug = slugify(nameInput.value);
    var key = keyOf(nameInput.value);
    suggest.textContent = '';
    suggest.className = 'category-suggest';
    if (slug.length < 2) return;

    var exact = existing.filter(function (c) {
      return keyOf(c.slug) === key || keyOf(c.label) === key;
    })[0];
    if (exact) {
      suggest.textContent = '« ' + exact.label + ' » existe déjà : ajoute plutôt ton plat dedans.';
      suggest.classList.add('is-error');
      return;
    }

    // Catégories proches : l'une contient l'autre (« pate » / « pates », « indien » / « plats-indiens »)
    var close = existing.filter(function (c) {
      return slug.length >= 3 && (c.slug.indexOf(slug) !== -1 || slug.indexOf(c.slug) !== -1);
    });
    if (close.length) {
      suggest.textContent = 'Catégorie proche : ' + close.map(function (c) { return '« ' + c.label + ' »'; }).join(', ') + '. Est-ce la même ?';
      suggest.classList.add('is-warning');
    }
  }

  function updatePreview() {
    preview.textContent = nameInput.value.trim() || 'Ta catégorie';
    var checked = form.querySelector('[data-category-color]:checked');
    preview.className = 'chip category-preview__chip' + (checked ? ' chip--' + checked.value : '');
  }

  nameInput.addEventListener('input', function () {
    updateSuggest();
    updatePreview();
  });
  colors.forEach(function (radio) {
    radio.addEventListener('change', updatePreview);
  });

  updateSuggest();
  updatePreview();
})();
