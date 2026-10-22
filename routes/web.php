<?php
// filepath: foodbattle-starter/routes/web.php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path === '/') {
    require_once __DIR__ . '/../app/controllers/HomeController.php';
    (new HomeController())->index();
    exit;
}

if ($path === '/signup' && in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    require_once __DIR__ . '/../app/controllers/AuthController.php';
    (new AuthController($db))->signup();
    exit;
}

http_response_code(404);
echo 'Page introuvable';