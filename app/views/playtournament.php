<?php

/**
 * Variables fournies par TournamentPlayController.
 *
 * @var string $title
 * @var array<string, mixed>|null $match
 * @var string|null $message
 * @var string|null $error
 * @var array<string, mixed>|null $tournament
 * @var array<int, int> $resultPercentages
 * @var int $resultTotal
 * @var array<int, array<string, mixed>> $recap
 * @var string|null $nextUrl
 */

require_once __DIR__ . '/../includes/header.php';

$slotLabels = [
    'Q1' => 'Quart 1/4',
    'Q2' => 'Quart 2/4',
    'Q3' => 'Quart 3/4',
    'Q4' => 'Quart 4/4',
    'D1' => 'Demi-finale 1/2',
    'D2' => 'Demi-finale 2/2',
    'F'  => 'Finale',
];
?>

<section class="duel-head">
    <div>
        <span class="pill">TOURNOI</span>
        <h1 class="duel-head__title"><?= h($title) ?></h1>
        <?php if ($match): ?>
            <p class="duel-head__lead">
                <?= h($slotLabels[$match['slot']] ?? $match['slot']) ?>
            </p>
        <?php endif ?>
    </div>
</section>

<?php if ($error): ?>
    <p role="alert"><?= h($error) ?></p>
<?php endif ?>

<?php if ($message): ?>
    <div class="box empty">
        <p><strong><?= h($message) ?></strong></p>
    </div>
<?php elseif ($match !== null && $tournament !== null): ?>
    <?php
    $duel = $match['dishes'];
    $category = null;
    $isLogged = true;
    $voteAction = "/play/tournament/{$tournament['id']}/{$match['slot']}/vote";
    $voteFields = [];
    $result = $match['dish_winner_id'] !== null
        ? ['choice' => (int) $match['dish_winner_id']]
        : null;

    require __DIR__ . '/partials/battle.php';
    ?>

    <?php if ($result): ?>
        <section class="duel-result box" role="status">
            <h2>Résultat du duel</h2>

            <div class="duel-bar"
                style="--pct-a: <?= max(15, min(85, $resultPercentages[(int) $duel[0]['id']] ?? 0)) ?>%">
                <span class="duel-bar__a">
                    A · <?= (int) ($resultPercentages[(int) $duel[0]['id']] ?? 0) ?> %
                </span>
                <span class="duel-bar__b">
                    <?= (int) ($resultPercentages[(int) $duel[1]['id']] ?? 0) ?> % · B
                </span>
            </div>

            <p>
                <?= (int) $resultTotal ?> participation<?= $resultTotal > 1 ? 's' : '' ?>
                sur cette paire.
            </p>

            <?php if ($match['slot'] === 'F'): ?>
                <h2>Récapitulatif du tournoi</h2>
                <ol>
                    <?php foreach ($recap as $dish): ?>
                        <li>
                            <?= h($dish['name']) ?> — <?= (int) $dish['score'] ?> point(s)
                        </li>
                    <?php endforeach ?>
                </ol>
                <a class="btn btn--primary" href="<?= h($nextUrl) ?>">Tournoi suivant</a>
            <?php else: ?>
                <a class="btn btn--primary" href="<?= h($nextUrl) ?>">Duel suivant</a>
            <?php endif ?>
        </section>
    <?php endif ?>
<?php endif ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>