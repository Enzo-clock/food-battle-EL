<?php
// filepath: app/views/createtournament.php
// Variables fournies par TournamentCreationController::create() :
// $categories, $battles, $duelCounts, $old, $errors, $success, $quarters

$category = $old['category'];
$categoryLabel = $category !== '' ? category_label($category) : '';

// Seuls les duels de la catégorie choisie sont proposés.
$shown = array_values(array_filter(
    $battles,
    static fn(array $b): bool => in_array($category, $b['shared'], true)
));
$missing = max(0, $quarters - count($shown));

// Ordre des quarts déjà choisis (après une erreur) : position 1 à 4, dans l'ordre d'envoi.
$seedOf = array_flip($old['battle_ids']);

$trophy = static fn(int $size = 22): string =>
'<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="2.2"'
    . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0z"/>'
    . '<path d="M17 6h3a3 3 0 0 1-3 4M7 6H4a3 3 0 0 0 3 4"/></svg>';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="create-page create-page--wide">
    <header class="create-page__head">
        <span class="pill">JOUER</span>
        <h1 class="create-page__title">Créer un tournoi</h1>
        <p class="create-page__lead">
            Réunis <?= $quarters ?> de tes duels d’une même catégorie : ils deviennent les quarts de finale.
            Un match par jour, puis les demi-finales et la finale.
        </p>
    </header>

    <?php if ($success): ?>
        <div class="create-success box" role="status">
            <p class="create-success__title">Le tournoi « <?= h($success['name']) ?> » est créé !</p>
            <div class="create-success__actions">
                <a class="btn btn--primary" href="/dashboard?onglet=tournois">Voir mes tournois</a>
                <a class="btn btn--ghost" href="/tournoi/creer">Créer un autre tournoi</a>
            </div>
        </div>

    <?php elseif (!$battles): ?>
        <div class="box dash-empty tour-empty">
            <span class="dash-empty__icon"><?= $trophy(40) ?></span>
            <h2 class="dash-empty__title">Il te faut d’abord des duels</h2>
            <p class="dash-empty__text">
                Un tournoi se compose de <?= $quarters ?> de tes duels d’une même catégorie,
                avec <?= $quarters * 2 ?> plats différents. Crée tes duels, puis reviens ici.
            </p>
            <a class="btn btn--primary" href="/duel/creer"><span aria-hidden="true">+</span> Créer un duel</a>
        </div>

    <?php else: ?>

        <?php if ($errors): ?>
            <div class="auth__errors" role="alert" tabindex="-1" data-autofocus>
                <p><strong>Le tournoi n’a pas pu être créé :</strong></p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= h($error) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>

        <form class="tour-layout" method="post" action="/tournoi/creer" data-lock-submit data-tournament
            data-quarters="<?= $quarters ?>">
            <input type="hidden" name="token" value="<?= h(csrf()) ?>">
            <input type="hidden" name="category" value="<?= h($category) ?>">

            <div class="create-form box tour-steps">
                <p class="create-form__legend"><span aria-hidden="true">*</span> Champ obligatoire</p>

                <!-- Étape 1 : des liens, pas des boutons radio. Changer de catégorie recharge
                     la liste des duels ; le nom et la date viennent après, rien n'est perdu. -->
                <section class="tour-step" aria-labelledby="step-category">
                    <h2 class="tour-step__title" id="step-category">
                        <span class="tour-step__num" aria-hidden="true">1</span> Catégorie
                    </h2>
                    <nav class="chips tour-cats" aria-label="Catégorie du tournoi">
                        <?php foreach ($duelCounts as $slug => $count): ?>
                            <a class="chip chip--<?= h(category_tone($slug)) ?>" href="/tournoi/creer?categorie=<?= h($slug) ?>"
                                <?= $slug === $category ? 'aria-current="true"' : '' ?>>
                                <?= h(category_label($slug)) ?>
                                <span class="tour-cats__count"><?= plural($count, 'duel') ?></span>
                            </a>
                        <?php endforeach ?>
                    </nav>
                    <p class="field__hint">Seules les catégories où tu as créé des duels sont proposées.</p>
                </section>

                <section class="tour-step" aria-labelledby="step-quarters">
                    <div class="tour-step__head">
                        <h2 class="tour-step__title" id="step-quarters">
                            <span class="tour-step__num" aria-hidden="true">2</span> Les quarts de finale
                            <span class="field__req" aria-hidden="true">*</span>
                        </h2>
                        <p class="tour-step__status" role="status" data-tour-status>
                            <?= count($old['battle_ids']) ?> / <?= $quarters ?> duels choisis
                        </p>
                    </div>
                    <p class="field__hint">
                        Choisis <?= $quarters ?> duels « <?= h($categoryLabel) ?> » sans plat en commun.
                        L’ordre de tes clics donne l’ordre des quarts.
                    </p>

                    <?php if ($missing > 0): ?>
                        <div class="tour-notice">
                            <p>
                                Tu as <?= plural(count($shown), 'duel') ?> en « <?= h($categoryLabel) ?> » :
                                il t’en manque <strong><?= $missing ?></strong> pour lancer un tournoi.
                            </p>
                            <a class="btn btn--dark btn--sm" href="/duel/creer?categorie=<?= h($category) ?>">
                                <span aria-hidden="true">+</span> Créer un duel
                            </a>
                        </div>
                    <?php endif ?>

                    <ul class="tour-duels">
                        <?php foreach ($shown as $b): ?>
                            <?php
                            $id = (int) $b['id'];
                            $isChecked = isset($seedOf[$id]);
                            ?>
                            <li>
                                <label class="tour-duel" data-tour-duel data-dishes="<?= (int) $b['a_id'] ?>,<?= (int) $b['b_id'] ?>"
                                    data-label="<?= h($b['a_name'] . ' vs ' . $b['b_name']) ?>">
                                    <input class="tour-duel__input" type="checkbox" name="battle_ids[]" value="<?= $id ?>"
                                        <?= $isChecked ? 'checked data-seed="' . ($seedOf[$id] + 1) . '"' : '' ?>>
                                    <span class="tour-duel__seed" aria-hidden="true" data-tour-seed></span>

                                    <span class="tour-duel__match">
                                        <?php foreach (['a', 'b'] as $side): ?>
                                            <span class="tour-duel__dish tour-duel__dish--<?= $side ?>">
                                                <img src="<?= h($b[$side . '_picture'] ?: image_for($category)) ?>" alt="" width="56" height="56"
                                                    loading="lazy" onerror="this.onerror=null;this.src='/assets/images/placeholder.svg'">
                                                <span class="tour-duel__text">
                                                    <strong><?= h($b[$side . '_name']) ?></strong>
                                                    <small><?= h($b[$side . '_restaurant'] ?? 'Plat maison') ?></small>
                                                </span>
                                            </span>
                                            <?php if ($side === 'a'): ?>
                                                <span class="tour-duel__vs" aria-hidden="true">VS</span>
                                            <?php endif ?>
                                        <?php endforeach ?>
                                    </span>

                                    <span class="tour-duel__foot">
                                        <span><?= plural((int) $b['votes'], 'vote') ?></span>
                                        <span class="tour-duel__conflict">Un de ces plats est déjà dans le tableau</span>
                                    </span>
                                </label>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </section>

                <section class="tour-step" aria-labelledby="step-info">
                    <h2 class="tour-step__title" id="step-info">
                        <span class="tour-step__num" aria-hidden="true">3</span> Nom du tournoi
                    </h2>
                    <div class="tour-info">
                        <div class="field">
                            <label for="name">Choisis un nom pour ton tournoi. <span class="field__req" aria-hidden="true">*</span></label>
                            <input id="name" name="name" maxlength="150" required autocomplete="off"
                                placeholder="Ex. Coupe des burgers d’automne" value="<?= h($old['name']) ?>" data-counter>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="box tour-preview" aria-labelledby="preview-title">
                <h2 class="tour-preview__title" id="preview-title"><?= $trophy() ?> Ton tableau</h2>

                <p class="tour-preview__round">Quarts de finale</p>
                <ol class="tour-bracket">
                    <?php for ($i = 1; $i <= $quarters; $i++): ?>
                        <li class="tour-slot" data-tour-slot>
                            <span class="tour-slot__seed">Q<?= $i ?></span>
                            <span class="tour-slot__name" data-tour-slot-name>À choisir</span>
                        </li>
                    <?php endfor ?>
                </ol>

                <p class="tour-preview__round">Demi-finales</p>
                <ol class="tour-bracket">
                    <li class="tour-slot tour-slot--next"><span class="tour-slot__seed">D1</span><span class="tour-slot__name">Vainqueur Q1 – vainqueur Q2</span></li>
                    <li class="tour-slot tour-slot--next"><span class="tour-slot__seed">D2</span><span class="tour-slot__name">Vainqueur Q3 – vainqueur Q4</span></li>
                </ol>

                <p class="tour-preview__round">Finale</p>
                <p class="tour-slot tour-slot--final"><span class="tour-slot__seed"><?= $trophy(18) ?></span><span class="tour-slot__name">Vainqueur D1 – vainqueur D2</span></p>

                <div class="tour-preview__actions">
                    <button class="btn btn--primary btn--block" type="submit" data-submit-label="Création…" data-tour-submit>
                        Créer le tournoi
                    </button>
                    <a class="btn btn--ghost btn--block" href="/dashboard?onglet=tournois">Annuler</a>
                </div>
            </aside>
        </form>
    <?php endif ?>
</section>

<script src="/assets/form.js" defer></script>
<script src="/assets/tournament.js" defer></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>