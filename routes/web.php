<?php
// filepath: foodbattle-starter/routes/web.php

require_once __DIR__ . '/../app/includes/functions.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Tout ce qui est sous /admin est réservé aux administrateurs.
// Une seule vérification ici, avant toute route : une nouvelle page admin est protégée d'office.
if ($path === '/admin' || str_starts_with($path, '/admin/')) {
    require_login();
    if (!is_admin()) {
        abort_page(403);
    }
}

// Modifier, supprimer, restaurer : /plat/12/modifier, /duel/5/supprimer, /plat/12/restaurer…
// Le contrôleur vérifie ensuite que l'utilisateur est le créateur de la ressource, ou admin.
$manageRoutes = [
    'plat'       => ['DishController', ['modifier' => 'edit', 'supprimer' => 'delete', 'restaurer' => 'restore']],
    'restaurant' => ['EstablishmentController', ['modifier' => 'edit', 'supprimer' => 'delete']],
    'categorie'  => ['CategoryController', ['modifier' => 'edit', 'supprimer' => 'delete']],
    'duel'       => ['BattleCreationController', ['supprimer' => 'delete']],
    'tournoi'    => ['TournamentCreationController', ['modifier' => 'edit', 'supprimer' => 'delete']],
];
if (preg_match('#^/([a-z]+)/(\d+)/([a-z]+)$#', $path, $m) && isset($manageRoutes[$m[1]][1][$m[3]])) {
    [$controller, $actions] = $manageRoutes[$m[1]];
    $action = $actions[$m[3]];
    // restaurer agit tout de suite (POST seul) ; modifier et supprimer ont un écran (GET) puis l'action (POST)
    $allowed = $action === 'restore' ? ['POST'] : ['GET', 'POST'];
    if (!in_array($method, $allowed, true)) {
        http_response_code(405);
        exit('Méthode non autorisée');
    }
    require_once __DIR__ . '/../app/controllers/' . $controller . '.php';
    (new $controller($db))->$action((int) $m[2]);
    exit;
}

if ($path === '/admin' && $method === 'GET') {
    require_once __DIR__ . '/../app/controllers/AdminController.php';
    (new AdminController($db))->index();
    exit;
}

// Modération des catégories proposées par les utilisateurs
if (preg_match('#^/admin/categories/(\d+)/(valider|refuser)$#', $path, $m) && $method === 'POST') {
    require_once __DIR__ . '/../app/controllers/AdminController.php';
    $action = $m[2] === 'valider' ? 'approveCategory' : 'rejectCategory';
    (new AdminController($db))->$action((int) $m[1]);
    exit;
}

if (preg_match('#^/admin/utilisateurs/(\d+)/role$#', $path, $m) && $method === 'POST') {
    require_once __DIR__ . '/../app/controllers/AdminController.php';
    (new AdminController($db))->setRole((int) $m[1]);
    exit;
}

if ($path === '/') {
    require_once __DIR__ . '/../app/controllers/HomeController.php';
    (new HomeController())->index();
    exit;
}

if ($path === '/duel' && $method === 'GET') {
    require_once __DIR__ . '/../app/controllers/DuelController.php';
    (new DuelController())->show();
    exit;
}

if ($path === '/duel/vote' && $method === 'POST') {
    require_once __DIR__ . '/../app/controllers/DuelController.php';
    (new DuelController())->vote();
    exit;
}

if ($path === '/signup' && in_array($method, ['GET', 'POST'], true)) {
    require_once __DIR__ . '/../app/controllers/AuthController.php';
    (new AuthController($db))->signup();
    exit;
}

if ($path === '/login' && in_array($method, ['GET', 'POST'], true)) {
    require_once __DIR__ . '/../app/controllers/AuthController.php';
    (new AuthController($db))->login();
    exit;
}

if ($path === '/logout' && $method === 'POST') {
    check_post();                       // vérifie le jeton CSRF
    $_SESSION = [];
    session_regenerate_id(true);
    header('Location: /', true, 303);
    exit;
}

if ($path === '/restaurant/creer' && in_array($method, ['GET', 'POST'], true)) {
    require_once __DIR__ . '/../app/controllers/EstablishmentController.php';
    (new EstablishmentController($db))->create();
    exit;
}

if ($path === '/plat/creer' && in_array($method, ['GET', 'POST'], true)) {
    require_once __DIR__ . '/../app/controllers/DishController.php';
    (new DishController($db))->create();
    exit;
}

if ($path === '/categorie/creer' && in_array($method, ['GET', 'POST'], true)) {
    require_once __DIR__ . '/../app/controllers/CategoryController.php';
    (new CategoryController($db))->create();
    exit;
}

if ($path === '/dashboard' && $method === 'GET') {
    require_once __DIR__ . '/../app/controllers/DashboardController.php';
    (new DashboardController($db))->index();
    exit;
}

// Pages de contenu fixe (mentions légales, confidentialité, contact)
$pages = [
    '/mentions-legales' => 'legal',
    '/confidentialite'  => 'privacy',
    '/contact'          => 'contact',
];
if (isset($pages[$path]) && $method === 'GET') {
    require_once __DIR__ . '/../app/controllers/PageController.php';
    (new PageController())->{$pages[$path]}();
    exit;
}

if ($path === '/duel/creer' && in_array($method, ['GET', 'POST'], true)) {
    require_once __DIR__ . '/../app/controllers/BattleCreationController.php';
    (new BattleCreationController($db))->create();
    exit;
}
if ($path === '/tournoi/creer' && in_array($method, ['GET', 'POST'], true)) {
    require_once __DIR__ . '/../app/controllers/TournamentCreationController.php';
    (new TournamentCreationController($db))->create();
    exit;
}

abort_page(404);