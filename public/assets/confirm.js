// Confirmation de suppression en fenêtre modale (dashboard, back-office).
// Amélioration progressive : sans JavaScript (ou sans <dialog>), le lien ouvre la page de confirmation.
// Le texte affiché vient du serveur (rules.php) : la modale annonce exactement ce qui va se passer.
(function () {
  var dialog = document.querySelector('[data-confirm-dialog]');
  if (!dialog || typeof dialog.showModal !== 'function') return;

  var form = dialog.querySelector('form');
  var title = dialog.querySelector('[data-confirm-dialog-title]');
  var text = dialog.querySelector('[data-confirm-dialog-text]');
  var submit = dialog.querySelector('[data-confirm-dialog-submit]');
  var cancel = dialog.querySelector('[data-confirm-dialog-cancel]');
  var opener = null;

  document.querySelectorAll('[data-confirm]').forEach(function (link) {
    link.addEventListener('click', function (event) {
      event.preventDefault();
      opener = link;
      form.action = link.dataset.confirmAction;
      title.textContent = link.dataset.confirmTitle;
      text.textContent = link.dataset.confirmText;
      submit.textContent = link.dataset.confirmLabel;
      // Suppression définitive en rouge, archivage (réversible) en noir
      submit.className = 'btn ' + (link.dataset.confirmMode === 'delete' ? 'btn--danger' : 'btn--dark');
      dialog.showModal();
      cancel.focus(); // l'action sans danger a le focus : Entrée n'efface rien par accident
    });
  });

  cancel.addEventListener('click', function () { dialog.close(); });

  // Clic sur le fond assombri : on ferme
  dialog.addEventListener('click', function (event) {
    if (event.target === dialog) dialog.close();
  });

  // Le focus revient sur le bouton d'origine (accessibilité clavier)
  dialog.addEventListener('close', function () {
    if (opener) opener.focus();
  });

  form.addEventListener('submit', function () {
    submit.disabled = true;
  });
})();
