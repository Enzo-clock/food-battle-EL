<?php
// filepath: app/views/classement.php
// Classements : une catégorie à la fois (pastilles), podium pour le top 3, puis la suite du classement.
// Même vocabulaire visuel que l'accueil (pastilles, podium) et la page duel (en-tête, barres de score).
//
// Variables fournies par RankingController : $title, $categories, $current (catégorie affichée ou null), $maxScore

$trophy = static fn(int $size = 22): string =>
    '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="2.2"'
    . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0z"/>'
    . '<path d="M17 6h3a3 3 0 0 1-3 4M7 6H4a3 3 0 0 0 3 4"/></svg>';
$score = static fn(float $value): string => number_format($value, 2, ',', ' ');
$votes = static fn(?int $percent): string => $percent === null ? '—' : $percent . ' %';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="duel-head rank-head">
    <div>
        <span class="pill">CLASSEMENTS</span>
        <h1 class="duel-head__title">Les meilleurs plats</h1>
        <p class="duel-head__lead">
            Le classement se construit avec vos tournois : plus un plat va loin dans les tableaux, plus il monte.
        </p>
    </div>
    <?php if ($current !== null): // sinon le bouton est déjà dans l'état vide, juste en dessous ?>
        <a class="btn btn--primary" href="/play/tournament"><?= $trophy(18) ?> Jouer un tournoi</a>
    <?php endif ?>
</section>

<?php if ($current === null): ?>
    <section class="box dash-empty tour-empty">
        <span class="dash-empty__icon"><?= $trophy(40) ?></span>
        <h2 class="dash-empty__title">Pas encore de classement</h2>
        <p class="dash-empty__text">
            Une catégorie apparaît ici dès qu’une partie de l’un de ses tournois est terminée.
            Joue le premier tournoi du jour pour lancer le classement !
        </p>
        <a class="btn btn--primary" href="/play/tournament">Jouer un tournoi</a>
    </section>

