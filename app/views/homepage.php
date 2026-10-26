<?php
$title = 'Accueil';
require_once __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../models/Battle.php';
$teaserCat = array_rand(duel_categories() ?: categories());
$teaser = (new Battle($db))->randomDuel($teaserCat, null);

$categories = categories();

// --- Paramètres de recherche (validés) ------------------------------------
$q   = trim($_GET['q'] ?? '');
$cat = $_GET['categorie'] ?? '';
if (!array_key_exists($cat, $categories)) {
    $cat = '';
}

$stats = $db->query(
    'SELECT
        (SELECT COUNT(*) FROM dishes) AS dishes,
        (SELECT COUNT(*) FROM establishments) AS establishments,
        (SELECT COUNT(*) FROM categories) AS categories'
)->fetch();

$sql = "
    SELECT
        d.*,
        e.name AS restaurant,
        e.city,
        cat_data.primary_category,
        cat_data.categories_json
    FROM dishes d
    LEFT JOIN establishments e ON e.id = d.establishment_id
    LEFT JOIN LATERAL (
        SELECT
            MIN(c.slug) AS primary_category,
            json_agg(c.slug ORDER BY c.slug)::text AS categories_json
        FROM dish_categories dc
        JOIN categories c ON c.id = dc.category_id
        WHERE dc.dish_id = d.id
    ) cat_data ON TRUE
    WHERE TRUE
";

$params = [];
if ($q !== '') {
    // Recherche sur le plat ET l'établissement, comme l'annonce le placeholder.
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $sql .= ' AND (d.name ILIKE ? OR e.name ILIKE ?)';
    array_push($params, $like, $like);
}
if ($cat !== '') {
    $sql .= "
        AND EXISTS (
            SELECT 1
            FROM dish_categories dc
            JOIN categories c ON c.id = dc.category_id
            WHERE dc.dish_id = d.id
              AND c.slug = ?
        )
    ";
    $params[] = $cat;
}
$sql .= ' ORDER BY d.id';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$dishes = $stmt->fetchAll();

/** Lien de filtre qui conserve la recherche en cours. */
function filter_url(string $category, string $q): string
{
    $query = array_filter(['q' => $q, 'categorie' => $category], 'strlen');
    return '/' . ($query ? '?' . http_build_query($query) : '') . '#carte';
}
?>

<section class="hero">
    <div class="hero__text">
        <h1 class="hero__title">Deux plats.<br>Un seul gagnant.</h1>
        <p class="hero__lead">Explore les spots du quartier, note les plats et vote pour ton favori.</p>
        <div class="hero__actions">
            <a class="btn btn--primary" href="/duel">Lancer un duel</a>
            <a class="btn btn--ghost" href="#carte">Découvrir la carte</a>
        </div>
    </div>

    
    <?php if ($teaser): ?>
    <a class="hero__duel" href="/duel?categorie=<?= h($teaserCat) ?>" aria-label="Lancer un duel <?= h(category_label($teaserCat)) ?>">
        <?php foreach ($teaser as $i => $d): ?>
            <span class="hero__card hero__card--<?= $i === 0 ? 'a' : 'b' ?>">
                <img src="<?= h($d['picture'] ?: image_for($teaserCat)) ?>" alt="" width="400" height="300" loading="lazy" onerror="this.onerror=null;this.src='/assets/images/placeholder.svg'">
                <span><?= h($d['name']) ?></span>
            </span>
            <?php if ($i === 0): ?><span class="vs" aria-hidden="true">VS</span><?php endif ?>
        <?php endforeach ?>
    </a>
<?php endif ?>

</section>

<section class="band" aria-label="Chiffres clés">
    <div class="band__inner">
        <p><strong><?= (int) $stats['dishes'] ?></strong><span>plats<br>en compétition</span></p>
        <p><strong><?= (int) $stats['establishments'] ?></strong><span>établissements<br>du quartier</span></p>
        <p><strong><?= (int) $stats['categories'] ?></strong><span>catégories<br>de plats</span></p>

    </div>
</section>

<section class="section" aria-labelledby="how-title">
    <h2 id="how-title" class="section__title">Comment ça marche</h2>
    <ol class="steps">
        <li class="box box--lilac"><span class="steps__num">01</span>
            <h3>Choisis un plat</h3>
            <p>Parcours la carte et filtre par catégorie ou par établissement.</p>
        </li>
        <li class="box box--pink"><span class="steps__num">02</span>
            <h3>Vote et donne ton avis</h3>
            <p>Un vote pour ton favori, une note sur 5 pour ton appréciation.</p>
        </li>
        <li class="box box--sky"><span class="steps__num">03</span>
            <h3>Le classement bouge</h3>
            <p>Chaque vote compte immédiatement dans le top.</p>
        </li>
    </ol>
</section>

