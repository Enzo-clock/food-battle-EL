<?php
// filepath: app/views/pages/error.php
// Page d'erreur dans le thème du site. Variables : $code (403 ou 404), $title.
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="box dash-empty error-page">
    <p class="error-page__code" aria-hidden="true"><?= (int) $code ?></p>
    <h1 class="dash-empty__title"><?= h($title) ?></h1>
    <p class="dash-empty__text">
        <?php if ($code === 403): ?>
            Cette page est réservée. Si tu penses que c’est une erreur, contacte un administrateur.
        <?php else: ?>
            Cette page n’existe pas, ou ce contenu a été retiré.
        <?php endif ?>
    </p>
    <a class="btn btn--primary" href="/">Retour à l’accueil</a>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
