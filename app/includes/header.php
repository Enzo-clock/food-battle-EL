<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions.php';

// Avec le front controller, SCRIPT_NAME vaut toujours index.php :
// on compare donc le chemin de l'URL pour savoir quel lien est actif.
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$navLinks = [
    '/'               => 'Accueil',
    '/duel'           => 'Duels',
    '/classement.php' => 'Classement',
];
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= h($title ?? 'FoodBattle') ?> · FoodBattle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap">
    <link rel="stylesheet" href="/assets/style.css">
</head>

<body>
    <a class="skip-link" href="#contenu">Aller au contenu</a>
    <header class="site-header">
        <div class="site-header__inner">
            <a class="logo" href="/" aria-label="FoodBattle, accueil">
                <img class="logo__img" src="/assets/images/logo_fb_v1.svg" alt="" width="40" height="40">
                FoodBattle
            </a>
            <nav class="site-nav" aria-label="Navigation principale">
                <?php foreach ($navLinks as $href => $label): ?>
                    <a href="<?= h($href) ?>" <?= $currentPath === $href ? 'aria-current="page"' : '' ?>><?= h($label) ?></a>
                <?php endforeach ?>
                <a class="btn btn--dark btn--sm" href="/#carte">Voir la carte</a>
            </nav>
        </div>
    </header>
    <main id="contenu" class="site-main">
