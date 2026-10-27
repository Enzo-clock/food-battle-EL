<?php
// filepath: app/views/edittournament.php
// Variables fournies par TournamentCreationController::edit() :
// $tournament, $old, $errors, $dateEditable, $today, $quarters, $return

$tomorrow = date('Y-m-d', strtotime('+1 day'));
$started = tournament_started($tournament);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="create-page">
    <header class="create-page__head">
        <span class="pill">MODIFIER</span>
        <h1 class="create-page__title">Modifier « <?= h($tournament['name']) ?> »</h1>
        <p class="create-page__lead">
            <?= $started ? 'Ce tournoi a commencé le ' . h(date_fr($tournament['start_date'])) . '.' : 'Ce tournoi commence le ' . h(date_fr($tournament['start_date'])) . '.' ?>
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

        <div class="create-form__grid create-form__grid--even">
            <div class="field">
                <label for="name">Nom du tournoi <span class="field__req" aria-hidden="true">*</span></label>
                <input id="name" name="name" maxlength="150" required autocomplete="off" value="<?= h($old['name']) ?>" data-counter>
            </div>

            <div class="field<?= $dateEditable ? '' : ' field--locked' ?>">
                <label for="start_date">Premier match <?= $dateEditable ? '<span class="field__req" aria-hidden="true">*</span>' : '' ?></label>
                <?php if ($dateEditable): ?>
                    <input id="start_date" name="start_date" type="date" required min="<?= h($tomorrow) ?>"
                        value="<?= h($old['start_date']) ?>" aria-describedby="date-hint">
                    <p class="field__hint" id="date-hint">Les <?= $quarters ?> quarts suivent : un match par jour à partir de cette date.</p>
                <?php else: ?>
                    <input id="start_date" type="date" value="<?= h($tournament['start_date']) ?>" readonly aria-describedby="date-lock">
                    <p class="field__notice" id="date-lock">
                        <?= $started ? 'Le tournoi a commencé : ses dates sont fixées.' : 'Le tournoi a déjà reçu des votes : ses dates sont fixées.' ?>
                    </p>
                <?php endif ?>
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
