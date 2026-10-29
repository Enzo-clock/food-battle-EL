<?php
// filepath: foodbattle-starter/app/views/pages/contact.php
// Remplacer [contact@exemple.fr] par l'adresse de l'équipe.
require_once __DIR__ . '/../../includes/header.php';

$contactEmail = '[contact@foodbattle.fr]';
$subjects = [
    'Proposer un plat ou un établissement' => 'Nom du plat, établissement, ville, prix…',
    'Signaler un contenu'                   => 'Lien de la page et ce qui pose problème.',
    'Exercer mes droits RGPD'               => 'Ton nom d’utilisateur et ta demande (accès, correction, suppression).',
    'Autre question'                        => 'Pose ta question, on te répond au plus vite.',
];
?>

<article class="prose">
    <span class="pill">CONTACT</span>
    <h1 class="prose__title">Une question ?</h1>
    <p class="prose__lead">Une idée de plat, un bug, une demande sur tes données : écris-nous, on lit tout.</p>

    <div class="contact-grid">
        <?php foreach ($subjects as $subject => $hint): ?>
            <a class="contact-card box" href="mailto:<?= h($contactEmail) ?>?subject=<?= rawurlencode('[FoodBattle] ' . $subject) ?>">
                <h2><?= h($subject) ?></h2>
                <p><?= h($hint) ?></p>
                <span class="link-strong">Écrire →</span>
            </a>
        <?php endforeach ?>
    </div>

    <p class="prose__updated">Ou directement : <a href="mailto:<?= h($contactEmail) ?>"><?= h($contactEmail) ?></a></p>
</article>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
