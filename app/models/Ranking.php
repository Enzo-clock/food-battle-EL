<?php

class Ranking
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Classe les plats par catégorie à partir des participations terminées.
     *
     * La moyenne finale d'un plat est la moyenne des moyennes obtenues
     * dans chaque tournoi auquel il a participé.
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     color: string,
     *     dishes: array<int, array{
     *         id: int,
     *         name: string,
     *         average_score: float,
     *         tournament_count: int,
     *         participation_count: int
     *     }>
     * }>
     */
    public function byCategory(): array
    {
        $stmt = $this->db->query("
            WITH completed_runs AS (
                SELECT
                    id AS run_id,
                    tournament_id
                FROM tournament_runs
                WHERE finished_at IS NOT NULL
            ),
            category_tournament_dishes AS (
                SELECT
                    c.id AS category_id,
                    td.tournament_id,
                    td.dish_id
                FROM categories c
                JOIN dish_categories dc
                    ON dc.category_id = c.id
                JOIN tournament_dishes td
                    ON td.dish_id = dc.dish_id
                WHERE c.status = 'approved'
            ),
            per_run_scores AS (
                SELECT
                    ctd.category_id,
                    cr.tournament_id,
                    cr.run_id,
                    ctd.dish_id,
                    COUNT(m.slot)::int AS score
                FROM category_tournament_dishes ctd
                JOIN completed_runs cr
                    ON cr.tournament_id = ctd.tournament_id
                LEFT JOIN tournament_run_matches m
                    ON m.run_id = cr.run_id
                   AND m.dish_winner_id = ctd.dish_id
                GROUP BY
                    ctd.category_id,
                    cr.tournament_id,
                    cr.run_id,
                    ctd.dish_id
            ),
            per_tournament_scores AS (
                SELECT
                    category_id,
                    tournament_id,
                    dish_id,
                    AVG(score) AS tournament_average,
                    COUNT(*)::int AS participation_count
                FROM per_run_scores
                GROUP BY
                    category_id,
                    tournament_id,
                    dish_id
            ),
            per_dish_scores AS (
                SELECT
                    category_id,
                    dish_id,
                    AVG(tournament_average) AS average_score,
                    COUNT(*)::int AS tournament_count,
                    SUM(participation_count)::int AS participation_count
                FROM per_tournament_scores
                GROUP BY
                    category_id,
                    dish_id
            )
            SELECT
                c.id AS category_id,
                c.name AS category_name,
                c.slug AS category_slug,
                c.color AS category_color,
                d.id AS dish_id,
                d.name AS dish_name,
                pds.average_score,
                pds.tournament_count,
                pds.participation_count
            FROM per_dish_scores pds
            JOIN categories c
                ON c.id = pds.category_id
            JOIN dishes d
                ON d.id = pds.dish_id
            ORDER BY
                c.name,
                pds.average_score DESC,
                d.name ASC
        ");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $categories = [];

        foreach ($rows as $row) {
            $categoryId = (int) $row['category_id'];

            if (!isset($categories[$categoryId])) {
                $categories[$categoryId] = [
                    'id' => $categoryId,
                    'name' => $row['category_name'],
                    'slug' => $row['category_slug'],
                    'color' => $row['category_color'],
                    'dishes' => [],
                ];
            }

            $categories[$categoryId]['dishes'][] = [
                'id' => (int) $row['dish_id'],
                'name' => $row['dish_name'],
                'average_score' => (float) $row['average_score'],
                'tournament_count' => (int) $row['tournament_count'],
                'participation_count' => (int) $row['participation_count'],
            ];
        }

        return array_values($categories);
    }
}