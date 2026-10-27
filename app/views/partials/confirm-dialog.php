<?php
// filepath: app/views/partials/confirm-dialog.php
// Fenêtre de confirmation partagée par tous les boutons [data-confirm] de la page (confirm.js).
// Sans JavaScript, ces boutons mènent à la page de confirmation : la fenêtre reste inutilisée.
// Variable : $returnUrl
?>
<dialog class="confirm-dialog" aria-labelledby="confirm-title" data-confirm-dialog>
    <form method="post" class="confirm-dialog__body">
        <input type="hidden" name="token" value="<?= h(csrf()) ?>">
        <input type="hidden" name="retour" value="<?= h($returnUrl) ?>">
        <h2 class="confirm-dialog__title" id="confirm-title" data-confirm-dialog-title></h2>
        <p class="confirm-box__text" data-confirm-dialog-text></p>
        <div class="confirm-box__actions">
            <button class="btn btn--ghost" type="button" value="cancel" data-confirm-dialog-cancel>Annuler</button>
            <button class="btn" type="submit" data-confirm-dialog-submit></button>
        </div>
    </form>
</dialog>
<script src="/assets/confirm.js" defer></script>
