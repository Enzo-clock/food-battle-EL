<?php
// filepath: app/views/playtournament.php
// Jouer un tournoi du jour : un match à la fois, le tableau du joueur à côté.
// Même structure que la page duel (en-tête, cartes, résultat, panneau latéral) pour que les deux se lisent pareil.
//
// Variables fournies par TournamentPlayController :
//   sans match (tout est joué) : $title, $dailyTotal, $match = null
//   avec match : $tournament (id, name, category, position, daily_total), $match, $bracket, $played,
//                $resultTotal, $resultPercentages, $champion, $championShare, $nextTournament,
//                $nextSlot, $nextUrl, $error

$labels = TournamentRun::SLOT_LABELS;
$trophy = static fn(int $size = 22): string =>
    '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="2.2"'
    . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0z"/>'
    . '<path d="M17 6h3a3 3 0 0 1-3 4M7 6H4a3 3 0 0 0 3 4"/></svg>';

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($match === null): ?>
    <!-- Tout est joué pour aujourd'hui (ou aucun tournoi complet n'existe encore) -->
    <section class="box dash-empty tour-empty">
        <span class="dash-empty__icon"><?= $trophy(40) ?></span>
        <?php if ($dailyTotal > 0): ?>
            <h1 class="dash-empty__title">Tu as joué les <?= plural($dailyTotal, 'tournoi', 'tournois') ?> du jour</h1>
            <p class="dash-empty__text">De nouveaux tournois t’attendent demain. En attendant, départage des duels ou crée ton propre tournoi.</p>
        <?php else: ?>
            <h1 class="dash-empty__title">Aucun tournoi à jouer pour l’instant</h1>
            <p class="dash-empty__text">Un tournoi réunit 4 duels d’une même catégorie. Crée le premier !</p>
        <?php endif ?>
        <div class="create-success__actions">
            <a class="btn btn--primary" href="/duel">Jouer aux duels</a>
            <a class="btn btn--ghost" href="/tournoi/creer">Créer un tournoi</a>
        </div>
    </section>

