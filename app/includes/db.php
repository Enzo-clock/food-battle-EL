<?php
$db = new PDO(
    'pgsql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';port=' . (getenv('DB_PORT') ?: '5432') . ';dbname=' . (getenv('DB_NAME') ?: 'foodbattle'),
    getenv('DB_USER') ?: 'foodbattle',
    getenv('DB_PASSWORD') ?: 'foodbattle_local',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
