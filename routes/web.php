<?php
// filepath: foodbattle-starter/routes/web.php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path === '/') {
    require_once __DIR__ . '/../app/controllers/HomeController.php';
    (new HomeController())->index();
    exit;
}

http_response_code(404);
echo 'Page introuvable';