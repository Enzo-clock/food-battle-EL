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

http_response_code(404);
echo 'Page introuvable';