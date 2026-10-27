<?php
// filepath: app/views/partials/item-actions.php
// Actions d'une ressource dans le dashboard et le back-office : Modifier, Supprimer / Archiver, Restaurer.
// Un bouton n'est affiché que si l'action sera acceptée (mêmes règles que le serveur, rules.php) ;
// sinon, un cadenas et la raison, plutôt qu'un bouton qui mènerait à un refus.
// Variables : $type ('plat'…), $row, $returnUrl, $canEdit (le type a-t-il une page de modification ?)

$base = '/' . $type . '/' . (int) $row['id'];
$back = '?retour=' . rawurlencode($returnUrl);
$archived = ($row['archived_at'] ?? null) !== null;
$plan = $archived ? null : deletion_plan($type, $row);
?>
<div class="dash-actions">
    <?php if ($canEdit): ?>
        <a class="btn btn--xs btn--ghost" href="<?= h($base . '/modifier' . $back) ?>">Modifier</a>
    <?php endif ?>

    <?php if ($archived): ?>
        <form method="post" action="<?= h($base) ?>/restaurer">
            <input type="hidden" name="token" value="<?= h(csrf()) ?>">
            <input type="hidden" name="retour" value="<?= h($returnUrl) ?>">
            <button class="btn btn--xs btn--dark" type="submit">Restaurer</button>
        </form>
    <?php elseif ($plan['mode'] === 'blocked'): ?>
        <span class="dash-lock" title="<?= h($plan['message']) ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"
                stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            <span class="visually-hidden">Suppression impossible :</span> <?= h(deletion_lock_reason($type, $row)) ?>
        </span>
    <?php else: ?>
        <a class="btn btn--xs <?= $plan['mode'] === 'delete' ? 'btn--danger-ghost' : 'btn--ghost' ?>"
            href="<?= h($base . '/supprimer' . $back) ?>"
            data-confirm
            data-confirm-action="<?= h($base) ?>/supprimer"
            data-confirm-title="<?= h($plan['label'] . ' « ' . ($row['name'] ?? (($row['a_name'] ?? '') . ' vs ' . ($row['b_name'] ?? ''))) . ' » ?') ?>"
            data-confirm-text="<?= h($plan['message']) ?>"
            data-confirm-label="<?= h($plan['label']) ?>"
            data-confirm-mode="<?= h($plan['mode']) ?>"><?= h($plan['label']) ?></a>
    <?php endif ?>
</div>
