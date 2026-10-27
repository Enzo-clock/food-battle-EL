<?php
// filepath: app/views/confirm-delete.php
// Confirmation de suppression (sans JavaScript, ou lien ouvert directement).
// Variables fournies par handle_deletion() : $type, $name, $plan, $return

$blocked = $plan['mode'] === 'blocked';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="create-page confirm-page">
    <header class="create-page__head">
        <span class="pill"><?= $blocked ? 'IMPOSSIBLE' : 'CONFIRMATION' ?></span>
        <h1 class="create-page__title"><?= h($plan['label']) ?> « <?= h($name) ?> » ?</h1>
    </header>

    <div class="box confirm-box confirm-box--<?= h($plan['mode']) ?>">
        <p class="confirm-box__text"><?= h($plan['message']) ?></p>

        <form class="confirm-box__actions" method="post">
            <input type="hidden" name="token" value="<?= h(csrf()) ?>">
            <input type="hidden" name="retour" value="<?= h($return) ?>">
            <a class="btn btn--ghost" href="<?= h($return) ?>"><?= $blocked ? 'Retour' : 'Annuler' ?></a>
            <?php if (!$blocked): ?>
                <button class="btn <?= $plan['mode'] === 'delete' ? 'btn--danger' : 'btn--dark' ?>" type="submit">
                    <?= h($plan['label']) ?>
                </button>
            <?php endif ?>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
