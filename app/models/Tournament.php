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
     * Duels libres de l'utilisateur, utilisables comme quarts de finale.
     * Pour chaque duel : les deux plats (photo, restaurant), les votes reçus
     * et les catégories communes aux deux plats (« shared », liste de slugs).
     */
    public function availableBattles(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.id,
                    d1.id AS a_id, d1.name AS a_name, d1.picture AS a_picture, e1.name AS a_restaurant,
                    d2.id AS b_id, d2.name AS b_name, d2.picture AS b_picture, e2.name AS b_restaurant,
                    (SELECT COUNT(*) FROM user_battle_votes v WHERE v.battle_id = b.id) AS votes,
                    COALESCE((
                        SELECT json_agg(c.slug ORDER BY c.id)
                        FROM dish_categories x
                        JOIN dish_categories y ON y.category_id = x.category_id AND y.dish_id = b.dish_2_id
                        JOIN categories c ON c.id = x.category_id
                        WHERE x.dish_id = b.dish_1_id
                    ), '[]')::text AS shared
             FROM battles b
             JOIN active_dishes d1 ON d1.id = b.dish_1_id -- un duel avec un plat archivé
             JOIN active_dishes d2 ON d2.id = b.dish_2_id -- ne peut plus ouvrir un tournoi
             LEFT JOIN establishments e1 ON e1.id = d1.establishment_id
             LEFT JOIN establishments e2 ON e2.id = d2.establishment_id
             WHERE b.creator_id = :user AND b.tournament_id IS NULL
             ORDER BY b.id DESC"
        );
        $stmt->execute(['user' => $userId]);

        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['shared'] = json_decode($row['shared'], true) ?: [];
        }
        return $rows;
    }

    /**
     * Crée un tournoi et ses quatre battles de quart de finale.
     * Les identifiants de battles sont traités dans l'ordre reçu.
     */
    public function createWithQuarterfinals(
        string $name,
        string $startDate,
        int $creatorId,
        string $category, // slug de la catégorie (ex. « burger »)
        array $battleIds
    ): int {
        $battleIds = array_map('intval', $battleIds);

        if (
            count($battleIds) !== 4
            || count(array_unique($battleIds)) !== 4
        ) {
            throw new InvalidArgumentException('Il faut quatre duels différents.');
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
                JOIN active_dishes d1 ON d1.id = b.dish_1_id
                JOIN active_dishes d2 ON d2.id = b.dish_2_id
                WHERE b.id IN ($placeholders)
                  AND b.creator_id = ?
                  AND b.tournament_id IS NULL
                  AND EXISTS (
                      SELECT 1
                      FROM dish_categories dc
                      JOIN categories c ON c.id = dc.category_id
                      WHERE dc.dish_id = d1.id AND c.slug = ?
                  )
                  AND EXISTS (
                      SELECT 1
                      FROM dish_categories dc
                      JOIN categories c ON c.id = dc.category_id
                      WHERE dc.dish_id = d2.id AND c.slug = ?
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
                    'Un des duels choisis n’est plus disponible ou n’est pas de cette catégorie.'
                );
            }
            $dishIds = [];

            foreach ($battlesById as $battle) {
                $dishIds[] = (int) $battle['dish_1_id'];
                $dishIds[] = (int) $battle['dish_2_id'];
            }

            if (count(array_unique($dishIds)) !== 8) {
                throw new InvalidArgumentException(
                    'Un même plat ne peut pas apparaître dans plusieurs duels du tournoi.'
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
                    dish_1_id,
                    dish_2_id
                )
                VALUES (?, ?, 'quarter', ?, ?, ?)
            ");

            foreach ($battleIds as $index => $battleId) {
                $source = $battlesById[$battleId];

                $insertBattle->execute([
                    $creatorId,
                    $tournamentId,
                    $index + 1,
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

    /** Un tournoi avec ses votes (tous matchs confondus). */
    public function findWithStats(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*,
                    (SELECT COUNT(*) FROM user_battle_votes v
                     JOIN battles b ON b.id = v.battle_id
                     WHERE b.tournament_id = t.id) AS votes
             FROM tournaments t WHERE t.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function rename(int $id, string $name): void
    {
        $this->db->prepare('UPDATE tournaments SET name = ? WHERE id = ?')->execute([$name, $id]);
    }

    /**
     * Supprime un tournoi pas encore commencé et sans vote ; ses matchs partent avec lui
     * (battles.tournament_id en CASCADE). Les duels d'origine, copiés à la création, restent.
     * Les conditions sont dans la requête : retourne false si elles ne sont plus remplies.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM tournaments t
             WHERE t.id = ? AND t.start_date > CURRENT_DATE
               AND NOT EXISTS (SELECT 1 FROM user_battle_votes v
                               JOIN battles b ON b.id = v.battle_id
                               WHERE b.tournament_id = t.id)'
        );
        $stmt->execute([$id]);
        return $stmt->rowCount() === 1;
    }
}
