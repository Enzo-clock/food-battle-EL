<?php
// filepath: app/controllers/DishController.php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Dish.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Establishment.php';

class DishController
{
    private const NAME_MAX = 150;          // dishes.name VARCHAR(150)
    private const DESCRIPTION_MAX = 1000;
    private const PRICE_MAX = 9999.99;     // dishes.price NUMERIC(6, 2)

    public function __construct(private PDO $db)
    {
    }

    /** GET/POST /plat/creer : formulaire d'ajout d'un plat. */
    public function create(): void
    {
        // La base exige un creator_id : il faut être connecté.
        if (empty($_SESSION['user_id'])) {
            header('Location: /login', true, 303);
            exit;
        }

        $categories = (new Category($this->db))->all();
        $establishments = (new Establishment($this->db))->allForSelect();

        $errors = [];
        $old = [
            'name'             => '',
            'description'      => '',
            'price'            => '',
            // ?restaurant=ID pré-sélectionne un restaurant (lien depuis sa fiche, plus tard)
            'establishment_id' => (string) (int) ($_GET['restaurant'] ?? 0),
            'categories'       => [],
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_post();

            $old['name'] = trim((string) ($_POST['name'] ?? ''));
            $old['description'] = trim((string) ($_POST['description'] ?? ''));
            $old['price'] = trim((string) ($_POST['price'] ?? ''));
            $old['establishment_id'] = (string) ($_POST['establishment_id'] ?? '0');
            $old['categories'] = array_map('intval', (array) ($_POST['categories'] ?? []));

            // Nom
            if ($old['name'] === '') {
                $errors[] = 'Le nom du plat est obligatoire.';
            } elseif (mb_strlen($old['name']) > self::NAME_MAX) {
                $errors[] = 'Le nom ne doit pas dépasser ' . self::NAME_MAX . ' caractères.';
            }

            // Description (facultative)
            if (mb_strlen($old['description']) > self::DESCRIPTION_MAX) {
                $errors[] = 'La description ne doit pas dépasser ' . self::DESCRIPTION_MAX . ' caractères.';
            }

            // Prix (facultatif) : accepte « 12,50 » comme « 12.50 »
            $price = null;
            if ($old['price'] !== '') {
                $normalized = str_replace([',', ' ', '€'], ['.', '', ''], $old['price']);
                if (!preg_match('/^\d{1,4}(\.\d{1,2})?$/', $normalized) || (float) $normalized > self::PRICE_MAX) {
                    $errors[] = 'Le prix doit être un montant entre 0 et 9 999,99 € (ex. 12,50).';
                } else {
                    $price = $normalized;
                }
            }

            // Restaurant (facultatif : « 0 » = plat maison) — doit exister en base
            $establishmentId = (int) $old['establishment_id'];
            if ($establishmentId !== 0 && !isset($establishments[$establishmentId])) {
                $errors[] = 'Le restaurant choisi n’existe pas.';
            }

            // Catégories : au moins une, et uniquement des catégories existantes
            $categoryIds = array_values(array_unique(array_filter(
                $old['categories'],
                static fn(int $id): bool => isset($categories[$id])
            )));
            if (!$categoryIds) {
                $errors[] = 'Choisis au moins une catégorie : sans catégorie, le plat ne peut pas entrer dans les duels.';
            }

            if (!$errors) {
                try {
                    (new Dish($this->db))->create(
                        $old['name'],
                        $old['description'] !== '' ? $old['description'] : null,
                        $price,
                        $establishmentId !== 0 ? $establishmentId : null,
                        (int) $_SESSION['user_id'],
                        $categoryIds
                    );

                    header('Location: /plat/creer?success=1', true, 303);
                    exit;
                } catch (Throwable $e) {
                    error_log($e->getMessage());
                    $errors[] = 'Une erreur est survenue lors de l’enregistrement.';
                }
            }
        }

        $title = 'Ajouter un plat';
        require __DIR__ . '/../views/createdish.php';
    }
}
