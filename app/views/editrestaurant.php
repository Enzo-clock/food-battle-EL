<?php
// filepath: app/views/editrestaurant.php
// Variables fournies par EstablishmentController::edit() : $restaurant, $old, $errors, $return

require_once __DIR__ . '/../includes/header.php';
?>

<section class="create-page">
    <header class="create-page__head">
        <span class="pill">MODIFIER</span>
        <h1 class="create-page__title">Modifier « <?= h($restaurant['name']) ?> »</h1>
        <p class="create-page__lead"><?= plural((int) $restaurant['dishes'], 'plat rattaché', 'plats rattachés') ?> à ce restaurant.</p>
    </header>

    <?php $row = $restaurant; require __DIR__ . '/partials/moderation-status.php'; ?>

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

    <form class="create-form box" method="post" action="/restaurant/<?= (int) $restaurant['id'] ?>/modifier" data-lock-submit>
        <input type="hidden" name="token" value="<?= h(csrf()) ?>">
        <input type="hidden" name="retour" value="<?= h($return) ?>">

        <p class="create-form__legend"><span aria-hidden="true">*</span> Champ obligatoire</p>

        <?php require __DIR__ . '/partials/restaurant-fields.php'; ?>

        <div class="create-form__actions">
            <a class="btn btn--ghost" href="<?= h($return) ?>">Annuler</a>
            <button class="btn btn--primary" type="submit" data-submit-label="Enregistrement…"><?= $restaurant['status'] === 'rejected' ? 'Corriger et renvoyer' : 'Enregistrer' ?></button>
        </div>
    </form>

    <?php
    $plan = deletion_plan('restaurant', $restaurant);
    $deleteUrl = '/restaurant/' . (int) $restaurant['id'] . '/supprimer?retour=' . rawurlencode($return);
    $what = 'ce restaurant';
    require __DIR__ . '/partials/danger-zone.php';
    ?>
</section>

<script src="/assets/form.js" defer></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
