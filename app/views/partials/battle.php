<?php
/**
 * Variables fournies par la vue qui inclut ce partial.
 *
 * @var array<int, array<string, mixed>> $duel
 * @var array<string, mixed>|null $result
 * @var bool $isLogged
 * @var string|null $category
 * @var string|null $voteAction
 * @var array<string, mixed>|null $voteFields
 */
// filepath: foodbattle-starter/app/views/partials/battle.php
// Carte de duel réutilisable (duel libre, et plus tard matchs de tournoi).
// Attend : $duel = [platA, platB], $category, $isLogged, $result (null avant le vote).
$category = $category ?? null;
$tone = $category !== null
    ? (categories()[$category]['tone'] ?? 'white')
    : 'white';

$defaultPicture = $category !== null
    ? image_for($category)
    : '/assets/images/placeholder.svg';

$voteAction = $voteAction ?? '/duel/vote';
$voteFields = $voteFields ?? [
    'categorie' => $category,
    'a' => $duel[0]['id'],
    'b' => $duel[1]['id'],
];
?>
<div class="battle<?= $result ? ' battle--done' : '' ?>">
    <?php foreach ($duel as $i => $dish): ?>
        <?php
        $side = $i === 0 ? 'a' : 'b';
        $letter = strtoupper($side);
        $isPick = $result && (int) $dish['id'] === (int) $result['choice'];
        $classes = 'battle__card battle__card--' . $side
            . ($result ? ($isPick ? ' is-picked' : ' is-dimmed') : '');
        ?>
        <article class="<?= $classes ?>">
            <span class="battle__letter" aria-hidden="true"><?= $letter ?></span>
            <img class="battle__img tone-<?= h($tone) ?>" src="<?= h($dish['picture'] ?: $defaultPicture) ?>" alt=""
                width="400" height="300" loading="lazy" onerror="this.onerror=null;this.src='/assets/images/placeholder.svg'">
            <div class="battle__body">
                <h2 class="battle__name"><?= h($dish['name']) ?></h2>
                <p class="battle__place"><?= h($dish['restaurant'] ?: 'Plat maison') ?><?= $dish['city'] ? ' · ' . h($dish['city']) : '' ?></p>
                <?php if ($dish['description']): ?>
                    <p class="battle__desc"><?= h($dish['description']) ?></p>
                <?php endif ?>
                <p class="battle__meta">
                    <strong><?= $dish['price'] !== null ? euro($dish['price']) : 'Prix non renseigné' ?></strong>
                    <?php if (isset($dish['votes'])): ?>
                        <span>
                            <?= (int) $dish['votes'] ?> vote<?= $dish['votes'] > 1 ? 's' : '' ?>
                            gagné<?= $dish['votes'] > 1 ? 's' : '' ?> en duel
                        </span>
                    <?php endif ?>
                </p>

                <?php if ($result): ?>
                    <p class="battle__state"><?= $isPick ? 'Ton choix' : '&nbsp;' ?></p>
                <?php elseif ($isLogged): ?>
                    <form method="post" action="<?= h($voteAction) ?>">
                        <input type="hidden" name="token" value="<?= h(csrf()) ?>">

                        <?php foreach ($voteFields as $name => $value): ?>
                            <input type="hidden" name="<?= h($name) ?>" value="<?= h($value) ?>">
                        <?php endforeach ?>

                        <input type="hidden" name="choice" value="<?= (int) $dish['id'] ?>">
                        <button class="btn btn--block battle__vote" type="submit" data-duel-pick="<?= $side ?>"
                            aria-label="Voter pour <?= h($dish['name']) ?>">
                            Je choisis <?= $letter ?> <kbd><?= $side === 'a' ? '←' : '→' ?></kbd>
                        </button>
                    </form>
                <?php else: ?>
                    <a class="btn btn--block btn--ghost" href="/login">Connecte-toi pour voter</a>
                <?php endif ?>
            </div>
        </article>
        <?php if ($i === 0): ?>
            <span class="vs battle__vs" aria-hidden="true">VS</span>
        <?php endif ?>
    <?php endforeach ?>
</div>