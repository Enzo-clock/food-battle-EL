<?php
// filepath: app/views/partials/moderation-status.php
// État de modération d'une proposition, en haut de sa page de modification.
// Variable : $row (status, rejection_reason)
if (($row['status'] ?? 'approved') === 'approved') {
    return;
}
?>
<?php if ($row['status'] === 'pending'): ?>
    <p class="dash-flash dash-flash--pending">
        <strong>En attente de validation.</strong> Tu peux encore corriger ta proposition ; elle sera publiée dès qu’un administrateur l’aura validée.
    </p>
<?php else: ?>
    <p class="dash-flash dash-flash--error">
        <strong>Proposition refusée.</strong>
        <?php if ($row['rejection_reason']): ?>Motif : <?= h($row['rejection_reason']) ?>.<?php endif ?>
        Corrige-la : elle repartira en validation.
    </p>
<?php endif ?>
