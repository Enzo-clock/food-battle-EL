<?php
// filepath: app/views/editcategory.php
// Variables fournies par CategoryController::edit() : $category, $old, $errors, $colors, $categories, $return

// Doublons à signaler pendant la saisie (category.js), sans la catégorie elle-même.
$existingForJs = [];
foreach ($categories as $slug => $c) {
    if ($c['id'] !== (int) $category['id']) {
        $existingForJs[] = ['slug' => $slug, 'label' => $c['label']];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="create-page">
    <header class="create-page__head">
        <span class="pill">MODIFIER</span>
        <h1 class="create-page__title">Modifier « <?= h($category['name']) ?> »</h1>
        <p class="create-page__lead">
            <?php if ($category['status'] === 'pending'): ?>
                Proposition en attente de validation : tu peux encore la corriger.
            <?php elseif ($category['status'] === 'rejected'): ?>
                Proposition refusée. Corrige-la, elle repartira en validation.
            <?php else: ?>
                <?= plural((int) $category['dishes'], 'plat') ?> dans cette catégorie.
            <?php endif ?>
        </p>
    </header>

    <?php if ($category['status'] === 'rejected' && $category['rejection_reason']): ?>
        <p class="dash-flash dash-flash--error"><strong>Motif du refus :</strong> <?= h($category['rejection_reason']) ?></p>
    <?php endif ?>

    <?php if ($errors): ?>
        <div class="auth__errors" role="alert" tabindex="-1" data-autofocus>
            <p><strong>Les modifications n’ont pas pu être enregistrées :</strong></p>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= h($error) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

    <form class="create-form box" method="post" action="/categorie/<?= (int) $category['id'] ?>/modifier" enctype="multipart/form-data"
        data-lock-submit data-category-form data-existing="<?= h(json_encode($existingForJs, JSON_UNESCAPED_UNICODE)) ?>">
        <input type="hidden" name="token" value="<?= h(csrf()) ?>">
        <input type="hidden" name="retour" value="<?= h($return) ?>">

        <p class="create-form__legend"><span aria-hidden="true">*</span> Champ obligatoire</p>

        <div class="create-form__grid">
            <div class="create-form__fields">
                <div class="field">
                    <label for="name">Nom <span class="field__req" aria-hidden="true">*</span></label>
                    <input id="name" name="name" minlength="2" maxlength="30" required autocomplete="off"
                        aria-describedby="name-hint name-suggest" value="<?= h($old['name']) ?>" data-counter data-category-name>
                    <p class="field__hint" id="name-hint">
                        <?php if ($category['status'] === 'approved'): ?>
                            Son adresse ne change pas (<code>?categorie=<?= h($category['slug']) ?></code>) : les liens déjà partagés restent valables.
                        <?php else: ?>
                            Un ou deux mots, au pluriel : un <strong>type de plat</strong>, pas un régime ni un critère.
                        <?php endif ?>
                    </p>
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
                    <legend>Photo</legend>
                    <div class="dropzone" data-dropzone>
                        <input class="dropzone__input" id="picture" name="picture" type="file"
                            accept="image/jpeg,image/png,image/webp" aria-label="Remplacer la photo de la catégorie"
                            aria-describedby="picture-hint" data-preview-input>
                        <img class="dropzone__preview" src="<?= h($category['picture']) ?>" alt="" data-preview
                            onerror="this.onerror=null;this.src='/assets/images/placeholder.svg'">
                        <span class="dropzone__empty" hidden data-preview-empty></span>
                    </div>
                    <p class="field__hint" id="picture-hint">
                        Choisis une image pour remplacer l’actuelle. JPG, PNG ou WebP · 5 Mo maximum.
                    </p>
                </fieldset>

                <div class="create-form__group category-preview" aria-hidden="true">
                    <p class="category-preview__title">Aperçu de la pastille</p>
                    <div class="category-preview__stage">
                        <span class="chip category-preview__chip" data-category-preview><?= h($old['name']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="create-form__actions">
            <a class="btn btn--ghost" href="<?= h($return) ?>">Annuler</a>
            <button class="btn btn--primary" type="submit" data-submit-label="Enregistrement…"><?= $category['status'] === 'rejected' ? 'Corriger et renvoyer' : 'Enregistrer' ?></button>
        </div>
    </form>

    <?php
    $plan = deletion_plan('categorie', $category);
    $deleteUrl = '/categorie/' . (int) $category['id'] . '/supprimer?retour=' . rawurlencode($return);
    $what = 'cette catégorie';
    require __DIR__ . '/partials/danger-zone.php';
    ?>
</section>

<script src="/assets/form.js" defer></script>
<script src="/assets/category.js" defer></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
