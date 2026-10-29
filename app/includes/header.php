<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions.php';

// Avec le front controller, SCRIPT_NAME vaut toujours index.php :
// on compare donc le chemin de l'URL pour savoir quel lien est actif.
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$navActive = static fn(string $href): string => $currentPath === $href ? 'aria-current="page"' : '';
// Utilisateur relu en base (pseudo et rôle à jour) ; null si déconnecté ou compte supprimé.
$navUser = current_user();
$navName = $navUser['username'] ?? '';
$navInitial = $navName !== '' ? mb_strtoupper(mb_substr($navName, 0, 1)) : '?';

// Navigation = les pages de contenu. Le logo ramène à l'accueil : pas de lien « Accueil ».
// Le compte (identité, back-office, déconnexion) a son propre menu, à droite.
$navLinks = ['/classements' => 'Classement'];
if ($navUser) {
    $navLinks['/dashboard'] = 'Dashboard';
}
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
    <?php if ($navUser): ?>
        <script src="/assets/account-menu.js" defer></script>
    <?php endif ?>
</head>

<body>
    <a class="skip-link" href="#contenu">Aller au contenu</a>
    <header class="site-header">
        <div class="site-header__inner">
            <a class="logo" href="/" aria-label="FoodBattle, accueil">
                <img class="logo__img" src="/assets/images/logo_fb_v1.svg" alt="" width="40" height="40">
                FoodBattle
            </a>

            <!-- 1. Navigation : où aller -->
            <nav class="site-nav" aria-label="Navigation principale">
                <?php foreach ($navLinks as $href => $label): ?>
                    <a href="<?= h($href) ?>" <?= $navActive($href) ?>><?= h($label) ?></a>
                <?php endforeach ?>
            </nav>

            <!-- 2. Action principale + 3. compte -->
            <div class="site-actions">
                <?php if ($navUser): ?>
                    <a class="btn btn--dark btn--sm site-actions__play" href="/play/tournament">Jouer</a>

                    <!-- Menu du compte : <details> fonctionne sans JavaScript ; account-menu.js ajoute
                         la fermeture au clic extérieur et à la touche Échap. -->
                    <details class="account" data-account-menu>
                        <summary class="account__chip" aria-label="Menu du compte de <?= h($navName) ?>">
                            <span class="account__avatar" aria-hidden="true"><?= h($navInitial) ?></span>
                            <span class="account__name"><?= h($navName) ?></span>
                            <svg class="account__caret" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"
                                stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>

                        <div class="account__menu">
                            <p class="account__who">Connecté en tant que <strong><?= h($navName) ?></strong></p>

                            <?php if (is_admin()): ?>
                                <a class="account__item" href="/admin" <?= $navActive('/admin') ?>>
                                    Back-office <span class="account__tag">Admin</span>
                                </a>
                            <?php endif ?>

                            <form class="account__logout" method="post" action="/logout">
                                <input type="hidden" name="token" value="<?= h(csrf()) ?>">
                                <button class="account__item account__item--out" type="submit">Se déconnecter</button>
                            </form>
                        </div>
                    </details>
                <?php else: ?>
                    <a class="site-actions__login" href="/login" <?= $navActive('/login') ?>>Connexion</a>
                    <a class="btn btn--dark btn--sm site-actions__play" href="/play/tournament">Jouer</a>
                <?php endif ?>
            </div>
        </div>
    </header>
    <main id="contenu" class="site-main">