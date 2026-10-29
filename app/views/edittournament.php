<?php
// filepath: app/views/edittournament.php
// Variables fournies par TournamentCreationController::edit() :
// $tournament, $old, $errors, $return

$started = tournament_started($tournament);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="create-page">
    <header class="create-page__head">
        <span class="pill">MODIFIER</span>
        <h1 class="create-page__title">Modifier « <?= h($tournament['name']) ?> »</h1>
        <p class="create-page__lead">
            <?= $started ? 'Ce tournoi a commencé.' : 'Ce tournoi n’a pas encore commencé.' ?>
            <?= plural((int) $tournament['votes'], 'vote reçu', 'votes reçus') ?>.
        </p>
    </header>

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

    <form class="create-form box" method="post" action="/tournoi/<?= (int) $tournament['id'] ?>/modifier" data-lock-submit>
        <input type="hidden" name="token" value="<?= h(csrf()) ?>">
        <input type="hidden" name="retour" value="<?= h($return) ?>">

        <p class="create-form__legend"><span aria-hidden="true">*</span> Champ obligatoire</p>

        <div class="create-form__fields">
            <div class="field">
                <label for="name">Nom du tournoi <span class="field__req" aria-hidden="true">*</span></label>
                <input id="name" name="name" maxlength="150" required autocomplete="off"
                    value="<?= h($old['name']) ?>" data-counter>
            </div>
        </div>

        <p class="field__notice">
            Les quarts de finale ne se modifient pas. Tant que le tournoi n’a pas commencé,
            tu peux le supprimer et en créer un autre avec d’autres duels.
        </p>

        <div class="create-form__actions">
            <a class="btn btn--ghost" href="<?= h($return) ?>">Annuler</a>
            <button class="btn btn--primary" type="submit" data-submit-label="Enregistrement…">Enregistrer</button>
        </div>
    </form>

    <?php
    $plan = deletion_plan('tournoi', $tournament);
    $deleteUrl = '/tournoi/' . (int) $tournament['id'] . '/supprimer?retour=' . rawurlencode($return);
    $what = 'ce tournoi';
    require __DIR__ . '/partials/danger-zone.php';
    ?>
</section>

<script src="/assets/form.js" defer></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>