<?php
// filepath: app/views/partials/review-actions.php
// File de modération (back-office) : valider, ou refuser avec un motif lu par l'auteur.
// Sans JavaScript : le motif se déplie avec <details>.
// Variables : $reviewType ('plats' | 'restaurants' | 'categories'), $row (id, creator)
$reviewUrl = '/admin/' . $reviewType . '/' . (int) $row['id'];
$reasonId = 'reason-' . $reviewType . '-' . (int) $row['id'];
$placeholder = [
    'plats'       => 'Ex. Ce plat existe déjà : « Le Double Smash », au Comptoir Smash.',
    'restaurants' => 'Ex. Adresse introuvable : vérifie le numéro et la ville.',
    'categories'  => 'Ex. C’est un régime, pas un type de plat : ajoute ton plat dans sa catégorie.',
][$reviewType];
?>
<div class="dash-review">
    <form method="post" action="<?= h($reviewUrl) ?>/valider">
        <input type="hidden" name="token" value="<?= h(csrf()) ?>">
        <button class="btn btn--xs btn--valid" type="submit">Valider</button>
    </form>
    <details class="dash-reject">
        <summary class="btn btn--xs btn--danger-ghost">Refuser</summary>
        <form method="post" action="<?= h($reviewUrl) ?>/refuser" class="dash-reject__form">
            <input type="hidden" name="token" value="<?= h(csrf()) ?>">
            <label for="<?= h($reasonId) ?>">Motif, lu par <?= h($row['creator']) ?></label>
            <textarea id="<?= h($reasonId) ?>" name="reason" rows="3" maxlength="255" required
                placeholder="<?= h($placeholder) ?>"></textarea>
            <button class="btn btn--xs btn--danger" type="submit">Confirmer le refus</button>
        </form>
    </details>
</div>