<?php else: ?>
    <?php
    $slot = $match['slot'];
    $category = $tournament['category'];
    $duel = $match['dishes'];
    $step = array_search($slot, TournamentRun::SLOTS, true) + 1;
    ?>

    <section class="duel-head">
        <div>
            <span class="pill">TOURNOI DU JOUR<?= $tournament['daily_total'] > 1 ? ' · ' . (int) $tournament['position'] . ' SUR ' . (int) $tournament['daily_total'] : '' ?></span>
            <h1 class="duel-head__title"><?= h($tournament['name']) ?></h1>
            <p class="duel-head__lead tour-play__stage">
                <?php if ($category): ?>
                    <span class="tag tone-<?= h(category_tone($category)) ?>"><?= h(category_label($category)) ?></span>
                <?php endif ?>
                <strong><?= h($labels[$slot]) ?></strong>
                <span>· match <?= $step ?> sur 7</span>
            </p>
        </div>
    </section>

    <?php if ($error): ?>
        <p role="alert"><?= h($error) ?></p>
    <?php endif ?>

    <div class="duel-layout">
        <div class="duel-main">

            <?php if ($champion): ?>
                <!-- Fin du tournoi : le champion remplace le dernier duel -->
                <?php
                $runnerUp = (int) $duel[0]['id'] === (int) $champion['id'] ? $duel[1] : $duel[0];
                // On compare aux AUTRES joueurs : sa propre partie est retirée du calcul.
                $others = $championShare['total'] - 1;
                $othersSame = max(0, $championShare['same'] - 1);
                $sharePct = $others > 0 ? (int) round($othersSame * 100 / $others) : 0;
                ?>
                <section class="box tour-champion" id="resultat" tabindex="-1" aria-labelledby="champion-title" data-confetti>
                    <span class="pill tour-champion__pill"><?= $trophy(16) ?> TON CHAMPION</span>
                    <div class="tour-champion__body">
                        <img class="tour-champion__img tone-<?= h(category_tone((string) $category)) ?>"
                            src="<?= h($champion['picture'] ?: image_for((string) $category)) ?>" alt="" width="240" height="240"
                            onerror="this.onerror=null;this.src='/assets/images/placeholder.svg'">
                        <div class="tour-champion__text">
                            <h2 class="tour-champion__name" id="champion-title"><?= h($champion['name']) ?></h2>
                            <p class="battle__place"><?= h($champion['restaurant'] ?: 'Plat maison') ?><?= $champion['city'] ? ' · ' . h($champion['city']) : '' ?></p>
                            <p class="tour-champion__final">Vainqueur en finale contre <strong><?= h($runnerUp['name']) ?></strong>.</p>
                            <p class="tour-champion__share">
                                <?php if ($others === 0): ?>
                                    Tu es le premier à terminer ce tournoi : ton champion ouvre le palmarès.
                                <?php elseif ($others === 1): ?>
                                    <?= $othersSame ? 'L’autre joueur a couronné le même champion que toi.' : 'L’autre joueur a couronné un autre plat : ton choix est unique.' ?>
                                <?php elseif ($othersSame === 0): ?>
                                    Choix unique : aucun des <?= $others ?> autres joueurs n’a couronné ce plat.
                                <?php else: ?>
                                    <strong><?= $sharePct ?> %</strong> des autres joueurs ont aussi couronné ce plat
                                    (<?= $othersSame ?> sur <?= $others ?>).
                                <?php endif ?>
                            </p>
                        </div>
                    </div>

                    <div class="tour-champion__actions">
                        <?php if ($nextTournament): ?>
                            <a class="btn btn--dark" href="/play/tournament" data-duel-next autofocus>
                                Tournoi suivant : <?= h($nextTournament['name']) ?> <kbd>Entrée</kbd>
                            </a>
                            <span class="tour-champion__hint">Tournoi <?= (int) $nextTournament['position'] ?> sur <?= (int) $nextTournament['daily_total'] ?> aujourd’hui</span>
                        <?php else: ?>
                            <p class="tour-champion__hint"><strong>Tu as joué tous les tournois du jour.</strong> Reviens demain pour les suivants !</p>
                            <a class="btn btn--dark" href="/duel">Jouer aux duels</a>
                            <a class="btn btn--ghost" href="/tournoi/creer">Créer un tournoi</a>
                        <?php endif ?>
                    </div>
                </section>

            <?php else: ?>
                <?php
                $isLogged = true;
                $voteAction = "/play/tournament/{$tournament['id']}/{$slot}/vote";
                $voteFields = [];
                $result = $played ? ['choice' => (int) $match['dish_winner_id']] : null;
                require __DIR__ . '/partials/battle.php';
                ?>

                <?php if ($played): ?>
                    <?php
                    $winner = (int) $duel[0]['id'] === (int) $match['dish_winner_id'] ? $duel[0] : $duel[1];
                    $pctA = (int) ($resultPercentages[(int) $duel[0]['id']] ?? 0);
                    $pctB = (int) ($resultPercentages[(int) $duel[1]['id']] ?? 0);
                    // Accord avec les AUTRES joueurs : son propre vote est retiré du calcul.
                    $others = $resultTotal - 1;
                    $othersAgree = max(0, (int) ($resultCounts[(int) $winner['id']] ?? 0) - 1);
                    $agree = $others > 0 ? (int) round($othersAgree * 100 / $others) : 0;
                    ?>
                    <section class="duel-result box" id="resultat" tabindex="-1" role="status">
                        <p class="duel-result__verdict">
                            <strong><?= h($winner['name']) ?></strong> file <?= h(TournamentRun::NEXT_ROUND[$slot]) ?>.
                            <?php if ($others === 0): ?>
                                Tu es le premier à départager ce match.
                            <?php elseif ($others === 1): ?>
                                <?= $othersAgree ? 'L’autre joueur a fait le même choix.' : 'L’autre joueur a fait un autre choix.' ?>
                            <?php elseif ($othersAgree === 0): ?>
                                Choix unique : aucun des <?= $others ?> autres joueurs n’a fait ce choix.
                            <?php else: ?>
                                <?= $agree ?> % des autres joueurs ont fait le même choix (<?= $othersAgree ?> sur <?= $others ?>).
                            <?php endif ?>
                        </p>
                        <?php if ($resultTotal > 1): ?>
                            <?php // Largeur bornée à 15–85 % pour que les deux libellés restent lisibles (comme sur la page duel). ?>
                            <div class="duel-bar" style="--pct-a: <?= max(15, min(85, $pctA)) ?>%">
                                <span class="duel-bar__a"><?= $pctA ?> % · <?= h($duel[0]['name']) ?> </span>
                                <span class="duel-bar__b"><?= h($duel[1]['name']) ?> · <?= $pctB ?> %</span>
                            </div>
                        <?php endif ?>
                        <div class="duel-result__foot">
                            <span><?= plural($resultTotal, 'joueur a', 'joueurs ont') ?> départagé ce match, toi compris</span>
                            <a class="btn btn--primary" href="<?= h($nextUrl) ?>" data-duel-next autofocus>
                                <?= $nextSlot ? h($labels[$nextSlot]) : 'Voir la suite' ?> <kbd>Entrée</kbd>
                            </a>
                        </div>
                    </section>
                <?php endif ?>
            <?php endif ?>
        </div>

        <aside class="duel-side" aria-label="Ton tableau">
            <div class="box tour-preview tour-play__bracket">
                <h2 class="tour-preview__title"><?= $trophy() ?> Ton tableau</h2>

                <?php foreach (['Quarts de finale' => ['Q1', 'Q2', 'Q3', 'Q4'], 'Demi-finales' => ['D1', 'D2'], 'Finale' => ['F']] as $round => $slots): ?>
                    <p class="tour-preview__round"><?= h($round) ?></p>
                    <ol class="tour-bracket">
                        <?php foreach ($slots as $s): ?>
                            <?php
                            $cell = $bracket[$s];
                            $state = $cell['winner'] !== null ? 'is-filled' : ($s === $slot ? 'is-current' : '');
                            $feeds = ['D1' => 'Q1 – Q2', 'D2' => 'Q3 – Q4', 'F' => 'D1 – D2'];
                            ?>
                            <li class="tour-slot <?= $state ?><?= $s === 'F' ? ' tour-slot--final' : '' ?>" <?= $s === $slot ? 'aria-current="step"' : '' ?>>
                                <span class="tour-slot__seed"><?= $s === 'F' ? $trophy(16) : h($s) ?></span>
                                <span class="tour-slot__name">
                                    <?php if ($cell['winner'] !== null): ?>
                                        <strong><?= h($cell['winner']) ?></strong>
                                        <small>contre <?= h($cell['winner'] === $cell['a'] ? $cell['b'] : $cell['a']) ?></small>
                                    <?php elseif ($cell['a'] !== null && $cell['b'] !== null): ?>
                                        <?= h($cell['a']) ?> <span class="tour-slot__vs">vs</span> <?= h($cell['b']) ?>
                                        <?php if ($s === $slot): ?><small>À toi de jouer</small><?php endif ?>
                                    <?php else: ?>
                                        <span class="tour-slot__tbd">Vainqueurs <?= h($feeds[$s] ?? '') ?></span>
                                    <?php endif ?>
                                </span>
                            </li>
                        <?php endforeach ?>
                    </ol>
                <?php endforeach ?>
            </div>

        </aside>
    </div>
<?php endif ?>

<script src="/assets/duel.js" defer></script>
<?php if ($match !== null && $champion): ?>
    <script src="/assets/confetti.js" defer></script>
<?php endif ?>
<script>
    // Après un vote, on arrive sur #resultat : le focus y est placé pour les lecteurs d'écran.
    if (location.hash === '#resultat') {
        var resultat = document.getElementById('resultat');
        if (resultat) resultat.focus({ preventScroll: true });
    }
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
