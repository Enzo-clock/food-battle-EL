<?php
// filepath: app/views/editdish.php
// Variables fournies par DishController::edit() :
// $dish, $old, $errors, $establishments, $nameLocked, $return

$pictureWasSent = $errors && (($_FILES['picture']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);
$category = (string) $dish['category'];
$currentPicture = $dish['picture'] ?: image_for($category);
$plan = $dish['archived_at'] === null ? deletion_plan('plat', $dish) : null;
$lockIcon = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"'
    . ' stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="create-page">
    <header class="create-page__head">
        <span class="pill">MODIFIER</span>
        <h1 class="create-page__title">Modifier « <?= h($dish['name']) ?> »</h1>
        <p class="create-page__lead">
            <?= dish_in_play($dish)
                ? 'Ce plat est en jeu (' . plural((int) $dish['battles'], 'duel ou match', 'duels ou matchs') . ', ' . plural((int) $dish['votes'], 'vote') . ').'
                : 'Ce plat n’est encore dans aucun duel.' ?>
        </p>
    </header>

    <?php if ($dish['archived_at'] !== null): ?>
        <div class="box confirm-box confirm-box--archive">
            <p class="confirm-box__text">
                <strong>Ce plat est archivé</strong> depuis le <?= h(date_fr($dish['archived_at'])) ?> :
                il n’apparaît plus dans les listes ni dans les nouveaux duels.
            </p>
            <form method="post" action="/plat/<?= (int) $dish['id'] ?>/restaurer">
                <input type="hidden" name="token" value="<?= h(csrf()) ?>">
                <input type="hidden" name="retour" value="<?= h($return) ?>">
                <button class="btn btn--dark btn--sm" type="submit">Restaurer le plat</button>
            </form>
        </div>
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

    <form class="create-form box" method="post" action="/plat/<?= (int) $dish['id'] ?>/modifier" enctype="multipart/form-data" data-lock-submit>
        <input type="hidden" name="token" value="<?= h(csrf()) ?>">
        <input type="hidden" name="retour" value="<?= h($return) ?>">

        <p class="create-form__legend"><span aria-hidden="true">*</span> Champ obligatoire</p>

        <div class="create-form__grid">
            <div class="create-form__fields">
                <fieldset class="create-form__group">
                    <legend>Le plat</legend>

                    <div class="field<?= $nameLocked ? ' field--locked' : '' ?>">
                        <label for="name">Nom <span class="field__req" aria-hidden="true">*</span><?= $nameLocked ? ' ' . $lockIcon : '' ?></label>
                        <?php if ($nameLocked): ?>
                            <input id="name" value="<?= h($dish['name']) ?>" readonly aria-describedby="name-lock">
                            <p class="field__notice" id="name-lock">
                                Le nom est verrouillé : le plat a déjà joué, et le changer fausserait les votes reçus.
                            </p>
                        <?php else: ?>
                            <input id="name" name="name" maxlength="150" required value="<?= h($old['name']) ?>" data-counter>
                        <?php endif ?>
                    </div>

                    <div class="field">
                        <label for="description">Description <span class="field__opt">(facultatif)</span></label>
                        <textarea id="description" name="description" rows="4" maxlength="1000"
                            placeholder="Ingrédients, sauce, ce qui le rend unique…" data-counter><?= h($old['description']) ?></textarea>
                    </div>

                    <div class="field field--price">
                        <label for="price">Prix <span class="field__opt">(facultatif)</span></label>
                        <div class="field__affix">
                            <input id="price" name="price" inputmode="decimal" autocomplete="off"
                                pattern="\d{1,4}([.,]\d{1,2})?" placeholder="12,50" value="<?= h($old['price']) ?>">
                            <span class="field__suffix" aria-hidden="true">€</span>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="create-form__group">
                    <legend>Où le manger</legend>
                    <div class="field">
                        <label for="establishment_id">Restaurant</label>
                        <select id="establishment_id" name="establishment_id">
                            <option value="0">Aucun : plat fait maison</option>
                            <?php foreach ($establishments as $id => $e): ?>
                                <option value="<?= (int) $id ?>" <?= (int) $old['establishment_id'] === $id ? 'selected' : '' ?>>
                                    <?= h($e['name']) ?> · <?= h($e['city']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </fieldset>
            </div>

            <div class="create-form__fields">
                <fieldset class="create-form__group">
                    <legend>Catégorie <?= $lockIcon ?></legend>
                    <p class="locked-value">
                        <span class="tag tone-<?= h(category_tone($category)) ?>"><?= h(category_label($category)) ?></span>
                    </p>
                    <p class="field__notice">
                        La catégorie définit les duels du plat : elle ne change pas.
                        Pour en faire une autre version, <a href="/plat/creer?depuis=<?= (int) $dish['id'] ?>">duplique ce plat</a>
                        et choisis une nouvelle catégorie.
                    </p>
                </fieldset>

                <fieldset class="create-form__group">
                    <legend>Photo <span class="field__opt">(facultative)</span></legend>

                    <div class="dropzone" data-dropzone>
                        <input class="dropzone__input" id="picture" name="picture" type="file"
                            accept="image/jpeg,image/png,image/webp" aria-label="Remplacer la photo du plat"
                            aria-describedby="picture-hint" data-preview-input>
                        <img class="dropzone__preview" src="<?= h($currentPicture) ?>" alt="" data-preview
                            onerror="this.onerror=null;this.src='/assets/images/placeholder.svg'">
                        <span class="dropzone__empty" hidden data-preview-empty></span>
                    </div>
                    <p class="field__hint" id="picture-hint">
                        <?= $dish['picture'] ? 'Choisis une image pour remplacer la photo actuelle.' : 'Pas de photo : c’est l’image de la catégorie qui s’affiche. Choisis-en une pour la remplacer.' ?>
                        JPG, PNG ou WebP · 5 Mo maximum.
                        <?php if ($pictureWasSent): ?>
                            <strong class="field__warn">Pense à resélectionner ta photo.</strong>
                        <?php endif ?>
                    </p>
                    <?php if ($dish['picture']): ?>
                        <label class="keep-picture">
                            <input type="checkbox" name="remove_picture" value="1">
                            <span>Retirer la photo<br><small>L’image de la catégorie s’affichera à la place.</small></span>
                        </label>
                    <?php endif ?>
                </fieldset>
            </div>
        </div>

        <div class="create-form__actions">
            <a class="btn btn--ghost" href="<?= h($return) ?>">Annuler</a>
            <button class="btn btn--primary" type="submit" data-submit-label="Enregistrement…">Enregistrer</button>
        </div>
    </form>

    <?php
    $deleteUrl = '/plat/' . (int) $dish['id'] . '/supprimer?retour=' . rawurlencode($return);
    $what = 'ce plat';
    require __DIR__ . '/partials/danger-zone.php';
    ?>
</section>

<script src="/assets/form.js" defer></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
