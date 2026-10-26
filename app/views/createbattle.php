<?php
// filepath: app/views/createbattle.php

$categories = $categories ?? [];
$q = $q ?? '';
$category = $category ?? '';
$selectedA = (int) ($selectedA ?? 0);
$selectedB = (int) ($selectedB ?? 0);
$errors = $errors ?? [];
$dishes = $dishes ?? [];

$battleFilterUrl = static function (string $categoryKey, string $search): string {
    $params = [];

    if ($search !== '') {
        $params['q'] = $search;
    }

    if ($categoryKey !== '') {
        $params['categorie'] = $categoryKey;
    }

    return '/duel/creer' . ($params ? '?' . http_build_query($params) : '');
};

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section" id="carte" aria-labelledby="carte-title">
    <h1 id="carte-title" class="section__title">Créer un duel</h1>
    <p>Choisis deux plats de la même catégorie.</p>

    <form class="search" method="get" action="/duel/creer" role="search">
        <label for="q">Rechercher un plat ou un établissement</label>
        <div class="search__row">
            <input
                id="q"
                type="search"
                name="q"
                value="<?= h($q) ?>"
                placeholder="Ex. Taco Tempo, Margherita…"
            >

            <?php if ($category !== ''): ?>
                <input type="hidden" name="categorie" value="<?= h($category) ?>">
            <?php endif ?>

            <button class="btn btn--dark" type="submit">Rechercher</button>
        </div>
    </form>

    <nav class="chips" aria-label="Filtrer par catégorie">
        <a
            class="chip"
            href="<?= h($battleFilterUrl('', $q)) ?>"
            <?= $category === '' ? 'aria-current="true"' : '' ?>
        >Tous</a>

        <?php foreach ($categories as $key => $categoryData): ?>
            <a
                class="chip chip--<?= h($categoryData['tone'] ?? 'lilac') ?>"
                href="<?= h($battleFilterUrl((string) $key, $q)) ?>"
                <?= $category === (string) $key ? 'aria-current="true"' : '' ?>
            ><?= h($categoryData['label'] ?? category_label((string) $key)) ?></a>
        <?php endforeach ?>
    </nav>

    <p class="results" role="status">
        <?= count($dishes) ?> plat<?= count($dishes) > 1 ? 's' : '' ?> trouvé<?= count($dishes) > 1 ? 's' : '' ?>
        <?php if ($q !== '' || $category !== ''): ?>
            · <a href="/duel/creer">Réinitialiser</a>
        <?php endif ?>
    </p>

    <?php if ($errors): ?>
        <div class="auth__errors" role="alert">
            <?php foreach ($errors as $error): ?>
                <p><?= h($error) ?></p>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <?php if ($dishes): ?>
        <form method="post" action="/duel/creer" id="battle-form">
            <input type="hidden" name="token" value="<?= h(csrf()) ?>">
            <input type="hidden" name="categorie" value="<?= h($category) ?>">
            <input type="hidden" name="q" value="<?= h($q) ?>">
            <input type="hidden" name="a" id="selected-a" value="<?= $selectedA ?: '' ?>">
            <input type="hidden" name="b" id="selected-b" value="<?= $selectedB ?: '' ?>">

            <div class="dish-grid">
                <?php foreach ($dishes as $dish): ?>
                    <?php
                    $id = (int) $dish['id'];
                    $primaryCategory = $dish['primary_category'] ?? '';
                    $tone = $categories[$primaryCategory]['tone'] ?? 'lilac';
                    $image = $dish['picture'] ?: image_for($primaryCategory);

                    $categoryKeys = !empty($dish['categories_json'])
                        ? json_decode($dish['categories_json'], true)
                        : [];

                    $categoryNames = array_map(
                        static fn(string $key): string => category_label($key),
                        is_array($categoryKeys) ? $categoryKeys : []
                    );

                    $categoryText = $categoryNames
                        ? implode(' · ', $categoryNames)
                        : 'Sans catégorie';

                    $isSelected = in_array($id, [$selectedA, $selectedB], true);
                    ?>

                    <article class="dish-card pick-card" data-id="<?= $id ?>" data-name="<?= h($dish['name']) ?>"
                        data-categories="<?= h(json_encode(is_array($categoryKeys) ? $categoryKeys : [])) ?>">
                        <span class="pick-card__badge" aria-hidden="true"></span>
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
                            <span class="tag tone-<?= h($tone) ?>">
                                <?= h($categoryText) ?>
                            </span>

                            <h2><?= h($dish['name']) ?></h2>

                            <p class="dish-card__place">
                                <?= h($dish['restaurant'] ?: 'Plat maison') ?>
                                <?php if (!empty($dish['city'])): ?>
                                    · <?= h($dish['city']) ?>
                                <?php endif ?>
                            </p>

                            <?php if (!empty($dish['description'])): ?>
                                <p class="dish-card__desc"><?= h($dish['description']) ?></p>
                            <?php endif ?>

                            <p class="dish-card__meta">
                                <strong>
                                    <?= $dish['price'] !== null ? euro($dish['price']) : 'Prix non renseigné' ?>
                                </strong>
                            </p>

                            <button
                                class="btn btn--ghost btn--block js-add-dish"
                                type="button"
                                data-id="<?= $id ?>"
                                aria-pressed="<?= $isSelected ? 'true' : 'false' ?>"
                                aria-label="<?= $isSelected ? 'Retirer' : 'Ajouter' ?> <?= h($dish['name']) ?> <?= $isSelected ? 'du' : 'au' ?> duel"
                            ><?= $isSelected ? 'Retirer du duel' : 'Ajouter au duel' ?></button>
                        </div>
                    </article>
                <?php endforeach ?>
            </div>

            <div class="battle-tray box" aria-label="Ton duel">
                <p class="battle-tray__slot battle-tray__slot--a" data-slot="a">
                    <span class="battle-tray__letter" aria-hidden="true">A</span>
                    <span class="battle-tray__name" data-slot-name>À choisir</span>
                </p>
                <span class="battle-tray__vs" aria-hidden="true">VS</span>
                <p class="battle-tray__slot battle-tray__slot--b" data-slot="b">
                    <span class="battle-tray__letter" aria-hidden="true">B</span>
                    <span class="battle-tray__name" data-slot-name>À choisir</span>
                </p>
                <p id="selection-status" class="battle-tray__status" role="status">Sélectionne deux plats.</p>
                <button class="btn btn--primary battle-tray__submit" type="submit" id="create-battle" disabled>
                    Créer le duel
                </button>
            </div>
        </form>
    <?php else: ?>
        <div class="box empty">
            <p><strong>Aucun plat ne correspond.</strong> Essaie un autre terme ou une autre catégorie.</p>
            <a class="btn btn--primary" href="/duel/creer">Voir tous les plats</a>
        </div>
    <?php endif ?>
