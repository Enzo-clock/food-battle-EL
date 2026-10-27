<?php
// filepath: app/controllers/DashboardController.php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Dashboard.php';
require_once __DIR__ . '/../includes/rules.php';

class DashboardController
{
    /** Pastilles « à traiter » par onglet (back-office : catégories en attente). */
    protected array $alerts = [];

    public function __construct(protected PDO $db)
    {
    }

    /**
     * Onglets du dashboard, dans le même ordre que le dock : on joue (duel, tournoi), puis on contribue.
     * Les icônes reprennent celles du dock pour qu'un même type de ressource se reconnaisse partout.
     */
    public static function tabs(): array
    {
        return [
            'duels' => [
                'label'  => 'Duels',
                'title'  => 'Mes duels',
                'create' => ['href' => '/duel/creer', 'label' => 'Créer un duel'],
                'icon'   => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
            ],
            'tournois' => [
                'label'  => 'Tournois',
                'title'  => 'Mes tournois',
                'create' => ['href' => '/tournoi/creer', 'label' => 'Créer un tournoi'],
                'icon'   => '<path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0z"/><path d="M17 6h3a3 3 0 0 1-3 4M7 6H4a3 3 0 0 0 3 4"/>',
            ],
            'plats' => [
                'label'  => 'Plats',
                'title'  => 'Mes plats',
                'create' => ['href' => '/plat/creer', 'label' => 'Ajouter un plat'],
                'icon'   => '<path d="M4 11a8 7 0 0 1 16 0z"/><path d="M9.5 7.5h.01M12.5 6.5h.01M14.5 8.5h.01"/><path d="M3 14.5c1.5 0 1.5 1 3 1s1.5-1 3-1 1.5 1 3 1 1.5-1 3-1 1.5 1 3 1 1.5-1 3-1"/><path d="M4 18h16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/>',
            ],
            'restaurants' => [
                'label'  => 'Restaurants',
                'title'  => 'Mes restaurants',
                'create' => ['href' => '/restaurant/creer', 'label' => 'Ajouter un restaurant'],
                'icon'   => '<path d="M4 10v10h16V10"/><path d="M3 10l2-6h14l2 6"/><path d="M10 20v-5h4v5"/>',
            ],
            'categories' => [
                'label'  => 'Catégories',
                'title'  => 'Mes catégories',
                'create' => ['href' => '/categorie/creer', 'label' => 'Ajouter une catégorie'],
                'icon'   => '<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9z"/><path d="M7.5 7.5h.01"/>',
            ],
        ];
    }

    /** GET /dashboard : les ressources créées par l'utilisateur connecté, un onglet par type. */
    public function index(): void
    {
        require_login();

        $userId = current_user_id();
        $this->render('user', self::tabs(), ['user' => $userId], (new Dashboard($this->db))->counts($userId));
    }

    /**
     * Partie commune au dashboard et au back-office (même vue, mêmes composants).
     * $context : 'user' (mes contenus) ou 'admin' (tous les contenus, avec recherche et créateur).
     */
    protected function render(string $context, array $tabs, array $filter, array $counts): void
    {
        $dashboard = new Dashboard($this->db);

        // Onglet demandé (?onglet=plats). Sans choix explicite : le premier onglet qui a du contenu,
        // pour ne pas ouvrir sur une liste vide quand l'utilisateur a déjà créé autre chose.
        $requested = (string) ($_GET['onglet'] ?? '');
        if (isset($tabs[$requested])) {
            $active = $requested;
        } else {
            $active = array_key_first(array_filter(array_intersect_key($counts, $tabs))) ?? array_key_first($tabs);
        }

        // Seul l'onglet affiché est chargé : les autres n'ont besoin que de leur compteur.
        $items = match ($active) {
            'duels'        => $dashboard->duels($filter),
            'tournois'     => $dashboard->tournaments($filter),
            'plats'        => $dashboard->dishes($filter),
            'restaurants'  => $dashboard->establishments($filter),
            'categories'   => $dashboard->categories($filter),
            'utilisateurs' => $dashboard->users($filter['q'] ?? ''),
        };

        $total = array_sum(array_intersect_key($counts, self::tabs()));
        $flash = flash_take();
        $alerts = $this->alerts;
        $q = $filter['q'] ?? '';
        $title = $context === 'admin' ? 'Back-office' : 'Dashboard';
        require __DIR__ . '/../views/dashboard.php';
    }
}
