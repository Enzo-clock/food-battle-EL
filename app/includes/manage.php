<?php
// filepath: app/includes/manage.php
//
// Parcours commun « supprimer une ressource », pour les 5 types (plat, restaurant, catégorie, duel, tournoi) :
//   GET  /<type>/<id>/supprimer → page de confirmation qui annonce la conséquence exacte
//   POST /<type>/<id>/supprimer → action, message, puis retour au dashboard ou au back-office (PRG)
// Avec JavaScript, la confirmation s'ouvre en fenêtre modale (dashboard, back-office) ;
// sans JavaScript, c'est cette page. Le serveur revérifie tout dans les deux cas.

require_once __DIR__ . '/functions.php';

/**
 * Charge la ressource ou affiche 404 ; vérifie que l'utilisateur peut la gérer, sinon 403.
 * Le contrôle de propriété est fait côté serveur : changer l'id dans l'URL ne donne aucun droit (IDOR).
 */
function load_managed(?array $row): array
{
    require_login();
    if ($row === null) {
        abort_page(404);
    }
    if (!can_manage($row)) {
        abort_page(403);
    }
    return $row;
}

/**
 * @param string   $type    'plat' | 'restaurant' | 'categorie' | 'duel' | 'tournoi'
 * @param array    $row     la ressource, avec les compteurs attendus par deletion_plan()
 * @param string   $name    nom affiché (« Le Double Smash », « Burger A vs Burger B »…)
 * @param callable $perform fn(string $mode): bool|string — exécute l'action, false si la base a refusé
 */
function handle_deletion(string $type, array $row, string $name, callable $perform): never
{
    $tabs = ['plat' => 'plats', 'restaurant' => 'restaurants', 'categorie' => 'categories', 'duel' => 'duels', 'tournoi' => 'tournois'];
    $default = '/dashboard?onglet=' . $tabs[$type];
    $return = safe_return($_POST['retour'] ?? $_GET['retour'] ?? null, $default);
    $plan = deletion_plan($type, $row);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $title = $plan['label'] . ' · ' . $name;
        require __DIR__ . '/../views/confirm-delete.php';
        exit;
    }

    check_post();

    if ($plan['mode'] === 'blocked') {
        flash_set('Suppression refusée pour « ' . $name . ' ». ' . $plan['message'], 'error');
    } else {
        $result = $perform($plan['mode']);
        if ($result === false) {
            // L'état a changé entre l'affichage et la confirmation (un vote est arrivé, par exemple).
            flash_set('Suppression refusée pour « ' . $name . ' » : il vient d’être utilisé entre-temps.', 'error');
        } elseif ($result === 'archived') {
            flash_set('Archivage effectué : « ' . $name . ' » n’apparaît plus dans les listes ni dans les nouveaux duels.');
        } else {
            flash_set('Suppression effectuée : « ' . $name . ' ».');
        }
    }

    header('Location: ' . $return, true, 303);
    exit;
}
