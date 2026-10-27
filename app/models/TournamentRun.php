<?php

class TournamentRun
{
    private const SLOTS = ['Q1', 'Q2', 'Q3', 'Q4', 'D1', 'D2', 'F'];

    public function __construct(private PDO $db) {}

    /** Les trois tournois du jour qui ont leurs quatre quarts complets. */
    public function dailyTournaments(): array
    {
        $stmt = $this->db->query("
            SELECT t.id, t.name, t.start_date
            FROM tournaments t
            JOIN battles b ON b.tournament_id = t.id
            GROUP BY t.id, t.name, t.start_date
            HAVING COUNT(*) FILTER (
                WHERE b.phase = 'quarter'
                  AND b.position BETWEEN 1 AND 4
                  AND b.dish_1_id IS NOT NULL
                  AND b.dish_2_id IS NOT NULL
            ) = 4
            ORDER BY md5(t.id::text || CURRENT_DATE::text)
            LIMIT 3
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Premier tournoi du jour non terminé.
     * Ajoute run_id (null si l'utilisateur n'a pas encore commencé ce tournoi).
     */
    public function nextForUser(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, finished_at
            FROM tournament_runs
            WHERE tournament_id = :tournament_id
              AND user_id = :user_id
              AND run_date = CURRENT_DATE
        ");

        foreach ($this->dailyTournaments() as $tournament) {
            $stmt->execute([
                'tournament_id' => $tournament['id'],
                'user_id' => $userId,
            ]);
            $run = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$run || $run['finished_at'] === null) {
                $tournament['run_id'] = $run ? (int) $run['id'] : null;
                return $tournament;
            }
        }

        return null;
    }

    /** Crée la participation du jour, ou renvoie son identifiant si elle existe. */
    public function start(int $tournamentId, int $userId): int
    {
        $isDailyTournament = false;

        foreach ($this->dailyTournaments() as $tournament) {
            if ((int) $tournament['id'] === $tournamentId) {
                $isDailyTournament = true;
                break;
            }
        }

        if (!$isDailyTournament) {
            throw new DomainException('Ce tournoi n’est pas disponible aujourd’hui.');
        }

        $stmt = $this->db->prepare("
            INSERT INTO tournament_runs (tournament_id, user_id)
            VALUES (:tournament_id, :user_id)
            ON CONFLICT (tournament_id, user_id, run_date) DO NOTHING
        ");
        $stmt->execute([
            'tournament_id' => $tournamentId,
            'user_id' => $userId,
        ]);

        $stmt = $this->db->prepare("
            SELECT id, finished_at
            FROM tournament_runs
            WHERE tournament_id = :tournament_id
              AND user_id = :user_id
              AND run_date = CURRENT_DATE
        ");
        $stmt->execute([
            'tournament_id' => $tournamentId,
            'user_id' => $userId,
        ]);
        $run = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$run) {
            throw new RuntimeException('Impossible de créer ou retrouver la participation.');
        }

        return (int) $run['id'];
    }

    /**
     * Renvoie les deux plats du duel demandé.
     * Les duels déjà votés restent consultables pour afficher leur résultat.
     */
    public function currentMatch(int $runId, string $slot, int $userId): array
    {
        $this->validateSlot($slot);
        $run = $this->ownedRun($runId, $userId);

        $stmt = $this->db->prepare("
            SELECT dish_1_id, dish_2_id, dish_winner_id
            FROM tournament_run_matches
            WHERE run_id = :run_id AND slot = :slot
        ");
        $stmt->execute(['run_id' => $runId, 'slot' => $slot]);
        $savedMatch = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($savedMatch) {
            $dishIds = [(int) $savedMatch['dish_1_id'], (int) $savedMatch['dish_2_id']];
            $winnerId = $savedMatch['dish_winner_id'] === null
                ? null
                : (int) $savedMatch['dish_winner_id'];
        } else {
            if ($this->nextSlot($runId) !== $slot) {
                throw new DomainException('Ce duel n’est pas encore accessible.');
            }

            $dishIds = $this->resolvePair((int) $run['tournament_id'], $runId, $slot);
            $winnerId = null;
        }

        return [
            'slot' => $slot,
            'dish_1_id' => $dishIds[0],
            'dish_2_id' => $dishIds[1],
            'dish_winner_id' => $winnerId,
            'dishes' => $this->loadDishes($dishIds),
        ];
    }

    /**
     * Enregistre le choix. Retourne false si ce duel avait déjà été voté.
     */
    public function vote(int $runId, string $slot, int $dishId, int $userId): bool
    {
        $this->validateSlot($slot);
        $this->db->beginTransaction();

        try {
            $run = $this->ownedRun($runId, $userId, true);

            $stmt = $this->db->prepare("
                SELECT 1
                FROM tournament_run_matches
                WHERE run_id = :run_id AND slot = :slot
            ");
            $stmt->execute(['run_id' => $runId, 'slot' => $slot]);

            if ($stmt->fetchColumn()) {
                $this->db->commit();
                return false;
            }

            if ($run['finished_at'] !== null || $this->nextSlot($runId) !== $slot) {
                throw new DomainException('Ce duel n’est pas accessible.');
            }

            $match = $this->currentMatch($runId, $slot, $userId);
            if (!in_array($dishId, [$match['dish_1_id'], $match['dish_2_id']], true)) {
                throw new InvalidArgumentException('Le plat choisi ne fait pas partie de ce duel.');
            }

            $stmt = $this->db->prepare("
                INSERT INTO tournament_run_matches (
                    run_id, slot, dish_1_id, dish_2_id, dish_winner_id
                )
                VALUES (:run_id, :slot, :dish_1_id, :dish_2_id, :winner_id)
            ");
            $stmt->execute([
                'run_id' => $runId,
                'slot' => $slot,
                'dish_1_id' => $match['dish_1_id'],
                'dish_2_id' => $match['dish_2_id'],
                'winner_id' => $dishId,
            ]);

            if ($slot === 'F') {
                $stmt = $this->db->prepare("
                    UPDATE tournament_runs
                    SET finished_at = CURRENT_TIMESTAMP
                    WHERE id = :run_id AND user_id = :user_id
                ");
                $stmt->execute(['run_id' => $runId, 'user_id' => $userId]);
            }

            $this->db->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    /** Nombre de choix pour chaque plat parmi les runs ayant joué ce même match. */
    public function resultsForMatch(int $runId, string $slot, int $userId): array
    {
        $this->ownedRun($runId, $userId);

        $stmt = $this->db->prepare("
        WITH target AS (
            SELECT
                r.tournament_id,
                m.slot,
                LEAST(m.dish_1_id, m.dish_2_id) AS dish_low,
                GREATEST(m.dish_1_id, m.dish_2_id) AS dish_high
            FROM tournament_run_matches m
            JOIN tournament_runs r ON r.id = m.run_id
            WHERE m.run_id = :run_id
              AND m.slot = :slot
        )
        SELECT m.dish_winner_id, COUNT(*) AS votes
        FROM tournament_run_matches m
        JOIN tournament_runs r ON r.id = m.run_id
        JOIN target t
          ON r.tournament_id = t.tournament_id
         AND m.slot = t.slot
         AND LEAST(m.dish_1_id, m.dish_2_id) = t.dish_low
         AND GREATEST(m.dish_1_id, m.dish_2_id) = t.dish_high
        WHERE m.dish_winner_id IS NOT NULL
        GROUP BY m.dish_winner_id
    ");
        $stmt->execute(['run_id' => $runId, 'slot' => $slot]);

        $counts = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $counts[(int) $row['dish_winner_id']] = (int) $row['votes'];
        }

        return $counts;
    }

    /** Récapitulatif des huit plats et de leurs points dans ce run terminé. */
    public function recap(int $runId, int $userId): array
    {
        $run = $this->ownedRun($runId, $userId);

        if ($run['finished_at'] === null) {
            throw new DomainException('Le récapitulatif est disponible après la finale.');
        }

        $stmt = $this->db->prepare("
            WITH run AS (
                SELECT id, tournament_id
                FROM tournament_runs
                WHERE id = :run_id AND user_id = :user_id
            ),
            entrants AS (
                SELECT b.dish_1_id AS dish_id
                FROM battles b
                JOIN run r ON r.tournament_id = b.tournament_id
                WHERE b.phase = 'quarter' AND b.position BETWEEN 1 AND 4

                UNION

                SELECT b.dish_2_id AS dish_id
                FROM battles b
                JOIN run r ON r.tournament_id = b.tournament_id
                WHERE b.phase = 'quarter' AND b.position BETWEEN 1 AND 4
            ),
            points AS (
                SELECT m.dish_winner_id AS dish_id, COUNT(*) AS score
                FROM tournament_run_matches m
                JOIN run r ON r.id = m.run_id
                WHERE m.dish_winner_id IS NOT NULL
                GROUP BY m.dish_winner_id
            )
            SELECT d.id, d.name, d.picture, COALESCE(p.score, 0) AS score
            FROM entrants e
            JOIN dishes d ON d.id = e.dish_id
            LEFT JOIN points p ON p.dish_id = d.id
            ORDER BY score DESC, d.name
        ");
        $stmt->execute(['run_id' => $runId, 'user_id' => $userId]);

        return array_map(
            static function (array $row): array {
                $row['id'] = (int) $row['id'];
                $row['score'] = (int) $row['score'];
                return $row;
            },
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    private function resolvePair(int $tournamentId, int $runId, string $slot): array
    {
        $quarterPositions = ['Q1' => 1, 'Q2' => 2, 'Q3' => 3, 'Q4' => 4];

        if (isset($quarterPositions[$slot])) {
            $stmt = $this->db->prepare("
                SELECT dish_1_id, dish_2_id
                FROM battles
                WHERE tournament_id = :tournament_id
                  AND phase = 'quarter'
                  AND position = :position
            ");
            $stmt->execute([
                'tournament_id' => $tournamentId,
                'position' => $quarterPositions[$slot],
            ]);
            $pair = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pair) {
                throw new RuntimeException('Le quart de finale est introuvable.');
            }

            return [(int) $pair['dish_1_id'], (int) $pair['dish_2_id']];
        }

        $semifinalWinners = [
            'D1' => ['Q1', 'Q2'],
            'D2' => ['Q3', 'Q4'],
            'F' => ['D1', 'D2'],
        ];

        return [
            $this->winnerFor($runId, $semifinalWinners[$slot][0]),
            $this->winnerFor($runId, $semifinalWinners[$slot][1]),
        ];
    }

    private function winnerFor(int $runId, string $slot): int
    {
        $stmt = $this->db->prepare("
            SELECT dish_winner_id
            FROM tournament_run_matches
            WHERE run_id = :run_id AND slot = :slot
        ");
        $stmt->execute(['run_id' => $runId, 'slot' => $slot]);
        $winnerId = $stmt->fetchColumn();

        if ($winnerId === false || $winnerId === null) {
            throw new DomainException('Le duel précédent n’est pas encore terminé.');
        }

        return (int) $winnerId;
    }

    private function nextSlot(int $runId): ?string
    {
        $stmt = $this->db->prepare("
            SELECT slot FROM tournament_run_matches WHERE run_id = :run_id
        ");
        $stmt->execute(['run_id' => $runId]);
        $played = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));

        foreach (self::SLOTS as $slot) {
            if (!isset($played[$slot])) {
                return $slot;
            }
        }

        return null;
    }

    private function ownedRun(int $runId, int $userId, bool $lock = false): array
    {
        $sql = "
            SELECT id, tournament_id, finished_at
            FROM tournament_runs
            WHERE id = :run_id AND user_id = :user_id
        ";

        if ($lock) {
            $sql .= ' FOR UPDATE';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['run_id' => $runId, 'user_id' => $userId]);
        $run = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$run) {
            throw new DomainException('Participation introuvable.');
        }

        return $run;
    }

    private function loadDishes(array $dishIds): array
    {
        $stmt = $this->db->prepare("
            SELECT d.id, d.name, d.description, d.picture, d.price,
                   e.name AS restaurant, e.city
            FROM dishes d
            LEFT JOIN establishments e ON e.id = d.establishment_id
            WHERE d.id IN (?, ?)
        ");
        $stmt->execute($dishIds);

        $byId = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $dish) {
            $byId[(int) $dish['id']] = $dish;
        }

        return [$byId[$dishIds[0]], $byId[$dishIds[1]]];
    }

    private function validateSlot(string $slot): void
    {
        if (!in_array($slot, self::SLOTS, true)) {
            throw new InvalidArgumentException('Étape de tournoi invalide.');
        }
    }

    public function nextSlotForRun(int $runId, int $userId): ?string
    {
        $this->ownedRun($runId, $userId);
        return $this->nextSlot($runId);
    }
}
