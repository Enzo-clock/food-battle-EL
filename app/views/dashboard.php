<?php
// filepath: app/views/dashboard.php
// Variables fournies par DashboardController::index() :
// $tabs, $counts, $active, $items, $total

$tab = $tabs[$active];
$today = date('Y-m-d');

/** Icône SVG d'un onglet (mêmes tracés que le dock). */
$icon = static fn(string $paths, int $size = 24): string =>
    '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="2.2"'
    . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';

/** Photo d'un plat, ou l'image par défaut de sa catégorie. */
$dishPicture = static fn(?string $picture, ?string $category): string =>
    $picture ?: image_for((string) $category);

$emptyStates = [
    'duels'       => ['Aucun duel pour l’instant', 'Choisis deux plats d’une même catégorie et lance le premier affrontement.'],
    'tournois'    => ['Aucun tournoi pour l’instant', 'Un tournoi réunit 4 de tes duels d’une même catégorie : crée d’abord tes duels, puis lance le tournoi.'],
    'plats'       => ['Tu n’as encore ajouté aucun plat', 'Ajoute ton plat préféré du quartier : il entrera dans les duels de sa catégorie.'],
    'restaurants' => ['Tu n’as encore ajouté aucun restaurant', 'Ajoute une adresse pour pouvoir y rattacher ses plats.'],
    'categories'  => ['Tu n’as encore créé aucune catégorie', 'Il manque un type de plat ? Crée sa catégorie : dès deux plats, elle a ses propres duels.'],
];

$panelSubtitle = [
    'duels'       => plural($counts['duels'], 'duel créé', 'duels créés'),
    'tournois'    => plural($counts['tournois'], 'tournoi créé', 'tournois créés'),
    'plats'       => plural($counts['plats'], 'plat ajouté', 'plats ajoutés'),
    'restaurants' => plural($counts['restaurants'], 'restaurant ajouté', 'restaurants ajoutés'),
    'categories'  => plural($counts['categories'], 'catégorie créée', 'catégories créées'),
][$active];

require_once __DIR__ . '/../includes/header.php';
?>

