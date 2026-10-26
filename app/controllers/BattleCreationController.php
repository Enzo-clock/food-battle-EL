<?php
// filepath: app/controllers/BattleCreationController.php

require_once __DIR__ . '/../models/Battle.php';
require_once __DIR__ . '/../includes/functions.php';

class BattleCreationController
{
    public function __construct(private PDO $db)
    {
    }

    public function create(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
            header('Location: /login', true, 303);
            exit;
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['GET', 'POST'], true)) {
            http_response_code(405);
            exit('Méthode non autorisée');
        }

        $categories = categories();
        $q = trim((string) ($_GET['q'] ?? $_POST['q'] ?? ''));
        $requestedCategory = (string) ($_GET['categorie'] ?? $_POST['categorie'] ?? '');
        $category = array_key_exists($requestedCategory, $categories)
            ? $requestedCategory
            : '';

        $selectedA = (int) ($_POST['a'] ?? $_GET['a'] ?? 0);
        $selectedB = (int) ($_POST['b'] ?? $_GET['b'] ?? 0);
        $errors = [];

        $sql = "
            SELECT
                d.*,
                e.name AS restaurant,
                e.city,
                cat_data.primary_category,
                cat_data.categories_json
            FROM dishes d
            LEFT JOIN establishments e ON e.id = d.establishment_id
            LEFT JOIN LATERAL (
                SELECT
                    MIN(c.name) AS primary_category,
                    json_agg(c.name ORDER BY c.name)::text AS categories_json
                FROM dish_categories dc
                JOIN categories c ON c.id = dc.category_id
                WHERE dc.dish_id = d.id
            ) cat_data ON TRUE
            WHERE TRUE
        ";

        $params = [];

        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $sql .= ' AND (d.name ILIKE ? OR e.name ILIKE ?)';
            array_push($params, $like, $like);
        }

        if ($category !== '') {
            $sql .= "
                AND EXISTS (
                    SELECT 1
                    FROM dish_categories dc
                    JOIN categories c ON c.id = dc.category_id
                    WHERE dc.dish_id = d.id
                      AND c.name = ?
                )
            ";
            $params[] = $category;
        }

        $sql .= ' ORDER BY d.id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $dishes = $stmt->fetchAll();

        $dishesById = [];
        foreach ($dishes as $dish) {
            $dishesById[(int) $dish['id']] = $dish;
        }

        if ($method === 'POST') {
            check_post();

            $dishA = $dishesById[$selectedA] ?? null;
            $dishB = $dishesById[$selectedB] ?? null;

            $dishCategoriesA = $dishA
                ? (json_decode((string) ($dishA['categories_json'] ?? '[]'), true) ?: [])
                : [];
            $dishCategoriesB = $dishB
                ? (json_decode((string) ($dishB['categories_json'] ?? '[]'), true) ?: [])
                : [];

            $sharedCategories = array_intersect($dishCategoriesA, $dishCategoriesB);
            $sameCategory = $category === ''
                ? !empty($sharedCategories)
                : in_array($category, $sharedCategories, true);

            if (
                $selectedA <= 0
                || $selectedB <= 0
                || $selectedA === $selectedB
                || !$dishA
                || !$dishB
                || !$sameCategory
            ) {
                $errors[] = 'Choisis deux plats différents qui partagent une catégorie.';
            } else {
                $battleId = (new Battle($this->db))->findOrCreateFree(
                    $selectedA,
                    $selectedB,
                    (int) $_SESSION['user_id']
                );

                header(
                    'Location: /duel?' . http_build_query([
                        'categorie' => $category !== ''
                            ? $category
                            : (string) reset($sharedCategories),
                        'a' => $selectedA,
                        'b' => $selectedB,
                    ]),
                    true,
                    303
                );
                exit;
            }
        }

        $title = 'Créer un duel';
        require __DIR__ . '/../views/createbattle.php';
    }
}