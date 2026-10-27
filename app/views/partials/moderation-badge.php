<?php
// filepath: app/views/partials/moderation-badge.php
// Statut d'une proposition dans les listes. Variables : $row (status), $feminine (accord : « Refusée »)
$feminine ??= false;
if ($row['status'] === 'pending'): ?>
    <span class="dash-status dash-status--pending">En attente de validation</span>
<?php elseif ($row['status'] === 'rejected'): ?>
    <span class="dash-status dash-status--rejected"><?= $feminine ? 'Refusée' : 'Refusé' ?></span>
<?php endif;
