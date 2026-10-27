<?php
// filepath: app/views/partials/moderation-reason.php
// Sous une proposition : le motif du refus, ou l'explication de l'attente (pour son auteur).
// Variables : $row (status, rejection_reason), $isAdmin
if ($row['status'] === 'rejected' && $row['rejection_reason']): ?>
    <p class="dash-reason"><strong>Motif :</strong> <?= h($row['rejection_reason']) ?> <em>Modifie-la pour la renvoyer.</em></p>
<?php elseif ($row['status'] === 'pending' && !$isAdmin): ?>
    <p class="dash-reason">Un administrateur va relire ta proposition. Elle n’est visible que par toi en attendant.</p>
<?php endif;
