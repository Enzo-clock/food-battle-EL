<?php
// filepath: app/models/Dashboard.php

/**
 * Lecture des ressources créées par un utilisateur (page /dashboard).
 *
 * Chaque méthode filtre sur creator_id : un utilisateur ne voit jamais
 * les contenus des autres. Toutes les requêtes sont préparées.
 */
class Dashboard
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Nombre de ressources par type, en une seule requête (pour les compteurs des onglets).
     * ['duels' => n, 'tournois' => n, 'plats' => n, 'restaurants' => n, 'categories' => n]
     */
    public function counts(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                (SELECT COUNT(*) FROM battles WHERE creator_id = :u1 AND tournament_id IS NULL) AS duels,
                (SELECT COUNT(*) FROM tournaments WHERE creator_id = :u2) AS tournois,
                (SELECT COUNT(*) FROM dishes WHERE creator_id = :u3) AS plats,
                (SELECT COUNT(*) FROM establishments WHERE creator_id = :u4) AS restaurants,
                (SELECT COUNT(*) FROM categories WHERE creator_id = :u5) AS categories'
        );
        $stmt->execute(['u1' => $userId, 'u2' => $userId, 'u3' => $userId, 'u4' => $userId, 'u5' => $userId]);

        return array_map('intval', $stmt->fetch());
    }

    /**
     * Duels libres (hors tournoi) créés par l'utilisateur, du plus récent au plus ancien,
     * avec les votes de chaque côté et la catégorie commune aux deux plats.
     */
    public function duels(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.id, b.battle_date,
                    d1.id AS a_id, d1.name AS a_name, d1.picture AS a_picture, e1.name AS a_restaurant,
                    d2.id AS b_id, d2.name AS b_name, d2.picture AS b_picture, e2.name AS b_restaurant,
                    COUNT(v.user_id) FILTER (WHERE v.dish_choice_id = b.dish_1_id) AS a_votes,
                    COUNT(v.user_id) FILTER (WHERE v.dish_choice_id = b.dish_2_id) AS b_votes,
                    (SELECT c.slug
                     FROM dish_categories x
                     JOIN dish_categories y ON y.category_id = x.category_id AND y.dish_id = b.dish_2_id
                     JOIN categories c ON c.id = x.category_id
                     WHERE x.dish_id = b.dish_1_id
                     ORDER BY c.id
                     LIMIT 1) AS category
             FROM battles b
             JOIN dishes d1 ON d1.id = b.dish_1_id
             JOIN dishes d2 ON d2.id = b.dish_2_id
             LEFT JOIN establishments e1 ON e1.id = d1.establishment_id
             LEFT JOIN establishments e2 ON e2.id = d2.establishment_id
             LEFT JOIN user_battle_votes v ON v.battle_id = b.id
             WHERE b.creator_id = :user AND b.tournament_id IS NULL
             GROUP BY b.id, d1.id, d2.id, e1.name, e2.name
             ORDER BY b.id DESC"
        );
        $stmt->execute(['user' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Tournois créés par l'utilisateur, avec leurs affiches de quarts de finale,
     * l'avancement (matchs départagés), les votes reçus et le vainqueur éventuel.
     */
    public function tournaments(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT t.id, t.name, t.start_date,
                    COUNT(b.id) AS battles,
                    COUNT(b.dish_winner_id) AS decided,
                    (SELECT COUNT(*) FROM user_battle_votes v
                     JOIN battles vb ON vb.id = v.battle_id
                     WHERE vb.tournament_id = t.id) AS votes,
                    (SELECT w.name FROM battles f
                     JOIN dishes w ON w.id = f.dish_winner_id
                     WHERE f.tournament_id = t.id AND f.phase = 'final') AS winner,
                    (SELECT c.slug FROM battles q
                     JOIN dish_categories dc ON dc.dish_id = q.dish_1_id
                     JOIN categories c ON c.id = dc.category_id
                     WHERE q.tournament_id = t.id
                     ORDER BY q.position, c.id
                     LIMIT 1) AS category,
                    COALESCE((
                        SELECT json_agg(json_build_object('a', qa.name, 'b', qb.name) ORDER BY q.position)
                        FROM battles q
                        JOIN dishes qa ON qa.id = q.dish_1_id
                        JOIN dishes qb ON qb.id = q.dish_2_id
                        WHERE q.tournament_id = t.id AND q.phase = 'quarter'
                    ), '[]')::text AS quarters
             FROM tournaments t
             LEFT JOIN battles b ON b.tournament_id = t.id
             WHERE t.creator_id = :user
             GROUP BY t.id
             ORDER BY t.start_date DESC, t.id DESC"
        );
        $stmt->execute(['user' => $userId]);

        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['quarters'] = json_decode($row['quarters'], true) ?: [];
        }
        return $rows;
    }

    /** Plats créés par l'utilisateur, avec restaurant, catégories, votes gagnés et duels joués. */
    public function dishes(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT d.id, d.name, d.description, d.picture, d.price,
                    e.name AS restaurant, e.city,
                    COALESCE((
                        SELECT json_agg(c.slug ORDER BY c.id)
                        FROM dish_categories dc
                        JOIN categories c ON c.id = dc.category_id
                        WHERE dc.dish_id = d.id
                    ), '[]')::text AS categories,
                    (SELECT COUNT(*) FROM user_battle_votes v WHERE v.dish_choice_id = d.id) AS votes,
                    (SELECT COUNT(*) FROM battles b WHERE d.id IN (b.dish_1_id, b.dish_2_id)) AS duels
             FROM dishes d
             LEFT JOIN establishments e ON e.id = d.establishment_id
             WHERE d.creator_id = :user
             ORDER BY d.id DESC"
        );
        $stmt->execute(['user' => $userId]);

        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['categories'] = json_decode($row['categories'], true) ?: [];
        }
        return $rows;
    }

    /** Restaurants créés par l'utilisateur, avec leur nombre de plats. */
    public function establishments(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT e.id, e.name, e.description, e.city, e.address,
                    COUNT(d.id) AS dishes
             FROM establishments e
             LEFT JOIN dishes d ON d.establishment_id = e.id
             WHERE e.creator_id = :user
             GROUP BY e.id
             ORDER BY e.id DESC'
        );
        $stmt->execute(['user' => $userId]);
        return $stmt->fetchAll();
    }

    /** Catégories créées par l'utilisateur, avec leur nombre de plats. */
    public function categories(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.name, c.slug, c.color, c.picture, c.created_at,
                    COUNT(dc.dish_id) AS dishes
             FROM categories c
             LEFT JOIN dish_categories dc ON dc.category_id = c.id
             WHERE c.creator_id = :user
             GROUP BY c.id
             ORDER BY c.created_at DESC, c.id DESC'
        );
        $stmt->execute(['user' => $userId]);
        return $stmt->fetchAll();
    }
}