<section class="dash">
    <header class="dash__head">
        <span class="pill">MON ESPACE</span>
        <h1 class="dash__title">Dashboard</h1>
        <p class="dash__lead">
            <?php if ($total === 0): ?>
                Tout ce que tu crées sur FoodBattle se retrouvera ici. Commence par un plat ou un duel !
            <?php else: ?>
                Tout ce que tu as créé sur FoodBattle, au même endroit :
                <strong><?= plural($total, 'contribution') ?></strong> pour l’instant.
            <?php endif ?>
        </p>
    </header>

    <nav class="dash-tabs" aria-label="Types de contenus">
        <?php foreach ($tabs as $key => $t): ?>
            <a class="dash-tab" href="/dashboard?onglet=<?= h($key) ?>" <?= $key === $active ? 'aria-current="page"' : '' ?>>
                <?= $icon($t['icon'], 22) ?>
                <span class="dash-tab__label"><?= h($t['label']) ?></span>
                <span class="dash-tab__count"><?= $counts[$key] ?></span>
            </a>
        <?php endforeach ?>
    </nav>

    <section class="dash-panel box" aria-labelledby="dash-panel-title">
        <header class="dash-panel__head">
            <div>
                <h2 class="dash-panel__title" id="dash-panel-title"><?= h($tab['title']) ?></h2>
                <p class="dash-panel__sub"><?= h($panelSubtitle) ?></p>
            </div>
            <?php if ($items): ?>
                <a class="btn btn--primary btn--sm" href="<?= h($tab['create']['href']) ?>">
                    <span aria-hidden="true">+</span> <?= h($tab['create']['label']) ?>
                </a>
            <?php endif ?>
        </header>

        <?php if (!$items): ?>
            <div class="dash-empty">
                <span class="dash-empty__icon"><?= $icon($tab['icon'], 40) ?></span>
                <h3 class="dash-empty__title"><?= h($emptyStates[$active][0]) ?></h3>
                <p class="dash-empty__text"><?= h($emptyStates[$active][1]) ?></p>
                <a class="btn btn--primary" href="<?= h($tab['create']['href']) ?>">
                    <span aria-hidden="true">+</span> <?= h($tab['create']['label']) ?>
                </a>
            </div>

        <?php elseif ($active === 'duels'): ?>
            <ul class="dash-list">
                <?php foreach ($items as $d): ?>
                    <?php
                    $aVotes = (int) $d['a_votes'];
                    $bVotes = (int) $d['b_votes'];
                    $votes = $aVotes + $bVotes;
                    $aPct = $votes ? (int) round($aVotes * 100 / $votes) : 50;
                    $category = (string) $d['category'];
                    ?>
                    <li class="dash-duel">
                        <div class="dash-duel__match">
                            <?php foreach (['a', 'b'] as $side): ?>
                                <?php
                                $sideVotes = $side === 'a' ? $aVotes : $bVotes;
                                $leads = $votes && $sideVotes > ($votes - $sideVotes);
                                ?>
                                <div class="dash-duel__side dash-duel__side--<?= $side ?><?= $leads ? ' is-leading' : '' ?>">
                                    <img class="dash-duel__img" src="<?= h($dishPicture($d[$side . '_picture'], $category)) ?>"
                                        alt="" width="64" height="64" loading="lazy">
                                    <div class="dash-duel__dish">
                                        <strong><?= h($d[$side . '_name']) ?></strong>
                                        <small><?= h($d[$side . '_restaurant'] ?? 'Plat maison') ?></small>
                                    </div>
                                    <span class="dash-duel__pct">
                                        <?= $votes ? ($side === 'a' ? $aPct : 100 - $aPct) . ' %' : '—' ?>
                                    </span>
                                </div>
                                <?php if ($side === 'a'): ?>
                                    <span class="dash-duel__vs" aria-hidden="true">VS</span>
                                <?php endif ?>
                            <?php endforeach ?>
                        </div>

                        <?php if ($votes): ?>
                            <div class="dash-split" role="img"
                                aria-label="<?= h($d['a_name']) ?> <?= $aPct ?> %, <?= h($d['b_name']) ?> <?= 100 - $aPct ?> %">
                                <span class="dash-split__a" style="width: <?= $aPct ?>%"></span>
                                <span class="dash-split__b"></span>
                            </div>
                        <?php endif ?>

                        <div class="dash-item__foot">
                            <p class="dash-meta">
                                <?php if ($category !== ''): ?>
                                    <span class="tag tone-<?= h(category_tone($category)) ?>"><?= h(category_label($category)) ?></span>
                                <?php endif ?>
                                <span><?= $votes ? plural($votes, 'vote') : 'Pas encore de vote' ?></span>
                                <span>Créé le <?= h(date_fr($d['battle_date'])) ?></span>
                            </p>
                            <?php if ($category !== ''): ?>
                                <a class="link-strong" href="/duel?<?= h(http_build_query(['categorie' => $category, 'a' => $d['a_id'], 'b' => $d['b_id']])) ?>">
                                    Voir le duel <span aria-hidden="true">→</span>
                                </a>
                            <?php endif ?>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>

        <?php elseif ($active === 'tournois'): ?>
            <ul class="dash-list">
                <?php foreach ($items as $t): ?>
                    <?php
                    $end = date('Y-m-d', strtotime($t['start_date'] . ' +3 days')); // 4 quarts, un par jour
                    if ($t['winner'] !== null) {
                        $status = ['Terminé', 'tone-mint'];
                    } elseif ($t['start_date'] > $today) {
                        $status = ['À venir', 'tone-sky'];
                    } else {
                        $status = ['En cours', 'tone-lemon'];
                    }
                    $decided = (int) $t['decided'];
                    $category = (string) $t['category'];
                    ?>
                    <li class="dash-item dash-tournament">
                        <div class="dash-tournament__head">
                            <div>
                                <h3 class="dash-item__title"><?= h($t['name']) ?></h3>
                                <p class="dash-meta">
                                    <span class="tag <?= $status[1] ?>"><?= $status[0] ?></span>
                                    <?php if ($category !== ''): ?>
                                        <span class="tag tone-<?= h(category_tone($category)) ?>"><?= h(category_label($category)) ?></span>
                                    <?php endif ?>
                                    <span>Quarts du <?= h(date_fr($t['start_date'])) ?> au <?= h(date_fr($end)) ?></span>
                                </p>
                            </div>
                            <p class="dash-stat">
                                <strong><?= (int) $t['votes'] ?></strong>
                                <span><?= (int) $t['votes'] > 1 ? 'votes' : 'vote' ?></span>
                            </p>
                        </div>

                        <?php if ($t['quarters']): ?>
                            <ol class="dash-bracket" aria-label="Quarts de finale">
                                <?php foreach ($t['quarters'] as $q): ?>
                                    <li>
                                        <span><?= h($q['a']) ?></span>
                                        <span class="dash-bracket__vs">vs</span>
                                        <span><?= h($q['b']) ?></span>
                                    </li>
                                <?php endforeach ?>
                            </ol>
                        <?php endif ?>

                        <div class="dash-item__foot">
                            <div class="dash-progress">
                                <span class="dash-progress__label"><?= $decided ?> / 7 matchs départagés</span>
                                <span class="dash-progress__track" aria-hidden="true">
                                    <span class="dash-progress__fill" style="width: <?= round($decided * 100 / 7) ?>%"></span>
                                </span>
                            </div>
                            <?php if ($t['winner'] !== null): ?>
                                <p class="dash-winner">
                                    <?= $icon($tabs['tournois']['icon'], 18) ?>
                                    Vainqueur : <strong><?= h($t['winner']) ?></strong>
                                </p>
                            <?php endif ?>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>

        <?php elseif ($active === 'plats'): ?>
            <ul class="dash-list">
                <?php foreach ($items as $d): ?>
                    <?php
                    $firstCategory = $d['categories'][0] ?? '';
                    // « Mettre en duel » seulement dans une catégorie qui a un adversaire possible (≥ 2 plats)
                    $duelCategory = '';
                    foreach ($d['categories'] as $slug) {
                        if ((categories()[$slug]['dishes'] ?? 0) >= 2) {
                            $duelCategory = $slug;
                            break;
                        }
                    }
                    ?>
                    <li class="dash-item dash-row">
                        <img class="dash-row__thumb" src="<?= h($dishPicture($d['picture'], $firstCategory)) ?>"
                            alt="" width="88" height="88" loading="lazy">
                        <div class="dash-row__body">
                            <h3 class="dash-item__title"><?= h($d['name']) ?></h3>
                            <p class="dash-row__place">
                                <?= $d['restaurant'] !== null ? h($d['restaurant']) . ' · ' . h($d['city']) : 'Plat maison' ?>
                            </p>
                            <p class="dash-meta">
                                <?php foreach ($d['categories'] as $slug): ?>
                                    <span class="tag tone-<?= h(category_tone($slug)) ?>"><?= h(category_label($slug)) ?></span>
                                <?php endforeach ?>
                                <span><?= plural((int) $d['votes'], 'vote') ?> · <?= plural((int) $d['duels'], 'duel') ?></span>
                            </p>
                        </div>
                        <div class="dash-row__aside">
                            <p class="dash-row__price"><?= $d['price'] !== null ? euro($d['price']) : '—' ?></p>
                            <?php if ($duelCategory !== ''): ?>
                                <a class="link-strong" href="/duel/creer?<?= h(http_build_query(['categorie' => $duelCategory, 'a' => $d['id']])) ?>">
                                    Mettre en duel <span aria-hidden="true">→</span>
                                </a>
                            <?php endif ?>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>

        <?php elseif ($active === 'restaurants'): ?>
            <ul class="dash-list">
                <?php foreach ($items as $r): ?>
                    <li class="dash-item dash-row">
                        <span class="dash-row__thumb dash-row__thumb--icon tone-lemon"><?= $icon($tabs['restaurants']['icon'], 36) ?></span>
                        <div class="dash-row__body">
                            <h3 class="dash-item__title"><?= h($r['name']) ?></h3>
                            <p class="dash-row__place"><?= h($r['address']) ?> · <?= h($r['city']) ?></p>
                            <?php if ($r['description']): ?>
                                <p class="dash-row__desc"><?= h($r['description']) ?></p>
                            <?php endif ?>
                        </div>
                        <div class="dash-row__aside">
                            <p class="dash-stat">
                                <strong><?= (int) $r['dishes'] ?></strong>
                                <span><?= (int) $r['dishes'] > 1 ? 'plats' : 'plat' ?></span>
                            </p>
                            <a class="link-strong" href="/plat/creer?restaurant=<?= (int) $r['id'] ?>">
                                Ajouter un plat ici <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>

        <?php else: /* categories */ ?>
            <ul class="dash-list">
                <?php foreach ($items as $c): ?>
                    <?php
                    $dishes = (int) $c['dishes'];
                    $ready = $dishes >= 2;
                    ?>
                    <li class="dash-item dash-row">
                        <img class="dash-row__thumb" src="<?= h($c['picture']) ?>" alt="" width="88" height="88" loading="lazy">
                        <div class="dash-row__body">
                            <h3 class="dash-item__title">
                                <span class="tag tone-<?= h($c['color']) ?> dash-row__chip"><?= h($c['name']) ?></span>
                            </h3>
                            <p class="dash-meta">
                                <?php if ($ready): ?>
                                    <span class="dash-status dash-status--ok">Prête pour les duels</span>
                                <?php else: ?>
                                    <span class="dash-status">Encore <?= plural(2 - $dishes, 'plat') ?> pour les duels</span>
                                <?php endif ?>
                                <span>Créée le <?= h(date_fr($c['created_at'])) ?></span>
                            </p>
                        </div>
                        <div class="dash-row__aside">
                            <p class="dash-stat">
                                <strong><?= $dishes ?></strong>
                                <span><?= $dishes > 1 ? 'plats' : 'plat' ?></span>
                            </p>
                            <?php if ($ready): ?>
                                <a class="link-strong" href="/duel?categorie=<?= h($c['slug']) ?>">Jouer <span aria-hidden="true">→</span></a>
                            <?php else: ?>
                                <a class="link-strong" href="/plat/creer?categorie=<?= (int) $c['id'] ?>">Ajouter un plat <span aria-hidden="true">→</span></a>
                            <?php endif ?>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </section>
</section>

<script>
    // Mobile : la barre d'onglets défile ; on y amène l'onglet actif pour qu'il soit visible au chargement.
    (function () {
        var tabs = document.querySelector('.dash-tabs');
        var current = tabs && tabs.querySelector('[aria-current="page"]');
        if (current && tabs.scrollWidth > tabs.clientWidth) {
            tabs.scrollLeft = current.offsetLeft - (tabs.clientWidth - current.offsetWidth) / 2;
        }
    })();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
