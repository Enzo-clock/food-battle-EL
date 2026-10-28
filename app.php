<?php
// filepath: foodbattle-starter/app.php
$appEnv = getenv('APP_ENV') ?: 'development';

if (!in_array($appEnv, ['development', 'production'], true)) {
    throw new RuntimeException('APP_ENV doit valoir development ou production.');
}

$secureSetting = getenv('SESSION_COOKIE_SECURE');

$secureCookie = ($secureSetting === false || $secureSetting === '')
    ? $appEnv === 'production'
    : filter_var(
        $secureSetting,
        FILTER_VALIDATE_BOOLEAN,
        FILTER_NULL_ON_FAILURE
    );

if ($secureCookie === null || ($appEnv === 'production' && !$secureCookie)) {
    throw new RuntimeException('SESSION_COOKIE_SECURE doit être true en production.');
}

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/app/includes/db.php';
