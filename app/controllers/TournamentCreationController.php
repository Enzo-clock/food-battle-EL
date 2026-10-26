<?php
// filepath: app/controllers/TournamentCreationController.php

require_once __DIR__ . '/../includes/functions.php';

class TournamentCreationController
{
    public function __construct(private PDO $db) {}

    public function create(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);

        if ($userId <= 0) {
            http_response_code(403);
            exit('Connexion requise.');
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (!in_array($method, ['GET', 'POST'], true)) {
            http_response_code(405);
            exit('Méthode non autorisée.');
        }

        $success = null;

        if ($method === 'GET' && isset($_SESSION['tournament_created'])) {
            $success = $_SESSION['tournament_created'];
            unset($_SESSION['tournament_created']);
        }

        $categories = categories();
        $errors = [];
        $selectedBattleIds = [];
        $title = 'Créer un tournoi';

        $requestedCategory = (string) (
            $_POST['category']
            ?? $_GET['categorie']
            ?? ''
        );

        $categoryIsValid = $requestedCategory !== ''
            && array_key_exists($requestedCategory, $categories);

        $category = $categoryIsValid ? $requestedCategory : '';

        if ($method === 'POST') {
            check_post();

            if ($requestedCategory === '') {
                $errors[] = 'Choisis une catégorie.';
            } elseif (!$categoryIsValid) {
                $errors[] = 'La catégorie sélectionnée est invalide.';
            }

            $rawIds = $_POST['battle_ids'] ?? null;

            if (!is_array($rawIds) || count($rawIds) !== 4) {
                $errors[] = 'Sélectionne exactement quatre battles.';
            } else {
                foreach ($rawIds as $rawId) {
                    $id = filter_var($rawId, FILTER_VALIDATE_INT);

                    if ($id === false || $id <= 0) {
                        $errors[] = 'La sélection contient un identifiant invalide.';
                        $selectedBattleIds = [];
                        break;
                    }

                    $selectedBattleIds[] = (int) $id;
                }

                if (
                    count($selectedBattleIds) === 4
                    && count(array_unique($selectedBattleIds)) !== 4
                ) {
                    $errors[] = 'Les quatre battles doivent être différents.';
                }
            }

            if (!$errors) {
                $placeholders = implode(
                    ', ',
                    array_fill(0, 4, '?')
                );

                $sql = "
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
                          WHERE dc.dish_id = d1.id
                            AND c.name = ?
                      )
                      AND EXISTS (
                          SELECT 1
                          FROM dish_categories dc
                          JOIN categories c ON c.id = dc.category_id
                          WHERE dc.dish_id = d2.id
                            AND c.name = ?
                      )
                ";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ...$selectedBattleIds,
                    $userId,
                    $category,
                    $category,
                ]);

                $validBattles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $validBattleIds = array_map(
                    'intval',
                    array_column($validBattles, 'id')
                );

                if (count($validBattleIds) !== 4) {
                    $errors[] = 'Un ou plusieurs battles ne sont plus disponibles, ne t’appartiennent pas ou ne correspondent pas à cette catégorie.';
                } else {
                    $dishIds = [];

                    foreach ($validBattles as $battle) {
                        $dishIds[] = (int) $battle['dish_1_id'];
                        $dishIds[] = (int) $battle['dish_2_id'];
                    }

                    if (count(array_unique($dishIds)) !== 8) {
                        $errors[] = 'Un même plat ne peut pas apparaître dans plusieurs battles du tournoi.';
                    }
                }
            }
        }

        $sql = "
            SELECT
                b.id AS battle_id,
                d1.name AS dish_1_name,
                d2.name AS dish_2_name
            FROM battles b
            JOIN dishes d1 ON d1.id = b.dish_1_id
            JOIN dishes d2 ON d2.id = b.dish_2_id
            WHERE b.creator_id = :creator_id
              AND b.tournament_id IS NULL
        ";

        $params = ['creator_id' => $userId];

        if ($category !== '') {
            $sql .= "
                AND EXISTS (
                    SELECT 1
                    FROM dish_categories dc1
                    JOIN categories c1 ON c1.id = dc1.category_id
                    WHERE dc1.dish_id = d1.id
                      AND c1.name = :category_1
                )
                AND EXISTS (
                    SELECT 1
                    FROM dish_categories dc2
                    JOIN categories c2 ON c2.id = dc2.category_id
                    WHERE dc2.dish_id = d2.id
                      AND c2.name = :category_2
                )
            ";

            $params['category_1'] = $category;
            $params['category_2'] = $category;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $startDate = (string) ($_POST['start_date'] ?? '');

        if ($method === 'POST' && !$errors) {
            require_once __DIR__ . '/../models/Tournament.php';

            try {
                $tournament = new Tournament($this->db);

                $tournamentId = $tournament->createWithQuarterfinals(
                    $name,
                    $startDate,
                    $userId,
                    $category,
                    $selectedBattleIds
                );

                $_SESSION['tournament_created'] = [
                    'id' => $tournamentId,
                    'name' => $name,
                ];

                header('Location: /tournoi/creer', true, 303);
                exit;
            } catch (InvalidArgumentException $error) {
                $errors[] = $error->getMessage();
            } catch (Throwable $error) {
                error_log($error->getMessage());
                $errors[] = 'La création du tournoi a échoué. Réessaie.';
            }
        }

        $sql .= ' ORDER BY b.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $battles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/createtournament.php';
    }
}
