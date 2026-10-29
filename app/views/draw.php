<?php
// filepath: app/views/draw.php
// « Mon plat du jour… ? » : tirage au sort d'un plat, révélé avec une animation (public/assets/draw.js).
//
// Variables fournies par DrawController::index() :
// $title, $categories (catégories validées qui ont des plats), $category (slug choisi ou null),
// $result (['winner', 'reel', 'total'] ou null), $error (bool)
//
// Sans JavaScript, la page reste complète : le serveur a déjà fait le tirage et la carte s'affiche,
// « Retenter ma chance » recharge simplement la page (= nouveau tirage).

/** Étoile SVG à N branches (éclat, étoile, soleil), centrée sur 0,0. */
$star = static function (int $points, float $outer, float $inner): string {
    $coords = [];
    for ($i = 0; $i < $points * 2; $i++) {
        $radius = $i % 2 === 0 ? $outer : $inner;
        $angle = M_PI * $i / $points - M_PI / 2;
        $coords[] = round($radius * cos($angle), 2) . ',' . round($radius * sin($angle), 2);
    }
    return implode(' ', $coords);
};

$query = $category !== null ? '?' . http_build_query(['categorie' => $category]) : '';
$winner = $result['winner'] ?? null;

// Données du premier tirage pour draw.js. Les drapeaux JSON_HEX_* échappent < > & ' " :
// un nom de plat contenant « </script> » ne peut pas sortir de la balise.
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

require_once __DIR__ . '/../includes/header.php';
?>

<section class="duel-head draw-head">
    <div>
        <span class="pill">TIRAGE AU SORT</span>
        <h1 class="duel-head__title">Mon plat du jour… ?</h1>
        <p class="duel-head__lead">Tu ne sais pas quoi manger ? Laisse le destin choisir.</p>
    </div>
</section>

<?php if (count($categories) > 1): ?>
    <nav class="chips draw-chips" aria-label="Tirer dans une catégorie">
        <a class="chip" href="/tirage" <?= $category === null ? 'aria-current="true"' : '' ?>>Toutes</a>
        <?php foreach ($categories as $slug => $c): ?>
            <a class="chip chip--<?= h($c['tone']) ?>" href="/tirage?<?= h(http_build_query(['categorie' => $slug])) ?>"
                <?= $category === $slug ? 'aria-current="true"' : '' ?>><?= h($c['label']) ?></a>
        <?php endforeach ?>
    </nav>
<?php endif ?>

<?php if ($error): ?>
    <section class="box dash-empty draw-empty">
        <h2 class="dash-empty__title">Le destin a eu un trou de mémoire</h2>
        <p class="dash-empty__text">Le tirage n’a pas pu être fait. Réessaie dans un instant.</p>
        <a class="btn btn--primary" href="/tirage<?= h($query) ?>">Réessayer</a>
    </section>

<?php elseif ($winner === null): ?>
    <section class="box dash-empty draw-empty">
        <?php if ($category !== null): ?>
            <h2 class="dash-empty__title">Aucun plat dans cette catégorie</h2>
            <p class="dash-empty__text">Le destin ne peut rien tirer ici pour l’instant. Essaie avec tous les plats.</p>
            <a class="btn btn--primary" href="/tirage">Tirer parmi tous les plats</a>
        <?php else: ?>
            <h2 class="dash-empty__title">Aucun plat à tirer pour l’instant</h2>
            <p class="dash-empty__text">Dès qu’un plat sera validé, le destin pourra choisir pour toi.</p>
            <a class="btn btn--primary" href="/plat/creer">Proposer un plat</a>
        <?php endif ?>
    </section>

<?php else: ?>
    <section class="draw" id="draw" data-state="result" data-api="/api/tirage<?= h($query) ?>" aria-label="Résultat du tirage">
        <script>document.getElementById('draw').dataset.state = 'armed';</script>

        <div class="draw__stage">
            <!-- Décor fixe et discret, loin de la carte ; l'éclat n'apparaît qu'à la révélation -->
            <svg class="draw__deco draw__deco--star" viewBox="-50 -50 100 100" aria-hidden="true">
                <polygon points="<?= $star(5, 48, 22) ?>" />
            </svg>
            <svg class="draw__deco draw__deco--sun" viewBox="-50 -50 100 100" aria-hidden="true">
                <polygon points="<?= $star(32, 48, 30) ?>" />
            </svg>
            <span class="draw__deco draw__deco--dot draw__deco--dot-a" aria-hidden="true"></span>
            <span class="draw__deco draw__deco--dot draw__deco--dot-b" aria-hidden="true"></span>

            <svg class="draw__burst" viewBox="-100 -100 200 200" aria-hidden="true">
                <polygon points="<?= $star(28, 98, 64) ?>" />
            </svg>

            <article class="dish-card draw-card" data-card>
                <img class="dish-card__img draw-card__img" data-field="picture" src="<?= h($winner['picture']) ?>"
                    alt="" width="400" height="300" onerror="this.onerror=null;this.src='/assets/images/placeholder.svg'">
                <div class="dish-card__body draw-card__body">
                    <span class="tag tone-<?= h($winner['tone']) ?> draw-card__reveal" data-field="category" style="--i: 1"><?= h($winner['category']) ?></span>
                    <h2 class="draw-card__name" data-field="name"><?= h($winner['name']) ?></h2>
                    <p class="dish-card__place draw-card__reveal" data-field="place" style="--i: 2"><?= h($winner['place']) ?></p>
                    <p class="dish-card__desc draw-card__desc draw-card__reveal" data-field="description" style="--i: 3"><?= h($winner['description']) ?></p>
                    <p class="dish-card__meta draw-card__reveal" style="--i: 4"><strong data-field="price"><?= h($winner['price']) ?></strong></p>
                </div>
            </article>
        </div>

        <p class="draw__status" role="status" data-status>
            <?= $result['total'] > 1
                ? 'Le destin a choisi parmi ' . plural($result['total'], 'plat') . '.'
                : 'Un seul plat en lice : le destin n’avait pas le choix !' ?>
        </p>
        <p class="draw__error" role="alert" data-error hidden></p>

        <div class="draw__actions">
            <a class="btn btn--dark draw__retry" href="/tirage<?= h($query) ?>" data-retry>Retenter ma chance</a>
        </div>

        <script type="application/json" id="draw-data"><?= json_encode($result, $jsonFlags) ?></script>
    </section>
    <script src="/assets/draw.js" defer></script>
<?php endif ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