</section>

<script>
// Sélection des deux plats du duel : A (teal) puis B (orange), comme sur la page duel.
// Le serveur revérifie tout (deux plats différents qui partagent une catégorie).
(() => {
    const a = document.getElementById('selected-a');
    const b = document.getElementById('selected-b');
    const status = document.getElementById('selection-status');
    const submit = document.getElementById('create-battle');
    const cards = [...document.querySelectorAll('.pick-card')];
    const slots = {
        a: document.querySelector('[data-slot="a"]'),
        b: document.querySelector('[data-slot="b"]'),
    };

    if (!a || !b) return;

    const cardById = id => cards.find(card => card.dataset.id === id);
    const categoriesOf = card => {
        try { return JSON.parse(card.dataset.categories || '[]'); } catch (e) { return []; }
    };
    const shareCategory = (c1, c2) => categoriesOf(c1).some(cat => categoriesOf(c2).includes(cat));

    function refresh() {
        const ids = [a.value, b.value].filter(Boolean);
        const first = ids[0] ? cardById(ids[0]) : null;
        const full = ids.length === 2;

        cards.forEach(card => {
            const id = card.dataset.id;
            const button = card.querySelector('.js-add-dish');
            const letter = id === a.value ? 'a' : (id === b.value ? 'b' : '');
            const incompatible = !letter && first && !shareCategory(card, first);
            const blocked = !letter && (full || incompatible);

            card.classList.toggle('is-picked-a', letter === 'a');
            card.classList.toggle('is-picked-b', letter === 'b');
            card.classList.toggle('is-unavailable', blocked);
            card.querySelector('.pick-card__badge').textContent = letter.toUpperCase();

            button.disabled = blocked;
            button.setAttribute('aria-pressed', String(letter !== ''));
            button.classList.toggle('btn--dark', letter !== '');
            button.classList.toggle('btn--ghost', letter === '');
            button.textContent = letter ? '✕ Retirer du duel'
                : full ? '2 plats déjà choisis'
                : incompatible ? 'Pas de catégorie commune'
                : 'Ajouter au duel';
            button.setAttribute('aria-label', (letter ? 'Retirer ' : 'Ajouter ') + card.dataset.name + (letter ? ' du duel' : ' au duel'));
        });

        ['a', 'b'].forEach(key => {
            const card = cardById(key === 'a' ? a.value : b.value);
            slots[key].classList.toggle('is-filled', Boolean(card));
            slots[key].querySelector('[data-slot-name]').textContent = card ? card.dataset.name : 'À choisir';
        });

        submit.disabled = !full;
        status.textContent = full ? 'Prêt ! Lance le duel.'
            : ids.length === 1 ? 'Choisis le plat B (même catégorie).'
            : 'Sélectionne deux plats.';
    }

    document.querySelectorAll('.js-add-dish').forEach(button => {
        button.addEventListener('click', () => {
            const id = button.dataset.id;

            if (a.value === id) {          // retirer A : B devient A
                a.value = b.value;
                b.value = '';
            } else if (b.value === id) {   // retirer B
                b.value = '';
            } else if (!a.value) {
                a.value = id;
            } else if (!b.value) {
                b.value = id;
            }
            // Deux plats déjà choisis : le bouton est désactivé, rien ne se passe.

            refresh();
        });
    });

    refresh();
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>