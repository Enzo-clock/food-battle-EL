<?php
// filepath: app/models/Dashboard.php

/**
 * Listes de ressources pour le dashboard (/dashboard) et le back-office (/admin).
 *
 * $filter['user'] : id du créateur — le dashboard ne montre que les contenus de l'utilisateur ;
 *                   null pour le back-office, qui voit tout.
 * $filter['q']    : recherche sur le nom (back-office).
 *
 * Chaque ligne contient les compteurs dont les règles de suppression ont besoin (rules.php) :
 * battles (plat), dishes (restaurant, catégorie), votes (duel, tournoi).
 * Toutes les requêtes sont préparées.
 */
class Dashboard
{
    public function __construct(private PDO $db)
    {
    }

    /** Nombre de ressources par type, en une seule requête (compteurs des onglets). */
    public function counts(?int $userId): array
    {
        $by = $userId === null ? 'TRUE' : 'creator_id = :u';
        $sql = "SELECT
                (SELECT COUNT(*) FROM battles WHERE $by AND tournament_id IS NULL) AS duels,
                (SELECT COUNT(*) FROM tournaments WHERE $by) AS tournois,
                (SELECT COUNT(*) FROM dishes WHERE $by) AS plats,
                (SELECT COUNT(*) FROM establishments WHERE $by) AS restaurants,
                (SELECT COUNT(*) FROM categories WHERE $by) AS categories";
        // Un paramètre nommé ne peut servir qu'une fois avec PDO_pgsql en mode natif : on les numérote.
        $sql = $userId === null ? $sql : $this->numbered($sql, ':u');
        $stmt = $this->db->prepare($sql);
        $stmt->execute($userId === null ? [] : array_fill_keys($this->names(':u', 5), $userId));

        return array_map('intval', $stmt->fetch());
    }

    /** Duels libres (hors tournoi), avec votes de chaque côté et catégorie commune. */
    public function duels(array $filter): array
    {
        $params = [];
        $where = $this->where($filter, 'b.creator_id', "d1.name || ' ' || d2.name", $params);
        $stmt = $this->db->prepare(
            "SELECT b.id, b.battle_date, b.creator_id, u.username AS creator,
                    d1.id AS a_id, d1.name AS a_name, d1.picture AS a_picture, e1.name AS a_restaurant,
                    d2.id AS b_id, d2.name AS b_name, d2.picture AS b_picture, e2.name AS b_restaurant,
                    COUNT(v.user_id) FILTER (WHERE v.dish_choice_id = b.dish_1_id) AS a_votes,
                    COUNT(v.user_id) FILTER (WHERE v.dish_choice_id = b.dish_2_id) AS b_votes,
                    COUNT(v.user_id) AS votes,
                    (SELECT c.slug
                     FROM dish_categories x
                     JOIN dish_categories y ON y.category_id = x.category_id AND y.dish_id = b.dish_2_id
                     JOIN categories c ON c.id = x.category_id
                     WHERE x.dish_id = b.dish_1_id
                     ORDER BY c.id
                     LIMIT 1) AS category
             FROM battles b
             JOIN users u ON u.id = b.creator_id
             JOIN dishes d1 ON d1.id = b.dish_1_id
             JOIN dishes d2 ON d2.id = b.dish_2_id
             LEFT JOIN establishments e1 ON e1.id = d1.establishment_id
             LEFT JOIN establishments e2 ON e2.id = d2.establishment_id
             LEFT JOIN user_battle_votes v ON v.battle_id = b.id
             WHERE b.tournament_id IS NULL AND $where
             GROUP BY b.id, u.username, d1.id, d2.id, e1.name, e2.name
             ORDER BY b.id DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Tournois avec affiches des quarts, avancement, votes et vainqueur éventuel. */
    public function tournaments(array $filter): array
    {
        $params = [];
        $where = $this->where($filter, 't.creator_id', 't.name', $params);
        $stmt = $this->db->prepare(
            "SELECT t.id, t.name, t.start_date, t.creator_id, u.username AS creator,
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
             JOIN users u ON u.id = t.creator_id
             LEFT JOIN battles b ON b.tournament_id = t.id
             WHERE $where
             GROUP BY t.id, u.username
             ORDER BY t.start_date DESC, t.id DESC"
        );
        $stmt->execute($params);

        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['quarters'] = json_decode($row['quarters'], true) ?: [];
        }
        return $rows;
    }

    /**
     * Plats (archivés compris, après les autres), avec restaurant, catégories,
     * votes gagnés et nombre de duels ou matchs où ils figurent (« en jeu »).
     */
    public function dishes(array $filter): array
    {
        $params = [];
        $where = $this->where($filter, 'd.creator_id', 'd.name', $params);
        $stmt = $this->db->prepare(
            "SELECT d.id, d.name, d.description, d.picture, d.price, d.archived_at, d.creator_id,
                    d.status, d.rejection_reason,
                    u.username AS creator, e.name AS restaurant, e.city,
                    COALESCE((
                        SELECT json_agg(c.slug ORDER BY c.id)
                        FROM dish_categories dc
                        JOIN categories c ON c.id = dc.category_id
                        WHERE dc.dish_id = d.id
                    ), '[]')::text AS categories,
                    (SELECT COUNT(*) FROM user_battle_votes v WHERE v.dish_choice_id = d.id) AS votes,
                    (SELECT COUNT(*) FROM battles b WHERE d.id IN (b.dish_1_id, b.dish_2_id))
                    + (SELECT COUNT(*) FROM tournament_dishes td WHERE td.dish_id = d.id) AS battles
             FROM dishes d
             JOIN users u ON u.id = d.creator_id
             LEFT JOIN establishments e ON e.id = d.establishment_id
             WHERE $where
             ORDER BY d.status = 'pending' DESC, d.archived_at IS NOT NULL, d.id DESC"
        );
        $stmt->execute($params);

        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['categories'] = json_decode($row['categories'], true) ?: [];
        }
        return $rows;
    }

