<?php
// filepath: app/views/dashboard.php
// Vue commune au dashboard (/dashboard, mes contenus) et au back-office (/admin, tous les contenus).
// Variables fournies par DashboardController::render() :
// $context ('user' | 'admin'), $tabs, $counts, $alerts, $active, $items, $total, $flash, $q

$isAdmin = $context === 'admin';
// Un utilisateur PROPOSE plats, restaurants et catégories (validés ensuite par un admin) : les boutons le disent.
if (!is_admin()) {
    $proposeLabels = ['plats' => 'Proposer un plat', 'restaurants' => 'Proposer un restaurant', 'categories' => 'Proposer une catégorie'];
    foreach ($proposeLabels as $key => $label) {
        if (isset($tabs[$key]['create'])) {
            $tabs[$key]['create']['label'] = $label;
        }
    }
}
$tab = $tabs[$active];
$today = date('Y-m-d');
$baseUrl = $isAdmin ? '/admin' : '/dashboard';
$returnUrl = $baseUrl . '?onglet=' . $active; // après une action, on revient sur le même onglet

/** Icône SVG d'un onglet (mêmes tracés que le dock). */
$icon = static fn(string $paths, int $size = 24): string =>
    '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="2.2"'
    . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';

/** Photo d'un plat, ou l'image par défaut de sa catégorie. */
$dishPicture = static fn(?string $picture, ?string $category): string =>
    $picture ?: image_for((string) $category);

$emptyStates = $q !== '' ? array_fill_keys(array_keys($tabs), ['Aucun résultat', 'Aucun élément ne correspond à « ' . $q . ' ».']) : [
    'duels'       => ['Aucun duel pour l’instant', 'Choisis deux plats d’une même catégorie et lance le premier affrontement.'],
    'tournois'    => ['Aucun tournoi pour l’instant', 'Un tournoi réunit 4 de tes duels d’une même catégorie : crée d’abord tes duels, puis lance le tournoi.'],
    'plats'       => ['Tu n’as encore ajouté aucun plat', 'Ajoute ton plat préféré du quartier : il entrera dans les duels de sa catégorie.'],
    'restaurants' => ['Tu n’as encore ajouté aucun restaurant', 'Ajoute une adresse pour pouvoir y rattacher ses plats.'],
    'categories'  => ['Tu n’as encore créé aucune catégorie', 'Il manque un type de plat ? Crée sa catégorie : dès deux plats, elle a ses propres duels.'],
    'utilisateurs' => ['Aucun utilisateur', 'Aucun compte pour l’instant.'],
];

$panelSubtitle = $isAdmin
    ? ($q !== '' ? plural(count($items), 'résultat') . ' pour « ' . $q . ' »' : plural($counts[$active], 'élément') . ' sur le site')
    : [
        'duels'       => plural($counts['duels'], 'duel créé', 'duels créés'),
        'tournois'    => plural($counts['tournois'], 'tournoi créé', 'tournois créés'),
        'plats'       => plural($counts['plats'], 'plat ajouté', 'plats ajoutés'),
        'restaurants' => plural($counts['restaurants'], 'restaurant ajouté', 'restaurants ajoutés'),
        'categories'  => plural($counts['categories'], 'catégorie créée', 'catégories créées'),
    ][$active];

/** « par john.raven » : dans le back-office, chaque contenu indique son créateur. */
$byLine = static fn(array $row): string => $isAdmin ? '<span class="dash-by">par ' . h($row['creator']) . '</span>' : '';


require_once __DIR__ . '/../includes/header.php';
?>

