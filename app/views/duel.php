<?php
// filepath: foodbattle-starter/app/views/duel.php
// Variables fournies par DuelController::show() :
// $categories, $category, $duel, $result, $error, $history, $sessionVotes, $isLogged
require_once __DIR__ . '/../includes/header.php';

$skipUrl = '/duel?' . http_build_query(['categorie' => $category]);
?>

<section class="duel-head">
    <div>
        <span class="pill">DUEL · <?= h(strtoupper(category_label($category))) ?></span>
        <h1 class="duel-head__title">Lequel tu préfères ?</h1>
        <p class="duel-head__lead">Deux plats du quartier, un seul vote. Le résultat s’affiche juste après.</p>
    </div>
    <nav class="chips" aria-label="Choisir la catégorie du duel">
        <?php foreach ($categories as $key => $c): ?>
            <a class="chip chip--<?= h($c['tone']) ?>" href="/duel?<?= h(http_build_query(['categorie' => $key])) ?>" <?= $category === $key ? 'aria-current="true"' : '' ?>><?= h($c['label']) ?></a>
        <?php endforeach ?>
    </nav>
</section>

<?php if ($error): ?>
    <p role="alert"><?= h($error) ?></p>
<?php endif ?>

<?php if (!$isLogged): ?>
    <p class="duel-notice box">
        <strong>Tu peux regarder les duels, mais il faut être connecté pour voter</strong> (un vote par personne et par duel).
        <a class="btn btn--dark btn--sm" href="/login">Se connecter</a>
    </p>
<?php endif ?>

<div class="duel-layout">
    <div class="duel-main">
        <?php if (!$duel): ?>
            <div class="box empty">
                <p><strong>Tu as départagé tous les duels <?= h(category_label($category)) ?> !</strong>
                    Essaie une autre catégorie.</p>
            </div>
        <?php else: ?>
            <?php require __DIR__ . '/partials/battle.php'; ?>

            <?php if ($result): ?>
                <div class="duel-result box" role="status">
                    <p class="duel-result__verdict">
                        <?php if ($result['already']): ?>
                            Tu avais déjà voté sur ce duel : ton premier choix est conservé.
                        <?php else: ?>
                            Vote enregistré pour <strong><?= h($duel[0]['id'] == $result['choice'] ? $duel[0]['name'] : $duel[1]['name']) ?></strong>. <?= h($result['verdict']) ?>
                        <?php endif ?>
                    </p>
                    <?php // Largeur bornée à 15–85 % pour que les deux libellés restent lisibles (même à 100/0). ?>
                    <div class="duel-bar" style="--pct-a: <?= max(15, min(85, (int) $result['pct'][$duel[0]['id']])) ?>%">
                        <span class="duel-bar__a">A · <?= (int) $result['pct'][$duel[0]['id']] ?> %</span>
                        <span class="duel-bar__b"><?= (int) $result['pct'][$duel[1]['id']] ?> % · B</span>
                    </div>
                    <div class="duel-result__foot">
                        <span><?= (int) $result['total'] ?> vote<?= $result['total'] > 1 ? 's' : '' ?> sur ce duel</span>
                        <a class="btn btn--primary" href="<?= h($skipUrl) ?>" data-duel-next autofocus>Duel suivant <kbd>Entrée</kbd></a>
                    </div>
                </div>
            <?php else: ?>
                <p class="duel-skip">
                    <a class="link-strong" href="<?= h($skipUrl) ?>" data-duel-skip>Je ne sais pas, passer ce duel</a>
                    <kbd>Espace</kbd>
                </p>
            <?php endif ?>
        <?php endif ?>
    </div>

    <aside class="duel-side" aria-label="Ta session">
        <div class="box">
            <h2 class="duel-side__title">Ta session</h2>
            <p class="duel-side__count"><strong><?= (int) $sessionVotes ?></strong> vote<?= $sessionVotes > 1 ? 's' : '' ?></p>
            <?php if ($history): ?>
                <ol class="duel-history">
                    <?php foreach ($history as $item): ?>
                        <li><strong><?= h($item['pick']) ?></strong>
                            <small>contre <?= h($item['vs']) ?> · <?= (int) $item['pct'] ?> % d’accord</small>
                        </li>
                    <?php endforeach ?>
                </ol>
            <?php else: ?>
                <p class="duel-side__empty">Tes votes de la session apparaîtront ici.</p>
            <?php endif ?>
        </div>
        <div class="box duel-keys">
            <h2 class="duel-side__title">Raccourcis</h2>
            <p><kbd>←</kbd> plat A · <kbd>→</kbd> plat B</p>
            <p><kbd>Espace</kbd> passer · <kbd>Entrée</kbd> suivant</p>
        </div>
    </aside>
</div>

<script src="/assets/duel.js" defer></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