<?php /*temporairement false car battles non alimentées pour l'instant*/ if (false): ?>
    <section class="section" aria-labelledby="podium-title">
        <div class="section__head">
            <h2 id="podium-title" class="section__title">Le podium</h2>
            <a class="link-strong" href="/classement.php">Classement complet →</a>
        </div>
        <ol class="podium">
            <?php foreach ($podium as $i => $d): ?>
                <li class="podium__item podium__item--<?= $i + 1 ?>">
                    <a class="podium__card" href="/plat.php?id=<?= (int) $d['id'] ?>">
                        <img src="<?= h(image_for($d['category'])) ?>" alt="" width="64" height="64" loading="lazy" onerror="this.onerror=null;this.src='/assets/images/placeholder.svg'">
                        <span><strong><?= h($d['name']) ?></strong><small><?= (int) $d['votes'] ?> votes · <?= h($d['restaurant']) ?></small></span>
                    </a>
                    <span class="podium__step" aria-label="Rang <?= $i + 1 ?>"><?= $i + 1 ?></span>
                </li>
            <?php endforeach ?>
        </ol>
    </section>
<?php endif ?>

<section id="carte" class="section" aria-labelledby="carte-title">
    <h2 id="carte-title" class="section__title">La carte</h2>

    <form class="search" method="get" action="/#carte" role="search">
        <label for="q">Rechercher un plat ou un établissement</label>
        <div class="search__row">
            <input id="q" type="search" name="q" value="<?= h($q) ?>" placeholder="Ex. Taco Tempo, Margherita…">
            <?php if ($cat !== ''): ?><input type="hidden" name="categorie" value="<?= h($cat) ?>"><?php endif ?>
            <button class="btn btn--dark" type="submit">Rechercher</button>
        </div>
    </form>

    <nav class="chips" aria-label="Filtrer par catégorie">
        <a class="chip" href="<?= h(filter_url('', $q)) ?>" <?= $cat === '' ? 'aria-current="true"' : '' ?>>Tous</a>
        <?php foreach (array_filter($categories, static fn(array $c): bool => $c['dishes'] > 0) as $key => $c): ?>
            <a class="chip chip--<?= $c['tone'] ?>" href="<?= h(filter_url($key, $q)) ?>" <?= $cat === $key ? 'aria-current="true"' : '' ?>><?= h($c['label']) ?></a>
        <?php endforeach ?>
    </nav>

    <p class="results" role="status">
        <?= count($dishes) ?> plat<?= count($dishes) > 1 ? 's' : '' ?> trouvé<?= count($dishes) > 1 ? 's' : '' ?>
        <?php if ($q !== '' || $cat !== ''): ?> · <a href="/#carte">Réinitialiser</a><?php endif ?>
    </p>

<?php if ($dishes): ?>
        <div class="dish-grid">
            <?php foreach ($dishes as $d): ?>
                <?php
                $primaryCategory = $d['primary_category'];
                $tone = $categories[$primaryCategory]['tone'] ?? 'lilac';
                $image = $d['picture'] ?: image_for($primaryCategory ?? '');

                $categoryKeys = $d['categories_json']
                    ? json_decode($d['categories_json'], true)
                    : [];

                $categoryNames = array_map(
                    static fn(string $key): string => category_label($key),
                    $categoryKeys ?: []
                );

                $categoryText = $categoryNames
                    ? implode(' · ', $categoryNames)
                    : 'Sans catégorie';
                ?>

                <article class="dish-card">
                    <img
                        class="dish-card__img tone-<?= h($tone) ?>"
                        src="<?= h($image) ?>"
                        alt=""
                        width="400"
                        height="300"
                        loading="lazy"
                        onerror="this.onerror=null;this.src='/assets/images/placeholder.svg'"
                    >

                    <div class="dish-card__body">
                        <span class="tag tone-<?= h($tone) ?>"><?= h($categoryText) ?></span>

                        <h3>
                            <a href="/plat.php?id=<?= (int) $d['id'] ?>">
                                <?= h($d['name']) ?>
                            </a>
                        </h3>

                        <p class="dish-card__place">
                            <?= h($d['restaurant'] ?: 'Plat maison') ?>
                            <?php if (!empty($d['city'])): ?>
                                · <?= h($d['city']) ?>
                            <?php endif ?>
                        </p>

                        <?php if (!empty($d['description'])): ?>
                            <p class="dish-card__desc"><?= h($d['description']) ?></p>
                        <?php endif ?>

                        <p class="dish-card__meta">
                            <strong>
                                <?= $d['price'] !== null ? euro($d['price']) : 'Prix non renseigné' ?>
                            </strong>
                        </p>

                        <a class="btn btn--ghost btn--block" href="/plat.php?id=<?= (int) $d['id'] ?>">
                            Découvrir
                        </a>
                    </div>
                </article>
            <?php endforeach ?>
        </div>
    <?php else: ?>
        <div class="box empty">
            <p><strong>Aucun plat ne correspond.</strong> Essaie un autre terme ou une autre catégorie.</p>
            <a class="btn btn--primary" href="/#carte">Voir tous les plats</a>
        </div>
    <?php endif ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>