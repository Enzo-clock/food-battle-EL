<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions.php';

$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$navLinks = [
    'index.php'      => ['href' => '/',               'label' => 'Accueil'],
    'classement.php' => ['href' => '/classement.php', 'label' => 'Classement'],
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
                <span class="logo__mark" aria-hidden="true"><span></span><span></span></span>
                FoodBattle
            </a>
            <nav class="site-nav" aria-label="Navigation principale">
                <?php foreach ($navLinks as $file => $link): ?>
                    <a href="<?= $link['href'] ?>" <?= $currentPage === $file ? 'aria-current="page"' : '' ?>><?= $link['label'] ?></a>
                <?php endforeach ?>
                <a class="btn btn--dark btn--sm" href="/#carte">Voir la carte</a>
            </nav>
        </div>
    </header>
    <main id="contenu" class="site-main">
