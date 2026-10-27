<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/TournamentRun.php';

class TournamentPlayController
{
    public function __construct(private PDO $db) {}

    /** GET /play/tournament */
    public function index(): void
    {
        $userId = $this->requireUserId();
        $runs = new TournamentRun($this->db);
        $tournament = $runs->nextForUser($userId);

        if ($tournament === null) {
            $title = 'Tournois du jour';
            $message = 'Tu as terminé les tournois du jour. Reviens demain !';
            $match = null;
            $error = null;
            $nextUrl = null;
            $recap = [];
            $tournament = null;
            $resultTotal = 0;
            $resultPercentages = [];
            require __DIR__ . '/../views/playtournament.php';
            return;
        }

        $tournamentId = (int) $tournament['id'];
        $runId = $tournament['run_id']
            ?? $runs->start($tournamentId, $userId);
        $slot = $runs->nextSlotForRun($runId, $userId);

        if ($slot === null) {
            // Le run vient éventuellement d’être terminé dans un autre onglet.
            $this->redirect('/play/tournament');
        }

        $this->redirect("/play/tournament/$tournamentId/$slot");
    }

    /** GET /play/tournament/{id}/{slot} */
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
            $target = $nextSlot
                ? "/play/tournament/$tournamentId/$nextSlot"
                : '/play/tournament';
            $this->redirect($target);
        }

        $this->randomizeSides($runId, $slot, $match);

        $resultCounts = $match['dish_winner_id'] !== null
            ? $runs->resultsForMatch($runId, $slot, $userId)
            : [];

        $resultTotal = array_sum($resultCounts);
        $resultPercentages = [];

        foreach ([$match['dish_1_id'], $match['dish_2_id']] as $dishId) {
            $resultPercentages[$dishId] = $resultTotal > 0
                ? (int) round(($resultCounts[$dishId] ?? 0) * 100 / $resultTotal)
                : 0;
        }

        $tournament = [
            'id' => $tournamentId,
            'name' => 'Tournoi',
        ];
        $error = $_SESSION['tournament_error'] ?? null;
        unset($_SESSION['tournament_error']);

        $recap = ($slot === 'F' && $match['dish_winner_id'] !== null)
            ? $runs->recap($runId, $userId)
            : [];

        $nextUrl = $nextSlot
            ? "/play/tournament/$tournamentId/$nextSlot"
            : '/play/tournament';

        $title = 'Tournoi — ' . $slot;
        $message = null;

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
        $this->redirect("/play/tournament/$tournamentId/$slot");
    }

    private function requireUserId(): int
    {
        $id = $_SESSION['user_id'] ?? null;

        if (!is_numeric($id)) {
            $this->redirect('/login');
        }

        return (int) $id;
    }

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
