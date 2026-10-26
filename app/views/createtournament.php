<?php
// filepath: app/views/createtournament.php

$categories = $categories ?? [];
$battles = $battles ?? [];
$errors = $errors ?? [];
$selectedBattleIds = array_map(
    'intval',
    $selectedBattleIds ?? []
);
$success = $success ?? null;

require_once __DIR__ . '/../includes/header.php';
?>

<main class="section">
    <h1 class="section__title">Créer un tournoi</h1>

    <?php if ($success): ?>
        <div class="create-success box" role="status">
            <p class="create-success__title">
                Le tournoi « <?= h($success['name']) ?> » a été créé avec succès.
            </p>
        </div>
    <?php else: ?>
        <p>Sélectionne quatre battles pour les quarts de finale.</p>

        <?php if ($errors): ?>
            <div class="auth__errors" role="alert">
                <?php foreach ($errors as $error): ?>
                    <p><?= h($error) ?></p>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <form method="post" action="/tournoi/creer" id="tournament-form">
            <input type="hidden" name="token" value="<?= h(csrf()) ?>">

            <div class="field">
                <label for="name">Nom du tournoi</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    maxlength="150"
                    value="<?= h($name ?? '') ?>"
                    required>
            </div>

            <div class="field">
                <label for="start_date">Date de début</label>
                <input
                    id="start_date"
                    name="start_date"
                    type="date"
                    value="<?= h($startDate ?? '') ?>"
                    required>
            </div>

            <div class="field">
                <label for="category">Catégorie</label>
                <select id="category" name="category" required>
                    <option value="">Choisir une catégorie</option>
                    <?php foreach ($categories as $key => $data): ?>
                        <option
                            value="<?= h((string) $key) ?>"
                            <?= ($category ?? '') === (string) $key ? 'selected' : '' ?>>
                            <?= h($data['label'] ?? category_label((string) $key)) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <fieldset>
                <legend>Choisir quatre battles</legend>
                <p id="battle-selection-status" role="status">
                    <?= count($selectedBattleIds) ?> battle(s) sélectionnée(s) sur 4.
                </p>

                <?php if (!$battles): ?>
                    <p>Aucune battle disponible pour le moment.</p>
                <?php else: ?>
                    <div class="dish-grid">
                        <?php foreach ($battles as $battle): ?>
                            <?php $battleId = (int) $battle['battle_id']; ?>
                            <label class="dish-card">
                                <input
                                    type="checkbox"
                                    name="battle_ids[]"
                                    value="<?= $battleId ?>"
                                    <?= in_array($battleId, $selectedBattleIds, true) ? 'checked' : '' ?>>
                                <span>
                                    <?= h($battle['dish_1_name']) ?>
                                    contre
                                    <?= h($battle['dish_2_name']) ?>
                                </span>
                            </label>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </fieldset>

            <button class="btn btn--primary" type="submit">
                Créer le tournoi
            </button>
        </form>
    <?php endif; ?>
</main>

<script>
    (() => {
        const checkboxes = [...document.querySelectorAll('input[name="battle_ids[]"]')];
        const status = document.getElementById('battle-selection-status');

        function updateSelection() {
            const selected = checkboxes.filter(checkbox => checkbox.checked);

            checkboxes.forEach(checkbox => {
                checkbox.disabled = !checkbox.checked && selected.length >= 4;
            });

            if (status) {
                status.textContent = `${selected.length} battle(s) sélectionnée(s) sur 4.`;
            }
        }

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelection);
        });

        updateSelection();
    })();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>