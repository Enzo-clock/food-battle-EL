<?php
// filepath: foodbattle-starter/app/models/Battle.php

/**
 * Accès aux données des battles (duels).
 *
 * Un « duel libre » est une ligne de `battles` sans tournoi (tournament_id NULL).
 * Une seule battle libre est réutilisée par paire de plats : tous les votes sur
 * cette paire s'additionnent, ce qui rend les pourcentages significatifs.
 * Les matchs de tournoi réutiliseront vote() et results() tels quels.
 */
class Battle
{
    public function __construct(private PDO $db)
    {
    }

    /** Plats d'une catégorie, avec leur établissement et leurs votes gagnés en duel. */
    public function dishesInCategory(string $category): array
    {
        $stmt = $this->db->prepare(
            "SELECT d.id, d.name, d.description, d.picture, d.price,
                    e.name AS restaurant, e.city,
                    (SELECT COUNT(*) FROM user_battle_votes v WHERE v.dish_choice_id = d.id) AS votes
             FROM dishes d
             JOIN dish_categories dc ON dc.dish_id = d.id
             JOIN categories c ON c.id = dc.category_id
             LEFT JOIN establishments e ON e.id = d.establishment_id
             WHERE c.name = ?
             ORDER BY d.id"
        );
        $stmt->execute([$category]);

        $dishes = [];
        foreach ($stmt->fetchAll() as $row) {
            $dishes[(int) $row['id']] = $row;
        }
        return $dishes;
    }

    /**
     * Tire au hasard deux plats de la catégorie que l'utilisateur n'a pas encore départagés.
     * Retourne [platA, platB], ou null s'il ne reste aucune paire à voter.
     */
    public function randomDuel(string $category, ?int $userId, array $skipPair = []): ?array
    {
        $dishes = $this->dishesInCategory($category);
        $ids = array_keys($dishes);
        $alreadyVoted = $userId ? $this->votedPairs($userId) : [];

        $pairs = [];
        foreach ($ids as $i => $a) {
            foreach (array_slice($ids, $i + 1) as $b) {
                $key = self::pairKey($a, $b);
                if (!isset($alreadyVoted[$key])) {
                    $pairs[$key] = [$a, $b];
                }
            }
        }

        // Évite de reproposer immédiatement le duel qu'on vient de passer.
        if (count($pairs) > 1 && $skipPair) {
            unset($pairs[self::pairKey(...$skipPair)]);
        }
        if (!$pairs) {
            return null;
        }

        $pair = array_values($pairs)[random_int(0, count($pairs) - 1)];
        if (random_int(0, 1) === 1) {
            $pair = array_reverse($pair); // gauche/droite aléatoire : évite le biais de position
        }
        return [$dishes[$pair[0]], $dishes[$pair[1]]];
    }

    /** Battle libre de la paire, créée au premier vote. */
    public function findOrCreateFree(int $dishA, int $dishB, int $creatorId): int
    {
        [$low, $high] = [min($dishA, $dishB), max($dishA, $dishB)];

        $stmt = $this->db->prepare(
            'SELECT id FROM battles
             WHERE tournament_id IS NULL AND dish_1_id = ? AND dish_2_id = ?
             ORDER BY id LIMIT 1'
        );
        $stmt->execute([$low, $high]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO battles (creator_id, battle_date, dish_1_id, dish_2_id)
             VALUES (?, CURRENT_DATE, ?, ?)
             RETURNING id'
        );
        $stmt->execute([$creatorId, $low, $high]);
        return (int) $stmt->fetchColumn();
    }

    /** Enregistre le vote. Retourne false si l'utilisateur avait déjà voté sur cette battle. */
    public function vote(int $userId, int $battleId, int $dishId): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO user_battle_votes (user_id, battle_id, dish_choice_id)
             VALUES (?, ?, ?)
             ON CONFLICT (user_id, battle_id) DO NOTHING'
        );
        $stmt->execute([$userId, $battleId, $dishId]);
        return $stmt->rowCount() === 1;
    }

    /** Votes par plat sur une battle : [dish_id => nombre]. */
    public function results(int $battleId): array
    {
        $stmt = $this->db->prepare(
            'SELECT dish_choice_id, COUNT(*) AS n
             FROM user_battle_votes
             WHERE battle_id = ?
             GROUP BY dish_choice_id'
        );
        $stmt->execute([$battleId]);

        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[(int) $row['dish_choice_id']] = (int) $row['n'];
        }
        return $counts;
    }

    /** Paires de plats déjà votées par l'utilisateur en duel libre. */
    private function votedPairs(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT b.dish_1_id, b.dish_2_id
             FROM user_battle_votes v
             JOIN battles b ON b.id = v.battle_id
             WHERE v.user_id = ? AND b.tournament_id IS NULL'
        );
        $stmt->execute([$userId]);

        $pairs = [];
        foreach ($stmt->fetchAll() as $row) {
            $pairs[self::pairKey((int) $row['dish_1_id'], (int) $row['dish_2_id'])] = true;
        }
        return $pairs;
    }

    private static function pairKey(int $a, int $b): string
    {
        return min($a, $b) . '-' . max($a, $b);
    }
}