<section class="dash">
    <header class="dash__head">
        <span class="pill"><?= $isAdmin ? 'ADMINISTRATION' : 'MON ESPACE' ?></span>
        <h1 class="dash__title"><?= $isAdmin ? 'Back-office' : 'Dashboard' ?></h1>
        <p class="dash__lead">
            <?php if ($isAdmin): ?>
                Tous les contenus du site : <strong><?= plural($total, 'contribution') ?></strong>.
                Corrige, archive ou supprime selon les mêmes règles que les créateurs, et gère les administrateurs.
            <?php elseif ($total === 0): ?>
                Tout ce que tu crées sur FoodBattle se retrouvera ici. Commence par un plat ou un duel !
            <?php else: ?>
                Tout ce que tu as créé sur FoodBattle, au même endroit :
                <strong><?= plural($total, 'contribution') ?></strong> pour l’instant.
            <?php endif ?>
        </p>
    </header>

    <?php if ($flash): ?>
        <p class="dash-flash dash-flash--<?= h($flash['type']) ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
            <?= h($flash['message']) ?>
        </p>
    <?php endif ?>

    <nav class="dash-tabs" aria-label="Types de contenus">
        <?php foreach ($tabs as $key => $t): ?>
            <a class="dash-tab" href="<?= h($baseUrl) ?>?onglet=<?= h($key) ?>" <?= $key === $active ? 'aria-current="page"' : '' ?>>
                <?= $icon($t['icon'], 22) ?>
                <span class="dash-tab__label"><?= h($t['label']) ?></span>
                <span class="dash-tab__count"><?= $counts[$key] ?></span>
                <?php if (!empty($alerts[$key])): ?>
                    <span class="dash-tab__alert" title="<?= (int) $alerts[$key] ?> à valider"><span aria-hidden="true"><?= (int) $alerts[$key] ?></span><span class="visually-hidden">, <?= (int) $alerts[$key] ?> à valider</span></span>
                <?php endif ?>
            </a>
        <?php endforeach ?>
    </nav>

    <section class="dash-panel box" aria-labelledby="dash-panel-title">
        <header class="dash-panel__head">
            <div>
                <h2 class="dash-panel__title" id="dash-panel-title"><?= h($isAdmin ? $tab['label'] : $tab['title']) ?></h2>
                <p class="dash-panel__sub"><?= h($panelSubtitle) ?></p>
            </div>
            <?php if ($isAdmin): ?>
                <form class="dash-search" method="get" action="/admin" role="search">
                    <input type="hidden" name="onglet" value="<?= h($active) ?>">
                    <label class="visually-hidden" for="dash-q">Rechercher dans « <?= h($tab['label']) ?> »</label>
                    <input id="dash-q" type="search" name="q" value="<?= h($q) ?>" maxlength="100"
                        placeholder="Rechercher…">
                    <button class="btn btn--dark btn--sm" type="submit">Rechercher</button>
                </form>
            <?php elseif ($items && $tab['create']): ?>
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
                <?php if ($q !== ''): ?>
                    <a class="btn btn--ghost" href="<?= h($returnUrl) ?>">Tout afficher</a>
                <?php elseif ($tab['create']): ?>
                    <a class="btn btn--primary" href="<?= h($tab['create']['href']) ?>">
                        <span aria-hidden="true">+</span> <?= h($tab['create']['label']) ?>
                    </a>
                <?php endif ?>
            </div>

        <?php elseif ($active === 'utilisateurs'): ?>
            <div class="dash-table">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Pseudo</th>
                            <th scope="col">E-mail</th>
                            <th scope="col">Inscrit le</th>
                            <th scope="col">Contributions</th>
                            <th scope="col">Rôle</th>
                            <th scope="col"><span class="visually-hidden">Action</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $u): ?>
                            <?php $isMe = (int) $u['id'] === current_user_id(); ?>
                            <tr>
                                <th scope="row"><?= h($u['username']) ?><?= $isMe ? ' <small>(toi)</small>' : '' ?></th>
                                <td><?= h($u['email']) ?></td>
                                <td><?= h(date_fr($u['created_at'])) ?></td>
                                <td><?= (int) $u['contributions'] ?></td>
                                <td><span class="tag <?= $u['role'] === 'admin' ? 'tone-lilac' : 'tone-white' ?>"><?= $u['role'] === 'admin' ? 'Admin' : 'Utilisateur' ?></span></td>
                                <td>
                                    <?php if ($isMe): ?>
                                        <span class="dash-me" title="Un autre administrateur peut changer ton rôle : le site ne reste jamais sans admin.">Ton compte</span>
                                    <?php else: ?>
                                        <form method="post" action="/admin/utilisateurs/<?= (int) $u['id'] ?>/role">
                                            <input type="hidden" name="token" value="<?= h(csrf()) ?>">
                                            <input type="hidden" name="role" value="<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>">
                                            <button class="btn btn--xs <?= $u['role'] === 'admin' ? 'btn--ghost' : 'btn--dark' ?>" type="submit">
                                                <?= $u['role'] === 'admin' ? 'Retirer admin' : 'Nommer admin' ?>
                                            </button>
                                        </form>
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
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
                                <?= $byLine($d) ?>
                            </p>
                            <div class="dash-item__end">
                                <?php if ($category !== ''): ?>
                                    <a class="link-strong" href="/duel?<?= h(http_build_query(['categorie' => $category, 'a' => $d['a_id'], 'b' => $d['b_id']])) ?>">
                                        Voir le duel <span aria-hidden="true">→</span>
                                    </a>
                                <?php endif ?>
                                <?php $type = 'duel'; $row = $d; $canEdit = false; require __DIR__ . '/partials/item-actions.php'; ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>

        <?php elseif ($active === 'tournois'): ?>
            <ul class="dash-list">
                <?php foreach ($items as $t): ?>
                    <?php
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
                                    <?= $byLine($t) ?>
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
                            <?php $type = 'tournoi'; $row = $t; $canEdit = true; require __DIR__ . '/partials/item-actions.php'; ?>
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
                    <li class="dash-item dash-row<?= $d['archived_at'] !== null ? ' is-archived' : '' ?><?= $d['status'] !== 'approved' ? ' is-' . h($d['status']) : '' ?>">
                        <img class="dash-row__thumb" src="<?= h($dishPicture($d['picture'], $firstCategory)) ?>"
                            alt="" width="88" height="88" loading="lazy">
                        <div class="dash-row__body">
                            <h3 class="dash-item__title">
                                <?= h($d['name']) ?>
                                <?php if ($d['archived_at'] !== null): ?>
                                    <span class="tag dash-archived">Archivé</span>
                                <?php endif ?>
                            </h3>
                            <p class="dash-row__place">
                                <?= $d['restaurant'] !== null ? h($d['restaurant']) . ' · ' . h($d['city']) : 'Plat maison' ?>
                            </p>
                            <p class="dash-meta">
                                <?php $row = $d; $feminine = false; require __DIR__ . '/partials/moderation-badge.php'; ?>
                                <?php foreach ($d['categories'] as $slug): ?>
                                    <span class="tag tone-<?= h(category_tone($slug)) ?>"><?= h(category_label($slug)) ?></span>
                                <?php endforeach ?>
                                <?php if ($d['status'] === 'approved'): ?>
                                    <span><?= plural((int) $d['votes'], 'vote') ?> · <?= plural((int) $d['battles'], 'duel') ?></span>
                                <?php endif ?>
                                <?= $byLine($d) ?>
                            </p>
                            <?php $row = $d; require __DIR__ . '/partials/moderation-reason.php'; ?>
                        </div>
                        <div class="dash-row__aside">
                            <p class="dash-row__price"><?= $d['price'] !== null ? euro($d['price']) : '—' ?></p>
                            <?php if ($d['status'] === 'pending' && $isAdmin): ?>
                                <?php $reviewType = 'plats'; $row = $d; require __DIR__ . '/partials/review-actions.php'; ?>
                            <?php elseif ($duelCategory !== '' && $d['archived_at'] === null && $d['status'] === 'approved'): ?>
                                <a class="link-strong" href="/duel/creer?<?= h(http_build_query(['categorie' => $duelCategory, 'a' => $d['id']])) ?>">
                                    Mettre en duel <span aria-hidden="true">→</span>
                                </a>
                            <?php endif ?>
                            <?php
                            // Validé : seul un admin le modifie. En attente ou refusé : son auteur peut le corriger.
                            $type = 'plat'; $row = $d; $canEdit = is_admin() || $d['status'] !== 'approved';
                            require __DIR__ . '/partials/item-actions.php';
                            ?>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>

        <?php elseif ($active === 'restaurants'): ?>
            <ul class="dash-list">
                <?php foreach ($items as $r): ?>
                    <li class="dash-item dash-row<?= $r['status'] !== 'approved' ? ' is-' . h($r['status']) : '' ?>">
                        <span class="dash-row__thumb dash-row__thumb--icon tone-lemon"><?= $icon($tabs['restaurants']['icon'], 36) ?></span>
                        <div class="dash-row__body">
                            <h3 class="dash-item__title"><?= h($r['name']) ?></h3>
                            <p class="dash-row__place"><?= h($r['address']) ?> · <?= h($r['city']) ?> <?= $byLine($r) ?></p>
                            <?php if ($r['status'] !== 'approved'): ?>
                                <p class="dash-meta"><?php $row = $r; $feminine = false; require __DIR__ . '/partials/moderation-badge.php'; ?></p>
                            <?php endif ?>
                            <?php if ($r['description']): ?>
                                <p class="dash-row__desc"><?= h($r['description']) ?></p>
                            <?php endif ?>
                            <?php $row = $r; require __DIR__ . '/partials/moderation-reason.php'; ?>
                        </div>
                        <div class="dash-row__aside">
                            <?php if ($r['status'] === 'pending' && $isAdmin): ?>
                                <?php $reviewType = 'restaurants'; $row = $r; require __DIR__ . '/partials/review-actions.php'; ?>
                            <?php elseif ($r['status'] === 'approved'): ?>
                                <p class="dash-stat">
                                    <strong><?= (int) $r['dishes'] ?></strong>
                                    <span><?= (int) $r['dishes'] > 1 ? 'plats' : 'plat' ?></span>
                                </p>
                                <a class="link-strong" href="/plat/creer?restaurant=<?= (int) $r['id'] ?>">
                                    Ajouter un plat ici <span aria-hidden="true">→</span>
                                </a>
                            <?php endif ?>
                            <?php
                            $type = 'restaurant'; $row = $r; $canEdit = is_admin() || $r['status'] !== 'approved';
                            require __DIR__ . '/partials/item-actions.php';
                            ?>
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
                    $status = $c['status'];
                    ?>
                    <li class="dash-item dash-row<?= $status !== 'approved' ? ' is-' . h($status) : '' ?>">
                        <img class="dash-row__thumb" src="<?= h($c['picture']) ?>" alt="" width="88" height="88" loading="lazy">
                        <div class="dash-row__body">
                            <h3 class="dash-item__title">
                                <span class="tag tone-<?= h($c['color']) ?> dash-row__chip"><?= h($c['name']) ?></span>
                            </h3>
                            <p class="dash-meta">
                                <?php if ($status !== 'approved'): ?>
                                    <?php $row = $c; $feminine = true; require __DIR__ . '/partials/moderation-badge.php'; ?>
                                <?php elseif ($ready): ?>
                                    <span class="dash-status dash-status--ok">Prête pour les duels</span>
                                <?php else: ?>
                                    <span class="dash-status">Encore <?= plural(2 - $dishes, 'plat') ?> pour les duels</span>
                                <?php endif ?>
                                <span><?= $status === 'approved' ? 'Créée' : 'Proposée' ?> le <?= h(date_fr($c['created_at'])) ?></span>
                                <?= $byLine($c) ?>
                            </p>
                            <?php $row = $c; require __DIR__ . '/partials/moderation-reason.php'; ?>
                        </div>
                        <div class="dash-row__aside">
                            <?php if ($status === 'pending' && $isAdmin): ?>
                                <?php $reviewType = 'categories'; $row = $c; require __DIR__ . '/partials/review-actions.php'; ?>
                            <?php elseif ($status === 'approved'): ?>
                                <p class="dash-stat">
                                    <strong><?= $dishes ?></strong>
                                    <span><?= $dishes > 1 ? 'plats' : 'plat' ?></span>
                                </p>
                                <?php if ($ready): ?>
                                    <a class="link-strong" href="/duel?categorie=<?= h($c['slug']) ?>">Jouer <span aria-hidden="true">→</span></a>
                                <?php else: ?>
                                    <a class="link-strong" href="/plat/creer?categorie=<?= (int) $c['id'] ?>">Ajouter un plat <span aria-hidden="true">→</span></a>
                                <?php endif ?>
                            <?php endif ?>
                            <?php
                            // Validée : seul un admin la modifie. En attente ou refusée : son créateur peut la corriger.
                            $type = 'categorie'; $row = $c; $canEdit = is_admin() || $status !== 'approved';
                            require __DIR__ . '/partials/item-actions.php';
                            ?>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </section>
</section>

<?php require __DIR__ . '/partials/confirm-dialog.php'; ?>
<script>
    // Mobile : la barre d'onglets défile. On y amène l'onglet actif, et on signale par un dégradé
    // les onglets cachés à gauche ou à droite (classes has-more-left / has-more-right).
    (function () {
        var tabs = document.querySelector('.dash-tabs');
        if (!tabs) return;
        var current = tabs.querySelector('[aria-current="page"]');

        function update() {
            var max = tabs.scrollWidth - tabs.clientWidth;
            tabs.classList.toggle('has-more-left', tabs.scrollLeft > 2);
            tabs.classList.toggle('has-more-right', tabs.scrollLeft < max - 2);
        }

        if (current && tabs.scrollWidth > tabs.clientWidth) {
            tabs.scrollLeft = current.offsetLeft - (tabs.clientWidth - current.offsetWidth) / 2;
        }
        tabs.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        update();
    })();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