    /** Restaurants, avec leur nombre de plats (archivés compris : ils y restent rattachés). */
    public function establishments(array $filter): array
    {
        $params = [];
        $where = $this->where($filter, 'e.creator_id', 'e.name', $params);
        $stmt = $this->db->prepare(
            "SELECT e.id, e.name, e.description, e.city, e.address, e.creator_id, u.username AS creator,
                    e.status, e.rejection_reason, COUNT(d.id) AS dishes
             FROM establishments e
             JOIN users u ON u.id = e.creator_id
             LEFT JOIN dishes d ON d.establishment_id = e.id
             WHERE $where
             GROUP BY e.id, u.username
             ORDER BY e.status = 'pending' DESC, e.id DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Catégories, avec leur nombre de plats (archivés compris) et leur statut ; les propositions en attente d'abord. */
    public function categories(array $filter): array
    {
        $params = [];
        $where = $this->where($filter, 'c.creator_id', 'c.name', $params);
        $stmt = $this->db->prepare(
            "SELECT c.id, c.name, c.slug, c.color, c.picture, c.created_at, c.creator_id, u.username AS creator,
                    c.status, c.rejection_reason, COUNT(dc.dish_id) AS dishes
             FROM categories c
             JOIN users u ON u.id = c.creator_id
             LEFT JOIN dish_categories dc ON dc.category_id = c.id
             WHERE $where
             GROUP BY c.id, u.username
             ORDER BY c.status = 'pending' DESC, c.created_at DESC, c.id DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Comptes utilisateurs (back-office), avec leur nombre de contributions. Le compte technique est exclu. */
    public function users(string $q = ''): array
    {
        $params = [];
        $where = "u.password_hash NOT LIKE '!%'"; // compte « seed » : connexion désactivée
        if ($q !== '') {
            $where .= ' AND (u.username ILIKE :q1 OR u.email ILIKE :q2)';
            $params['q1'] = $params['q2'] = '%' . addcslashes($q, '%_\\') . '%';
        }

        $stmt = $this->db->prepare(
            "SELECT u.id, u.username, u.email, u.role, u.created_at,
                    (SELECT COUNT(*) FROM dishes WHERE creator_id = u.id)
                    + (SELECT COUNT(*) FROM establishments WHERE creator_id = u.id)
                    + (SELECT COUNT(*) FROM categories WHERE creator_id = u.id)
                    + (SELECT COUNT(*) FROM battles WHERE creator_id = u.id AND tournament_id IS NULL)
                    + (SELECT COUNT(*) FROM tournaments WHERE creator_id = u.id) AS contributions
             FROM users u
             WHERE $where
             ORDER BY u.role = 'admin' DESC, u.username"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Clause WHERE commune : filtre par créateur (dashboard) et recherche sur le nom (back-office). */
    private function where(array $filter, string $creatorColumn, string $nameExpression, array &$params): string
    {
        $clauses = ['TRUE'];
        if (($filter['user'] ?? null) !== null) {
            $clauses[] = "$creatorColumn = :user";
            $params['user'] = (int) $filter['user'];
        }
        if (($filter['q'] ?? '') !== '') {
            $clauses[] = "($nameExpression) ILIKE :q";
            $params['q'] = '%' . addcslashes($filter['q'], '%_\\') . '%';
        }
        return implode(' AND ', $clauses);
    }

    /** « :u … :u … » → « :u1 … :u2 … » */
    private function numbered(string $sql, string $name): string
    {
        $i = 0;
        return preg_replace_callback('/' . preg_quote($name, '/') . '\b/', static function () use (&$i, $name): string {
            return $name . ++$i;
        }, $sql);
    }

    /** [':u'] × 5 → ['u1', …, 'u5'] */
    private function names(string $name, int $count): array
    {
        return array_map(static fn(int $i): string => ltrim($name, ':') . $i, range(1, $count));
    }
}
