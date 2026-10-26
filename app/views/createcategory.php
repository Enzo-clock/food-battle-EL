<?php
// filepath: app/views/createcategory.php
// Variables fournies par CategoryController::create() :
// $errors, $old, $colors, $categories, $created, $return
$errors ??= [];

// Un navigateur ne conserve jamais un fichier choisi après un envoi en erreur :
// on prévient l'utilisateur qu'il doit resélectionner sa photo.
$pictureWasSent = $errors && (($_FILES['picture']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);

// Données des catégories existantes pour les suggestions en direct (category.js)
$existingForJs = array_map(
    static fn(string $slug, array $c): array => ['slug' => $slug, 'label' => $c['label']],
    array_keys($categories),
    $categories
);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="create-page">
    <header class="create-page__head">
        <span class="pill">CONTRIBUER</span>
        <h1 class="create-page__title">Ajouter une catégorie</h1>
        <p class="create-page__lead">Il manque un type de plat ? Crée sa catégorie : dès qu’elle aura deux plats, elle aura ses propres duels.</p>
    </header>

    <?php if ($created): ?>
        <div class="create-success box" role="status">
            <p class="create-success__title">La catégorie « <?= h($created['label']) ?> » a bien été créée.</p>
            <p>Ajoute-lui au moins deux plats pour lancer ses premiers duels.</p>
            <div class="create-success__actions">
                <a class="btn btn--primary" href="/plat/creer?categorie=<?= (int) $created['id'] ?>">Ajouter un plat dans « <?= h($created['label']) ?> »</a>
                <a class="btn btn--ghost" href="/categorie/creer">Créer une autre catégorie</a>
            </div>
        </div>
    <?php endif ?>

    <section class="box category-list" aria-labelledby="existing-title">
        <h2 id="existing-title" class="category-list__title">Les catégories existantes</h2>
        <p class="field__hint">Vérifie que la tienne n’existe pas déjà : il suffit alors d’y ajouter ton plat.</p>
        <ul class="category-list__items">
            <?php foreach ($categories as $slug => $c): ?>
                <li class="chip chip--<?= h($c['tone']) ?> category-list__chip">
                    <?= h($c['label']) ?>
                    <span class="category-list__count">
                        <?= $c['dishes'] ?> plat<?= $c['dishes'] > 1 ? 's' : '' ?>
                        <?php if ($c['dishes'] < 2): ?>
                            · encore <?= 2 - $c['dishes'] ?> pour les duels
                        <?php endif ?>
                    </span>
                </li>
            <?php endforeach ?>
        </ul>
    </section>

    <?php if (!$created): ?>

        <?php if ($errors): ?>
            <div class="auth__errors" role="alert" tabindex="-1" data-autofocus>
                <p><strong>La catégorie n’a pas pu être créée :</strong></p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= h($error) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>

        <form class="create-form box" method="post" action="/categorie/creer" enctype="multipart/form-data" data-lock-submit
            data-category-form data-existing="<?= h(json_encode($existingForJs, JSON_UNESCAPED_UNICODE)) ?>">
            <input type="hidden" name="token" value="<?= h(csrf()) ?>">
            <?php if ($return !== ''): ?>
                <input type="hidden" name="retour" value="<?= h($return) ?>">
            <?php endif ?>

            <p class="create-form__legend"><span aria-hidden="true">*</span> Champ obligatoire</p>

            <div class="create-form__grid">
                <div class="create-form__fields">
                    <div class="field">
                        <label for="name">Nom <span class="field__req" aria-hidden="true">*</span></label>
                        <input id="name" name="name" minlength="2" maxlength="30" required autocomplete="off"
                            placeholder="Ex. Pâtes, Plats indiens" aria-describedby="name-hint name-suggest"
                            value="<?= h($old['name']) ?>" data-counter data-category-name>
                        <p class="field__hint" id="name-hint">Un ou deux mots, au pluriel.</p>
                        <p class="category-suggest" id="name-suggest" role="status" aria-live="polite" data-category-suggest></p>
                    </div>

                    <fieldset class="create-form__group">
                        <legend>Couleur <span class="field__req" aria-hidden="true">*</span></legend>
                        <div class="swatch-grid">
                            <?php foreach ($colors as $value => $label): ?>
                                <label class="swatch">
                                    <input type="radio" name="color" value="<?= h($value) ?>" required
                                        <?= $old['color'] === $value ? 'checked' : '' ?> data-category-color>
                                    <span class="swatch__dot tone-<?= h($value) ?>" aria-hidden="true"></span>
                                    <span class="swatch__label"><?= h($label) ?></span>
                                </label>
                            <?php endforeach ?>
                        </div>
                    </fieldset>
                </div>

                <div class="create-form__fields">
                    <fieldset class="create-form__group">
                        <legend>Photo <span class="field__req" aria-hidden="true">*</span></legend>

                        <div class="dropzone" data-dropzone>
                            <input class="dropzone__input" id="picture" name="picture" type="file"
                                accept="image/jpeg,image/png,image/webp" aria-label="Choisir une photo pour la catégorie"
                                aria-describedby="picture-hint" required data-preview-input>
                            <img class="dropzone__preview" alt="" hidden data-preview>
                            <span class="dropzone__empty" data-preview-empty>
                                <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="14" rx="2" />
                                    <circle cx="9" cy="10" r="2" />
                                    <path d="m21 16-5-5-8 8" />
                                </svg>
                                <strong>Choisir ou glisser une photo</strong>
                                <small>JPG, PNG ou WebP · 5 Mo maximum</small>
                            </span>
                        </div>
                        <p class="field__hint" id="picture-hint">
                            C’est l’image par défaut des plats de la catégorie : elle s’affiche
                            quand un plat n’a pas sa propre photo.
                            <?php if ($pictureWasSent): ?>
                                <strong class="field__warn">Pense à resélectionner ta photo.</strong>
                            <?php endif ?>
                        </p>
                    </fieldset>

                    <div class="create-form__group category-preview" aria-hidden="true">
                        <p class="category-preview__title">Aperçu de la pastille</p>
                        <div class="category-preview__stage">
                            <span class="chip category-preview__chip" data-category-preview>Ta catégorie</span>
                        </div>
                        <p class="field__hint">C’est ainsi qu’elle apparaîtra dans les filtres et les duels.</p>
                    </div>
                </div>
            </div>

            <div class="create-form__actions">
                <a class="btn btn--ghost" href="<?= $return === 'plat' ? '/plat/creer' : '/' ?>">Annuler</a>
                <button class="btn btn--primary" type="submit" data-submit-label="Création…">Créer la catégorie</button>
            </div>
        </form>
    <?php endif ?>
</section>

<script src="/assets/form.js" defer></script>
<script src="/assets/category.js" defer></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
