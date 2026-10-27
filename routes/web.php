<?php
// filepath: foodbattle-starter/routes/web.php

require_once __DIR__ . '/../app/includes/functions.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

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

if ($path === '/play/tournament' && $method === 'GET') {
    require_once __DIR__ . '/../app/controllers/TournamentPlayController.php';
    (new TournamentPlayController($db))->index();
    exit;
}

if (
    $method === 'GET'
    && preg_match('#^/play/tournament/(\d+)/(Q[1-4]|D[12]|F)$#', $path, $matches)
) {
    require_once __DIR__ . '/../app/controllers/TournamentPlayController.php';
    (new TournamentPlayController($db))->show(
        (int) $matches[1],
        $matches[2]
    );
    exit;
}

if (
    $method === 'POST'
    && preg_match('#^/play/tournament/(\d+)/(Q[1-4]|D[12]|F)/vote$#', $path, $matches)
) {
    require_once __DIR__ . '/../app/controllers/TournamentPlayController.php';
    (new TournamentPlayController($db))->vote(
        (int) $matches[1],
        $matches[2]
    );
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

http_response_code(404);
echo 'Page introuvable';