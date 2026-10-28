<?php
// filepath: app/controllers/TournamentPlayController.php
//
// « Jouer » : les tournois du jour. Chaque joueur remplit SON tableau, match après match
// (4 quarts, 2 demies, la finale) ; ses vainqueurs avancent au tour suivant.
// Après chaque match, on lui montre ce qu'ont choisi les autres joueurs.

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/TournamentRun.php';

class TournamentPlayController
{
    public function __construct(private PDO $db) {}

    /** GET /play/tournament : reprend le tournoi du jour en cours, au bon match. */
    public function index(): void
    {
        $userId = $this->requireUserId();
        $runs = new TournamentRun($this->db);
        $next = $runs->nextForUser($userId);

        if ($next === null) {
            // Plus rien à jouer aujourd'hui (ou aucun tournoi complet) : état final de la page.
            $title = 'Tournois du jour';
            $dailyTotal = count($runs->dailyTournaments());
            $match = null;
            require __DIR__ . '/../views/playtournament.php';
            return;
        }

        $tournamentId = (int) $next['id'];
        $runId = $next['run_id'] ?? $runs->start($tournamentId, $userId);
        $slot = $runs->nextSlotForRun($runId, $userId);

        if ($slot === null) {
            // Partie terminée entre-temps (autre onglet) : on repasse par la sélection.
            $this->redirect('/play/tournament');
        }

        $this->redirect("/play/tournament/$tournamentId/$slot");
    }

    /** GET /play/tournament/{id}/{slot} : un match du tableau (à jouer, ou déjà joué avec son résultat). */
    public function show(int $tournamentId, string $slot): void
    {
        $userId = $this->requireUserId();
        $runs = new TournamentRun($this->db);
        $nextSlot = null;

        try {
            $runId = $runs->start($tournamentId, $userId);
            $nextSlot = $runs->nextSlotForRun($runId, $userId);
            $match = $runs->currentMatch($runId, $slot, $userId);
        } catch (DomainException) {
            $this->redirect($nextSlot ? "/play/tournament/$tournamentId/$nextSlot" : '/play/tournament');
        }

        $this->randomizeSides($runId, $slot, $match);

        $tournament = $runs->info($tournamentId);
        $bracket = $runs->bracket($runId, $tournamentId, $userId);
        $played = $match['dish_winner_id'] !== null;

        // Ce qu'ont choisi les autres joueurs sur ce même match (même tournoi, même étape, même paire)
        $resultTotal = 0;
        $resultPercentages = [];
        $resultCounts = [];
        if ($played) {
            $counts = $resultCounts = $runs->resultsForMatch($runId, $slot, $userId);
            $resultTotal = array_sum($counts);
            foreach ([$match['dish_1_id'], $match['dish_2_id']] as $dishId) {
                $resultPercentages[$dishId] = $resultTotal > 0 ? (int) round(($counts[$dishId] ?? 0) * 100 / $resultTotal) : 0;
            }
        }

        // Après la finale : le champion, et combien de joueurs ont couronné le même
        $champion = null;
        $championShare = null;
        $nextTournament = null;
        if ($slot === 'F' && $played) {
            foreach ($match['dishes'] as $dish) {
                if ((int) $dish['id'] === (int) $match['dish_winner_id']) {
                    $champion = $dish;
                }
            }
            $championShare = $runs->championShare($tournamentId, (int) $match['dish_winner_id']);
            $upcoming = $runs->nextForUser($userId);
            $nextTournament = $upcoming ? $runs->info((int) $upcoming['id']) : null;
        }

        $nextUrl = $nextSlot ? "/play/tournament/$tournamentId/$nextSlot" : '/play/tournament';
        $error = $_SESSION['tournament_error'] ?? null;
        unset($_SESSION['tournament_error']);

        $title = $tournament['name'] . ' · ' . TournamentRun::SLOT_LABELS[$slot];
        require __DIR__ . '/../views/playtournament.php';
    }

    /** POST /play/tournament/{id}/{slot}/vote */
    public function vote(int $tournamentId, string $slot): void
    {
        check_post();

        $userId = $this->requireUserId();
        $runs = new TournamentRun($this->db);
        $dishId = filter_input(INPUT_POST, 'choice', FILTER_VALIDATE_INT);

        try {
            $runId = $runs->start($tournamentId, $userId);

            if ($dishId === false || $dishId === null) {
                throw new InvalidArgumentException('Choix de plat invalide.');
            }

            $runs->vote($runId, $slot, $dishId, $userId);
        } catch (InvalidArgumentException $error) {
            $_SESSION['tournament_error'] = $error->getMessage();
        } catch (DomainException) {
            $this->redirect('/play/tournament');
        }

        // PRG : le GET relit le match et son vainqueur depuis la base.
        // #resultat : on arrive directement sur le résultat, sans avoir à défiler.
        $this->redirect("/play/tournament/$tournamentId/$slot#resultat");
    }

    private function requireUserId(): int
    {
        $id = $_SESSION['user_id'] ?? null;

        if (!is_numeric($id)) {
            $this->redirect('/login');
        }

        return (int) $id;
    }

    /** Gauche/droite tirés au sort une fois par match, pour éviter le biais de position. */
    private function randomizeSides(int $runId, string $slot, array &$match): void
    {
        $_SESSION['tournament_sides'] ??= [];
        $_SESSION['tournament_sides'][$runId] ??= [];

        if (!isset($_SESSION['tournament_sides'][$runId][$slot])) {
            $_SESSION['tournament_sides'][$runId][$slot] =
                random_int(0, 1) === 1 ? [1, 0] : [0, 1];
        }

        $order = $_SESSION['tournament_sides'][$runId][$slot];
        $match['dishes'] = [
            $match['dishes'][$order[0]],
            $match['dishes'][$order[1]],
        ];
    }

    private function redirect(string $url): never
    {
        header('Location: ' . $url, true, 303);
        exit;
    }
}
