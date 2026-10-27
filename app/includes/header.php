<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions.php';

// Avec le front controller, SCRIPT_NAME vaut toujours index.php :
// on compare donc le chemin de l'URL pour savoir quel lien est actif.
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$navIsLogged = isset($_SESSION['user_id']);
$navLinks = [
    '/'               => 'Accueil',
    /*'/duel'           => 'Duels',*/
    '/classement.php' => 'Classement',
];
// Le dashboard liste les contenus de l'utilisateur : le lien n'apparaît qu'une fois connecté.
if ($navIsLogged) {
    $navLinks['/dashboard'] = 'Dashboard';
}
$navUsername = $_SESSION['username'] ?? null;
$navName = $_SESSION['username'] ?? '';
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

                <?php if ($navIsLogged): ?>
                    <span class="site-nav__user">Salut <?= h($navName ?? '') ?> !</span>
                    <form class="site-nav__logout" method="post" action="/logout">
                        <input type="hidden" name="token" value="<?= h(csrf()) ?>">
                        <button class="link-btn" type="submit">Déconnexion</button>
                    </form>
                <?php else: ?>
                    <a href="/login" <?= $currentPath === '/login' ? 'aria-current="page"' : '' ?>>Connexion</a>
                    <a class="site-nav__signup" href="/signup" <?= $currentPath === '/signup' ? 'aria-current="page"' : '' ?>>Inscription</a>
                <?php endif ?>

                <a class="btn btn--dark btn--sm" href="/duel">Jouer</a>
            </nav>
        </div>
    </header>
    <main id="contenu" class="site-main">