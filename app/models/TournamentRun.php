<?php

class TournamentRun
{
    public const SLOTS = ['Q1', 'Q2', 'Q3', 'Q4', 'D1', 'D2', 'F'];

    /** Libellés affichés : les codes Q1, D2, F restent internes (URL, base). */
    public const SLOT_LABELS = [
        'Q1' => 'Quart de finale 1 sur 4',
        'Q2' => 'Quart de finale 2 sur 4',
        'Q3' => 'Quart de finale 3 sur 4',
        'Q4' => 'Quart de finale 4 sur 4',
        'D1' => 'Demi-finale 1 sur 2',
        'D2' => 'Demi-finale 2 sur 2',
        'F'  => 'Finale',
    ];

    /** Où file le vainqueur de chaque match (phrase du résultat). */
    public const NEXT_ROUND = [
        'Q1' => 'en demi-finale', 'Q2' => 'en demi-finale', 'Q3' => 'en demi-finale', 'Q4' => 'en demi-finale',
        'D1' => 'en finale', 'D2' => 'en finale',
    ];

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

    /**
     * Nom du tournoi, catégorie de ses plats (image et couleur par défaut)
     * et sa place parmi les tournois du jour (« Tournoi 1 sur 3 »).
     */
    public function info(int $tournamentId): array
    {
        $stmt = $this->db->prepare("
            SELECT t.id, t.name,
                   (SELECT c.slug
                    FROM battles q
                    JOIN dish_categories dc ON dc.dish_id = q.dish_1_id
                    JOIN categories c ON c.id = dc.category_id
                    WHERE q.tournament_id = t.id
                    ORDER BY q.position, c.id
                    LIMIT 1) AS category
            FROM tournaments t
            WHERE t.id = :id
        ");
        $stmt->execute(['id' => $tournamentId]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['id' => $tournamentId, 'name' => 'Tournoi', 'category' => null];

        $daily = array_map('intval', array_column($this->dailyTournaments(), 'id'));
        $info['position'] = (int) array_search($tournamentId, $daily, true) + 1;
        $info['daily_total'] = count($daily);
        return $info;
    }

    /**
     * Le tableau du joueur, match par match : les deux plats (dès qu'ils sont connus) et son vainqueur.
     * Les quarts viennent du tournoi ; les demies et la finale, des vainqueurs qu'il a choisis.
     * [slot => ['a' => nom|null, 'b' => nom|null, 'winner' => nom|null]]
     */
    public function bracket(int $runId, int $tournamentId, int $userId): array
    {
        $this->ownedRun($runId, $userId);

        $stmt = $this->db->prepare("
            SELECT b.position, d1.name AS a, d2.name AS b
            FROM battles b
            JOIN dishes d1 ON d1.id = b.dish_1_id
            JOIN dishes d2 ON d2.id = b.dish_2_id
            WHERE b.tournament_id = :tournament_id AND b.phase = 'quarter'
            ORDER BY b.position
        ");
        $stmt->execute(['tournament_id' => $tournamentId]);
        $quarters = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $quarters['Q' . $row['position']] = $row;
        }

        $stmt = $this->db->prepare("
            SELECT m.slot, w.name AS winner
            FROM tournament_run_matches m
            LEFT JOIN dishes w ON w.id = m.dish_winner_id
            WHERE m.run_id = :run_id
        ");
        $stmt->execute(['run_id' => $runId]);
        $winners = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'winner', 'slot');

        $feeds = ['D1' => ['Q1', 'Q2'], 'D2' => ['Q3', 'Q4'], 'F' => ['D1', 'D2']];
        $bracket = [];
        foreach (self::SLOTS as $slot) {
            [$a, $b] = isset($feeds[$slot])
                ? [$winners[$feeds[$slot][0]] ?? null, $winners[$feeds[$slot][1]] ?? null]
                : [$quarters[$slot]['a'] ?? null, $quarters[$slot]['b'] ?? null];
            $bracket[$slot] = ['a' => $a, 'b' => $b, 'winner' => $winners[$slot] ?? null];
        }
        return $bracket;
    }

    /**
     * Combien de joueurs ont couronné le même champion dans ce tournoi (tous les jours confondus).
     * ['same' => n, 'total' => n] ; total compte la partie du joueur.
     */
    public function championShare(int $tournamentId, int $dishId): array
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FILTER (WHERE m.dish_winner_id = :dish_id) AS same, COUNT(*) AS total
            FROM tournament_run_matches m
            JOIN tournament_runs r ON r.id = m.run_id
            WHERE r.tournament_id = :tournament_id AND m.slot = 'F'
        ");
        $stmt->execute(['dish_id' => $dishId, 'tournament_id' => $tournamentId]);
        return array_map('intval', $stmt->fetch(PDO::FETCH_ASSOC));
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
