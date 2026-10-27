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

            <?php require __DIR__ . '/partials/restaurant-fields.php'; ?>

            <div class="create-form__actions">
                <a class="btn btn--ghost" href="/">Annuler</a>
                <button class="btn btn--primary" type="submit" data-submit-label="Enregistrement…">Enregistrer le restaurant</button>
            </div>
        </form>
    <?php endif ?>
</section>

<script src="/assets/form.js" defer></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
