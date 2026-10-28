<?php
// filepath: app/models/Ranking.php

/**
 * Classements par catégorie, calculés à partir des parties de tournoi terminées.
 *
 * Score d'un plat dans une partie = nombre de matchs qu'il y a gagnés :
 *   0 = éliminé en quart, 1 = demi-finaliste, 2 = finaliste, 3 = champion.
 * Score d'un plat dans un tournoi = moyenne de ses scores sur toutes les parties terminées de ce tournoi.
 * Score final = moyenne de ses scores par tournoi : chaque tournoi compte autant que les autres,
 * même si l'un a été joué beaucoup plus souvent.
 *
 * En complément (sans effet sur le rang) : le pourcentage moyen des votes obtenus par le plat
 * dans toutes les battles où il figure (duels libres et matchs de tournoi).
 */
class Ranking
{
    /** Score maximal d'une partie : 3 matchs gagnés (quart, demie, finale). */
    public const MAX_SCORE = 3;

    public function __construct(private PDO $db)
    {
    }

    /**
     * @return array<int, array{
     *     id: int, name: string, slug: string, color: string,
     *     tournaments: int, runs: int,
     *     dishes: array<int, array{
     *         id: int, name: string, picture: ?string, restaurant: ?string, city: ?string,
     *         average_score: float, tournament_count: int, participation_count: int,
     *         vote_percent: ?int, voted_battles: int, rank: int
     *     }>
     * }>
     */
    public function byCategory(): array
    {
        $stmt = $this->db->query("
            WITH completed_runs AS (
                SELECT id AS run_id, tournament_id
                FROM tournament_runs
                WHERE finished_at IS NOT NULL
            ),
            category_tournament_dishes AS (
                SELECT c.id AS category_id, td.tournament_id, td.dish_id
                FROM categories c
                JOIN dish_categories dc ON dc.category_id = c.id
                JOIN tournament_dishes td ON td.dish_id = dc.dish_id
                WHERE c.status = 'approved'
            ),
            per_run_scores AS (
                SELECT
                    ctd.category_id, cr.tournament_id, cr.run_id, ctd.dish_id,
                    COUNT(m.slot)::int AS score
                FROM category_tournament_dishes ctd
                JOIN completed_runs cr ON cr.tournament_id = ctd.tournament_id
                LEFT JOIN tournament_run_matches m
                    ON m.run_id = cr.run_id AND m.dish_winner_id = ctd.dish_id
                GROUP BY ctd.category_id, cr.tournament_id, cr.run_id, ctd.dish_id
            ),
            per_tournament_scores AS (
                SELECT
                    category_id, tournament_id, dish_id,
                    AVG(score) AS tournament_average,
                    COUNT(*)::int AS participation_count
                FROM per_run_scores
                GROUP BY category_id, tournament_id, dish_id
            ),
            per_dish_scores AS (
                SELECT
                    category_id, dish_id,
                    AVG(tournament_average) AS average_score,
                    COUNT(*)::int AS tournament_count,
                    SUM(participation_count)::int AS participation_count
                FROM per_tournament_scores
                GROUP BY category_id, dish_id
            ),
            category_totals AS (
                SELECT
                    category_id,
                    COUNT(DISTINCT tournament_id)::int AS tournaments,
                    COUNT(DISTINCT run_id)::int AS runs
                FROM per_run_scores
                GROUP BY category_id
            ),
            -- Tous les votes entre deux plats : duels libres (un vote par joueur) et matchs de tournoi
            -- (chaque partie jouée est un vote). Un même match d'un même tournoi = une seule battle.
            battle_votes AS (
                SELECT 'duel-' || b.id AS battle_key, b.dish_1_id, b.dish_2_id, v.dish_choice_id AS choice
                FROM battles b
                JOIN user_battle_votes v ON v.battle_id = b.id
                UNION ALL
                SELECT 'match-' || tr.tournament_id || '-' || m.slot || '-'
                           || LEAST(m.dish_1_id, m.dish_2_id) || '-' || GREATEST(m.dish_1_id, m.dish_2_id),
                       m.dish_1_id, m.dish_2_id, m.dish_winner_id
                FROM tournament_run_matches m
                JOIN tournament_runs tr ON tr.id = m.run_id
                WHERE m.dish_winner_id IS NOT NULL
            ),
            -- Part des votes obtenue par chaque plat dans chaque battle votée
            battle_shares AS (
                SELECT
                    bv.battle_key, side.dish_id,
                    COUNT(*) FILTER (WHERE bv.choice = side.dish_id)::numeric / COUNT(*) AS share
                FROM battle_votes bv
                CROSS JOIN LATERAL (VALUES (bv.dish_1_id), (bv.dish_2_id)) AS side (dish_id)
                GROUP BY bv.battle_key, side.dish_id
            ),
            -- Moyenne de ces parts : chaque battle compte autant, quel que soit son nombre de votes
            dish_vote_shares AS (
                SELECT dish_id, AVG(share) * 100 AS vote_percent, COUNT(*)::int AS voted_battles
                FROM battle_shares
                GROUP BY dish_id
            )
            SELECT
                c.id AS category_id, c.name AS category_name, c.slug AS category_slug, c.color AS category_color,
                ct.tournaments AS category_tournaments, ct.runs AS category_runs,
                d.id AS dish_id, d.name AS dish_name, d.picture AS dish_picture,
                e.name AS restaurant, e.city,
                pds.average_score, pds.tournament_count, pds.participation_count,
                dvs.vote_percent, COALESCE(dvs.voted_battles, 0) AS voted_battles
            FROM per_dish_scores pds
            JOIN categories c ON c.id = pds.category_id
            JOIN category_totals ct ON ct.category_id = pds.category_id
            JOIN active_dishes d ON d.id = pds.dish_id -- un plat archivé sort des classements
            LEFT JOIN establishments e ON e.id = d.establishment_id
            LEFT JOIN dish_vote_shares dvs ON dvs.dish_id = pds.dish_id
            ORDER BY c.id, pds.average_score DESC, d.name ASC
        ");

        $categories = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $categoryId = (int) $row['category_id'];

            $categories[$categoryId] ??= [
                'id'          => $categoryId,
                'name'        => $row['category_name'],
                'slug'        => $row['category_slug'],
                'color'       => $row['category_color'],
                'tournaments' => (int) $row['category_tournaments'],
                'runs'        => (int) $row['category_runs'],
                'dishes'      => [],
            ];

            $categories[$categoryId]['dishes'][] = [
                'id'                  => (int) $row['dish_id'],
                'name'                => $row['dish_name'],
                'picture'             => $row['dish_picture'],
                'restaurant'          => $row['restaurant'],
                'city'                => $row['city'],
                'average_score'       => (float) $row['average_score'],
                'tournament_count'    => (int) $row['tournament_count'],
                'participation_count' => (int) $row['participation_count'],
                // null si aucune battle votée : on affiche « — » plutôt qu'un 0 % trompeur
                'vote_percent'        => $row['vote_percent'] === null ? null : (int) round((float) $row['vote_percent']),
                'voted_battles'       => (int) $row['voted_battles'],
            ];
        }

        // Rang « sportif » : deux plats au même score affiché partagent le rang, le suivant saute (1, 2, 2, 4).
        foreach ($categories as &$category) {
            $previous = null;
            foreach ($category['dishes'] as $i => &$dish) {
                $shown = round($dish['average_score'], 2);
                $dish['rank'] = $shown === $previous ? $category['dishes'][$i - 1]['rank'] : $i + 1;
                $previous = $shown;
            }
            unset($dish);
        }
        unset($category);

        return array_values($categories);
    }
}
