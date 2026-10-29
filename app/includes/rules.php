<?php
// filepath: app/includes/rules.php
//
// Règles métier de modification et de suppression, au même endroit.
// Le dashboard, le back-office, les pages de confirmation et les contrôleurs
// les appliquent tous : un bouton affiché correspond toujours à une action acceptée.
//
// Principe : dès qu'une ressource est utilisée par d'autres (duel, tournoi, vote),
// son créateur ne peut plus la détruire. On archive, ou on bloque en expliquant pourquoi.

/**
 * Un plat est « en jeu » dès qu'il figure dans un duel ou un match de tournoi,
 * de n'importe quel utilisateur, avec ou sans votes.
 * $dish['battles'] : nombre de duels et de matchs qui contiennent le plat.
 */
function dish_in_play(array $dish): bool
{
    return (int) ($dish['battles'] ?? 0) > 0;
}

/**
 * Le nom d'un plat en jeu est verrouillé : le changer falsifierait les votes reçus.
 * L'admin peut toujours corriger (faute de frappe signalée, nom injurieux…).
 * La catégorie, elle, n'est jamais modifiable : elle définit la nature du plat.
 */
function dish_name_locked(array $dish): bool
{
    return dish_in_play($dish) && !is_admin();
}

/**
 * Ce que produirait une demande de suppression.
 * Retourne ['mode' => 'delete'|'archive'|'blocked', 'label' => texte du bouton, 'message' => explication].
 */
function deletion_plan(string $type, array $row): array
{
    switch ($type) {
        case 'plat':
            $battles = (int) ($row['battles'] ?? 0);
            if ($battles === 0) {
                return [
                    'mode'    => 'delete',
                    'label'   => 'Supprimer',
                    'message' => 'Ce plat n’a encore joué aucun duel : il sera supprimé définitivement, avec sa photo.',
                ];
            }
            return [
                'mode'    => 'archive',
                'label'   => 'Archiver',
                'message' => 'Ce plat est déjà en jeu (' . plural($battles, 'duel ou match', 'duels ou matchs') . ') : '
                    . 'il sera archivé, pas supprimé. Il disparaîtra des listes et des nouveaux duels, '
                    . 'mais ses votes et les tournois où il figure restent intacts. Tu pourras le restaurer.',
            ];

        case 'restaurant':
            $dishes = (int) ($row['dishes'] ?? 0);
            return $dishes === 0
                ? ['mode' => 'delete', 'label' => 'Supprimer', 'message' => 'Ce restaurant n’a aucun plat : il sera supprimé définitivement.']
                : ['mode' => 'blocked', 'label' => 'Supprimer', 'message' => 'Ce restaurant a ' . plural($dishes, 'plat')
                    . ' rattaché' . ($dishes > 1 ? 's' : '') . ' (archivés compris). Supprime-les ou rattache-les à un autre restaurant d’abord.'];

        case 'categorie':
            $dishes = (int) ($row['dishes'] ?? 0);
            return $dishes === 0
                ? ['mode' => 'delete', 'label' => 'Supprimer', 'message' => 'Cette catégorie n’a aucun plat : elle sera supprimée définitivement, avec sa photo.']
                : ['mode' => 'blocked', 'label' => 'Supprimer', 'message' => 'Cette catégorie contient ' . plural($dishes, 'plat')
                    . ' : la supprimer retirerait à ses plats leur famille de duels.'];

        case 'duel':
            $votes = (int) ($row['votes'] ?? 0);
            // Un duel libre est partagé : tous les joueurs du mode aléatoire votent sur la même paire.
            return $votes === 0
                ? ['mode' => 'delete', 'label' => 'Supprimer', 'message' => 'Ce duel n’a reçu aucun vote : il sera supprimé définitivement. Les plats, eux, restent.']
                : ['mode' => 'blocked', 'label' => 'Supprimer', 'message' => 'Ce duel a reçu ' . plural($votes, 'vote')
                    . ' d’autres joueurs : le supprimer effacerait leurs votes.'];

        case 'tournoi':
            $votes = (int) ($row['votes'] ?? 0);
            if (tournament_started($row)) {
                return ['mode' => 'blocked', 'label' => 'Supprimer', 'message' => 'Ce tournoi a commencé : c’est un événement public, il ne peut plus être supprimé.'];
            }
            return $votes === 0
                ? ['mode' => 'delete', 'label' => 'Supprimer', 'message' => 'Ce tournoi n’a pas encore commencé : il sera supprimé avec ses matchs. Tes duels d’origine restent.']
                : ['mode' => 'blocked', 'label' => 'Supprimer', 'message' => 'Ce tournoi a déjà reçu ' . plural($votes, 'vote') . '.'];
    }

    throw new InvalidArgumentException('Type inconnu : ' . $type);
}

/**
 * Motif court d'un blocage, affiché à la place du bouton dans les listes
 * (le détail complet reste dans deletion_plan()['message']).
 */
function deletion_lock_reason(string $type, array $row): string
{
    return match ($type) {
        'restaurant', 'categorie' => plural((int) $row['dishes'], 'plat rattaché', 'plats rattachés'),
        'duel'                    => plural((int) $row['votes'], 'vote reçu', 'votes reçus'),
        'tournoi'                 => tournament_started($row) ? 'tournoi commencé' : plural((int) $row['votes'], 'vote reçu', 'votes reçus'),
        default                   => '',
    };
}

/** Un tournoi a commencé le jour de son premier quart de finale. */
function tournament_started(array $tournament): bool
{
    return $tournament['start_date'] <= date('Y-m-d');
}