<?php else: ?>
    <?php
    $dishes = $current['dishes'];
    $podium = array_slice($dishes, 0, 3);
    $rest = array_slice($dishes, 3);
    $tone = h($current['color'] ?: 'white');
    ?>

    <nav class="chips rank-chips" aria-label="Choisir une catégorie">
        <?php foreach ($categories as $category): ?>
            <a class="chip chip--<?= h($category['color'] ?: 'white') ?>" href="/classements?categorie=<?= h(urlencode($category['slug'])) ?>"
                <?= $category['id'] === $current['id'] ? 'aria-current="true"' : '' ?>>
                <?= h($category['name']) ?>
            </a>
        <?php endforeach ?>
    </nav>

    <section class="rank" aria-labelledby="rank-title">
        <div class="rank__head">
            <h2 class="rank__title" id="rank-title"><?= h($current['name']) ?></h2>
            <p class="rank__meta">
                <?= plural(count($dishes), 'plat classé', 'plats classés') ?>
                · <?= plural($current['tournaments'], 'tournoi') ?>
                · <?= plural($current['runs'], 'partie terminée', 'parties terminées') ?>
            </p>
        </div>

        <!-- Podium : même composant que l'accueil (2e, 1er, 3e), avec la photo du plat -->
        <ol class="podium rank-podium">
            <?php foreach ($podium as $i => $dish): ?>
                <li class="podium__item podium__item--<?= $i + 1 ?>">
                    <div class="podium__card">
                        <img class="tone-<?= $tone ?>" src="<?= h($dish['picture'] ?: image_for($current['slug'])) ?>" alt=""
                            width="64" height="64" loading="lazy" onerror="this.onerror=null;this.src='/assets/images/placeholder.svg'">
                        <span>
                            <strong><?= h($dish['name']) ?></strong>
                            <small><?= h($dish['restaurant'] ?: 'Plat maison') ?><?= $dish['city'] ? ' · ' . h($dish['city']) : '' ?></small>
                            <small class="rank-podium__score">Score moyen <strong><?= $score($dish['average_score']) ?></strong></small>
                            <small><?= plural($dish['tournament_count'], 'tournoi') ?> · <?= plural($dish['participation_count'], 'participation') ?></small>
                            <small>Votes en duel : <strong><?= $votes($dish['vote_percent']) ?></strong></small>
                        </span>
                    </div>
                    <span class="podium__step" aria-label="Rang <?= $dish['rank'] ?>"><?= $dish['rank'] ?></span>
                </li>
            <?php endforeach ?>
        </ol>

        <?php if ($rest): ?>
            <!-- La suite : le tableau d'Enzo (mêmes colonnes), au style du site -->
            <table class="rank-table">
                <caption class="visually-hidden">Suite du classement de la catégorie <?= h($current['name']) ?></caption>
                <thead>
                    <tr>
                        <th scope="col">Rang</th>
                        <th scope="col">Plat</th>
                        <th scope="col" class="rank-table__num"><span class="rank-table__long">Score moyen</span><span class="rank-table__short">Score</span></th>
                        <th scope="col" class="rank-table__num rank-table__extra">Tournois pris en compte</th>
                        <th scope="col" class="rank-table__num rank-table__extra">Participations</th>
                        <th scope="col" class="rank-table__num"><span class="rank-table__long">% des votes</span><span class="rank-table__short">Votes</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rest as $dish): ?>
                        <tr>
                            <td><span class="rank-table__pos"><?= $dish['rank'] ?></span></td>
                            <th scope="row">
                                <span class="rank-table__dish">
                                    <img class="tone-<?= $tone ?>" src="<?= h($dish['picture'] ?: image_for($current['slug'])) ?>" alt=""
                                        width="48" height="48" loading="lazy" onerror="this.onerror=null;this.src='/assets/images/placeholder.svg'">
                                    <span>
                                        <strong><?= h($dish['name']) ?></strong>
                                        <small><?= h($dish['restaurant'] ?: 'Plat maison') ?><?= $dish['city'] ? ' · ' . h($dish['city']) : '' ?></small>
                                        <!-- sur mobile, les deux dernières colonnes sont masquées : on les résume ici -->
                                        <small class="rank-table__compact">
                                            <?= plural($dish['tournament_count'], 'tournoi') ?> · <?= plural($dish['participation_count'], 'participation') ?>
                                        </small>
                                    </span>
                                </span>
                            </th>
                            <td class="rank-table__num"><strong><?= $score($dish['average_score']) ?></strong></td>
                            <td class="rank-table__num rank-table__extra"><?= (int) $dish['tournament_count'] ?></td>
                            <td class="rank-table__num rank-table__extra"><?= (int) $dish['participation_count'] ?></td>
                            <td class="rank-table__num">
                                <?= $votes($dish['vote_percent']) ?>
                                <?php if ($dish['voted_battles'] > 0): ?>
                                    <small class="rank-table__sub">sur <?= plural($dish['voted_battles'], 'duel') ?></small>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </section>

    <!-- Explication du score : repliée par défaut, pour ceux qui veulent comprendre le calcul -->
    <details class="box rank-help">
        <summary>Comment le classement est-il calculé ?</summary>
        <div class="rank-help__body">
            <p>À chaque partie terminée, un plat marque <strong>1 point par match gagné</strong>, donc 3 au maximum :</p>
            <ul class="rank-help__scale">
                <li><span class="tag">0</span> éliminé en quart</li>
                <li><span class="tag">1</span> demi-finaliste</li>
                <li><span class="tag">2</span> finaliste</li>
                <li><span class="tag tone-lemon">3</span> champion</li>
            </ul>
            <p>
                Le score est la moyenne de ces points, tournoi par tournoi, puis entre les tournois :
                <strong>chaque tournoi compte autant</strong>, même s’il a été joué plus souvent qu’un autre.
                À score égal, les plats partagent le même rang.
            </p>
            <p>
                <strong>% des votes</strong> : dans chaque duel où le plat figure (duels libres et matchs de tournoi),
                la part des votes qu’il a reçus ;
                la colonne affiche la moyenne de ces parts. Elle donne la tendance des votes en duel,
                mais <strong>ne change pas le rang</strong>, qui ne dépend que des tournois.
            </p>
        </div>
    </details>
<?php endif ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
