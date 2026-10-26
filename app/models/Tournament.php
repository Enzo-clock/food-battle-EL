<?php
// filepath: app/models/Tournament.php

class Tournament
{
    public function __construct(private PDO $db)
    {
    }

    /** Enregistre un tournoi et retourne son identifiant. */
    public function create(string $name, string $startDate, int $creatorId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO tournaments (name, start_date, creator_id)
             VALUES (:name, :start_date, :creator_id)
             RETURNING id'
        );

        $stmt->execute([
            'name' => $name,
            'start_date' => $startDate,
            'creator_id' => $creatorId,
        ]);

        return (int) $stmt->fetchColumn();
    }

    /** Recherche un tournoi par son identifiant. */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM tournaments WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        $tournament = $stmt->fetch(PDO::FETCH_ASSOC);

        return $tournament ?: null;
    }

    /** Récupère tous les tournois, du plus récent au plus ancien. */
    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM tournaments ORDER BY id DESC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée un tournoi et ses quatre battles de quart de finale.
     * Les identifiants de battles sont traités dans l'ordre reçu.
     */
    public function createWithQuarterfinals(
        string $name,
        string $startDate,
        int $creatorId,
        string $category,
        array $battleIds
    ): int {
        $battleIds = array_map('intval', $battleIds);

        if (
            count($battleIds) !== 4
            || count(array_unique($battleIds)) !== 4
        ) {
            throw new InvalidArgumentException('Il faut quatre battles différentes.');
        }

        $start = DateTimeImmutable::createFromFormat('!Y-m-d', $startDate);

        if (!$start || $start->format('Y-m-d') !== $startDate) {
            throw new InvalidArgumentException('La date de début est invalide.');
        }

        $placeholders = implode(',', array_fill(0, 4, '?'));

        $this->db->beginTransaction();

        try {
            // Re-vérifie l'appartenance, la disponibilité et la catégorie,
            // puis verrouille les battles sélectionnées pendant la transaction.
            $stmt = $this->db->prepare("
                SELECT b.id, b.dish_1_id, b.dish_2_id
                FROM battles b
                JOIN dishes d1 ON d1.id = b.dish_1_id
                JOIN dishes d2 ON d2.id = b.dish_2_id
                WHERE b.id IN ($placeholders)
                  AND b.creator_id = ?
                  AND b.tournament_id IS NULL
                  AND EXISTS (
                      SELECT 1
                      FROM dish_categories dc
                      JOIN categories c ON c.id = dc.category_id
                      WHERE dc.dish_id = d1.id AND c.name = ?
                  )
                  AND EXISTS (
                      SELECT 1
                      FROM dish_categories dc
                      JOIN categories c ON c.id = dc.category_id
                      WHERE dc.dish_id = d2.id AND c.name = ?
                  )
                FOR UPDATE OF b
            ");

            $stmt->execute([
                ...$battleIds,
                $creatorId,
                $category,
                $category,
            ]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $battlesById = [];

            foreach ($rows as $row) {
                $battlesById[(int) $row['id']] = $row;
            }

            if (count($battlesById) !== 4) {
                throw new InvalidArgumentException(
                    'Un battle n’est plus disponible ou ne correspond pas à la catégorie.'
                );
            }
            $dishIds = [];

            foreach ($battlesById as $battle) {
                $dishIds[] = (int) $battle['dish_1_id'];
                $dishIds[] = (int) $battle['dish_2_id'];
            }

            if (count(array_unique($dishIds)) !== 8) {
                throw new InvalidArgumentException(
                    'Un même plat ne peut pas apparaître dans plusieurs battles du tournoi.'
                );
            }

            $stmt = $this->db->prepare("
                INSERT INTO tournaments (name, start_date, creator_id)
                VALUES (:name, :start_date, :creator_id)
                RETURNING id
            ");
            $stmt->execute([
                'name' => trim($name),
                'start_date' => $startDate,
                'creator_id' => $creatorId,
            ]);

            $tournamentId = (int) $stmt->fetchColumn();

            $insertBattle = $this->db->prepare("
                INSERT INTO battles (
                    creator_id,
                    tournament_id,
                    phase,
                    position,
                    battle_date,
                    dish_1_id,
                    dish_2_id
                )
                VALUES (?, ?, 'quarter', ?, ?, ?, ?)
            ");

            foreach ($battleIds as $index => $battleId) {
                $source = $battlesById[$battleId];
                $date = $start->modify("+$index days")->format('Y-m-d');

                $insertBattle->execute([
                    $creatorId,
                    $tournamentId,
                    $index + 1,
                    $date,
                    (int) $source['dish_1_id'],
                    (int) $source['dish_2_id'],
                ]);
            }

            $this->db->commit();

            return $tournamentId;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $error;
        }
    }
}