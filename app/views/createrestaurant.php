<?php
// filepath: app/views/createrestaurant.php
// Variables fournies par EstablishmentController::create() : $errors, $old
// Les noms des champs (name, description, picture, city, address, token) sont inchangés.
if (!isset($errors) || !is_array($errors)) {
    $errors = [];
}

if (!isset($old) || !is_array($old)) {
    $old = [];
}

$old = array_merge(
    ['name' => '', 'description' => '', 'city' => '', 'address' => ''],
    $old
);

// Un navigateur ne conserve jamais un fichier choisi après un envoi en erreur :
// on prévient l'utilisateur qu'il doit resélectionner sa photo.
$pictureWasSent = $errors && (($_FILES['picture']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);
$success = isset($_GET['success']);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="create-page">
    <header class="create-page__head">
        <span class="pill">CONTRIBUER</span>
        <h1 class="create-page__title">Ajouter un restaurant</h1>
        <p class="create-page__lead">Fais découvrir une adresse du quartier. Ses plats pourront ensuite entrer dans les duels.</p>
    </header>

    <?php if ($success): ?>
        <div class="create-success box" role="status">
            <p class="create-success__title">Le restaurant a bien été ajouté.</p>
            <p>Merci pour ta contribution ! Tu peux maintenant lui ajouter des plats.</p>
            <div class="create-success__actions">
                <a class="btn btn--primary" href="/restaurant/creer">Ajouter un autre restaurant</a>
                <a class="btn btn--ghost" href="/">Retour à l’accueil</a>
            </div>
        </div>
    <?php else: ?>

        <?php if ($errors): ?>
            <div class="auth__errors" role="alert" tabindex="-1" data-autofocus>
                <p><strong>Le restaurant n’a pas pu être enregistré :</strong></p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= h($error) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>

        <form class="create-form box" method="post" action="/restaurant/creer" enctype="multipart/form-data" data-lock-submit>
            <input type="hidden" name="token" value="<?= h(csrf()) ?>">

            <p class="create-form__legend"><span aria-hidden="true">*</span> Champ obligatoire</p>

            <div class="create-form__grid">
                <div class="create-form__fields">
                    <fieldset class="create-form__group">
                        <legend>L’établissement</legend>

                        <div class="field">
                            <label for="name">Nom <span class="field__req" aria-hidden="true">*</span></label>
                            <input id="name" name="name" maxlength="150" required autocomplete="organization"
                                placeholder="Ex. Le Comptoir Smash" value="<?= h($old['name']) ?>" data-counter>
                        </div>

                        <div class="field">
                            <label for="description">Description <span class="field__opt">(facultatif)</span></label>
                            <textarea id="description" name="description" rows="4" maxlength="1000"
                                placeholder="Spécialités, ambiance, ce qui le rend unique…" data-counter><?= h($old['description']) ?></textarea>
                        </div>
                    </fieldset>

                    <fieldset class="create-form__group">
                        <legend>Où le trouver</legend>

                        <div class="field">
                            <label for="address">Adresse <span class="field__req" aria-hidden="true">*</span></label>
                            <input id="address" name="address" required autocomplete="street-address"
                                placeholder="Ex. 12 rue des Ateliers" value="<?= h($old['address']) ?>">
                        </div>

                        <div class="field">
                            <label for="city">Ville <span class="field__req" aria-hidden="true">*</span></label>
                            <input id="city" name="city" maxlength="80" required autocomplete="address-level2"
                                placeholder="Ex. Lille" value="<?= h($old['city']) ?>">
                        </div>
                    </fieldset>
                </div>

                <fieldset class="create-form__group create-form__photo">
                    <legend>Photo <span class="field__opt">(facultative)</span></legend>

                    <div class="dropzone" data-dropzone>
                        <input class="dropzone__input" id="picture" name="picture" type="file"
                            accept="image/jpeg,image/png,image/webp" aria-label="Choisir une photo du restaurant"
                            aria-describedby="picture-hint" data-preview-input>
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
                        Une photo de la devanture ou de la salle donne envie de découvrir l’adresse.
                        <?php if ($pictureWasSent): ?>
                            <strong class="field__warn">Pense à resélectionner ta photo.</strong>
                        <?php endif ?>
                    </p>
                </fieldset>
            </div>

            <div class="create-form__actions">
                <a class="btn btn--ghost" href="/">Annuler</a>
                <button class="btn btn--primary" type="submit" data-submit-label="Enregistrement…">Enregistrer le restaurant</button>
            </div>
        </form>
    <?php endif ?>
</section>

<script src="/assets/form.js" defer></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
