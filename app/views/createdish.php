<?php
// filepath: app/views/createdish.php
// Variables fournies par DishController::create() : $errors, $old, $categories, $establishments
$errors ??= [];
$success = isset($_GET['success']);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="create-page">
    <header class="create-page__head">
        <span class="pill">CONTRIBUER</span>
        <h1 class="create-page__title">Ajouter un plat</h1>
        <p class="create-page__lead">Propose un plat du quartier : il entrera aussitôt dans les duels de sa catégorie.</p>
    </header>

    <?php if ($success): ?>
        <div class="create-success box" role="status">
            <p class="create-success__title">Le plat a bien été ajouté.</p>
            <p>Il apparaît déjà sur la carte et peut être tiré au sort dans les duels.</p>
            <div class="create-success__actions">
                <a class="btn btn--primary" href="/plat/creer">Ajouter un autre plat</a>
                <a class="btn btn--ghost" href="/duel">Lancer un duel</a>
            </div>
        </div>
    <?php else: ?>

        <?php if ($errors): ?>
            <div class="auth__errors" role="alert" tabindex="-1" data-autofocus>
                <p><strong>Le plat n’a pas pu être enregistré :</strong></p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= h($error) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>

        <form class="create-form box" method="post" action="/plat/creer" data-lock-submit>
            <input type="hidden" name="token" value="<?= h(csrf()) ?>">

            <p class="create-form__legend"><span aria-hidden="true">*</span> Champ obligatoire</p>

            <div class="create-form__grid">
                <div class="create-form__fields">
                    <fieldset class="create-form__group">
                        <legend>Le plat</legend>

                        <div class="field">
                            <label for="name">Nom <span class="field__req" aria-hidden="true">*</span></label>
                            <input id="name" name="name" maxlength="150" required
                                placeholder="Ex. Le Double Smash" value="<?= h($old['name']) ?>" data-counter>
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
                                    pattern="\d{1,4}([.,]\d{1,2})?" placeholder="12,50"
                                    aria-describedby="price-hint" value="<?= h($old['price']) ?>">
                                <span class="field__suffix" aria-hidden="true">€</span>
                            </div>
                            <p class="field__hint" id="price-hint">En euros, avec une virgule ou un point.</p>
                        </div>
                    </fieldset>

                    <fieldset class="create-form__group">
                        <legend>Où le manger</legend>

                        <div class="field">
                            <label for="establishment_id">Restaurant</label>
                            <select id="establishment_id" name="establishment_id" aria-describedby="establishment-hint">
                                <option value="0">Aucun : plat fait maison</option>
                                <?php foreach ($establishments as $id => $e): ?>
                                    <option value="<?= (int) $id ?>" <?= (int) $old['establishment_id'] === $id ? 'selected' : '' ?>>
                                        <?= h($e['name']) ?> · <?= h($e['city']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                            <p class="field__hint" id="establishment-hint">
                                Il n’est pas dans la liste ? <a href="/restaurant/creer">Ajoute d’abord le restaurant</a>.
                            </p>
                        </div>
                    </fieldset>
                </div>

                <fieldset class="create-form__group">
                    <legend>Catégories <span class="field__req" aria-hidden="true">*</span></legend>
                    <p class="field__hint" id="categories-hint">Une ou plusieurs. Le plat sera tiré au sort dans les duels de chacune.</p>

                    <div class="choice-grid" role="group" aria-describedby="categories-hint">
                        <?php foreach ($categories as $id => $key): ?>
                            <?php $tone = category_tone($key); ?>
                            <label class="choice choice--<?= h($tone) ?>">
                                <input type="checkbox" name="categories[]" value="<?= (int) $id ?>"
                                    <?= in_array($id, $old['categories'], true) ? 'checked' : '' ?>>
                                <span><?= h(category_label($key)) ?></span>
                            </label>
                        <?php endforeach ?>
                    </div>
                </fieldset>
            </div>

            <div class="create-form__actions">
                <a class="btn btn--ghost" href="/">Annuler</a>
                <button class="btn btn--primary" type="submit" data-submit-label="Enregistrement…">Enregistrer le plat</button>
            </div>
        </form>
    <?php endif ?>
</section>

<script src="/assets/form.js" defer></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
