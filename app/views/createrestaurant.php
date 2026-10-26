<?php
// filepath: app/views/createrestaurant.php
// Variables fournies par EstablishmentController::create() : $errors, $old
// Champs : name, description, city, address, token (plus de photo : elle se met sur les plats et les catégories).
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

        <form class="create-form box" method="post" action="/restaurant/creer" data-lock-submit>
            <input type="hidden" name="token" value="<?= h(csrf()) ?>">

            <p class="create-form__legend"><span aria-hidden="true">*</span> Champ obligatoire</p>

            <div class="create-form__grid create-form__grid--even">
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

            <div class="create-form__actions">
                <a class="btn btn--ghost" href="/">Annuler</a>
                <button class="btn btn--primary" type="submit" data-submit-label="Enregistrement…">Enregistrer le restaurant</button>
            </div>
        </form>
    <?php endif ?>
</section>

<script src="/assets/form.js" defer></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
