<?php
// filepath: app/controllers/DishController.php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';
require_once __DIR__ . '/../includes/manage.php';
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

    /**
     * GET/POST /plat/creer : formulaire d'ajout d'un plat.
     * ?depuis=ID : « Dupliquer » — formulaire prérempli à partir d'un de ses plats,
     * pour en faire une autre version (en pratique : changer de catégorie, qui n'est pas modifiable).
     */
    public function create(): void
    {
        // La base exige un creator_id : il faut être connecté.
        require_login();

        $dishes = new Dish($this->db);
        $categories = (new Category($this->db))->all();
        $establishments = (new Establishment($this->db))->allForSelect();

        // Plat source d'une duplication : seulement un de ses plats (ou n'importe lequel pour un admin).
        $sourceId = (int) ($_GET['depuis'] ?? $_POST['depuis'] ?? 0);
        $source = $sourceId > 0 ? load_managed($dishes->find($sourceId)) : null;

        $errors = [];
        $old = [
            'name'             => $source['name'] ?? '',
            'description'      => $source['description'] ?? '',
            'price'            => $source ? $this->priceForInput($source['price']) : '',
            // ?restaurant=ID pré-sélectionne un restaurant (lien depuis le dashboard)
            'establishment_id' => (string) ($source['establishment_id'] ?? (int) ($_GET['restaurant'] ?? 0)),
            // ?categorie=ID pré-sélectionne la catégorie (retour depuis « Ajouter une catégorie »).
            // Pas en duplication : on duplique justement pour en choisir une autre.
            'category_id'      => $source ? 0 : (int) ($_GET['categorie'] ?? 0),
            'keep_picture'     => $source && $source['picture'] !== null,
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_post();

            $old['name'] = trim((string) ($_POST['name'] ?? ''));
            $old['keep_picture'] = $source && $source['picture'] !== null && isset($_POST['keep_picture']);
            $price = $this->validateDetails($old, $establishments, $errors);

            if ($old['name'] === '') {
                $errors[] = 'Le nom du plat est obligatoire.';
            } elseif (mb_strlen($old['name']) > self::NAME_MAX) {
                $errors[] = 'Le nom ne doit pas dépasser ' . self::NAME_MAX . ' caractères.';
            }

            // Une seule catégorie par plat : un plat appartient à une seule famille de duels.
            $old['category_id'] = (int) ($_POST['category_id'] ?? 0);
            if ($old['category_id'] === 0) {
                $errors[] = 'Choisis une catégorie : sans catégorie, le plat ne peut pas entrer dans les duels.';
            } elseif (!isset($categories[$old['category_id']])) {
                $errors[] = 'La catégorie choisie n’existe pas.';
            }

            // Photo facultative : une nouvelle photo, sinon celle du plat dupliqué (copiée), sinon aucune.
            // Enregistrée seulement une fois le reste validé (pas de fichier orphelin).
            $picture = null;
            if (!$errors) {
                $picture = save_uploaded_picture('picture', 'dishes', $errors);
                if ($picture === null && !$errors && $old['keep_picture']) {
                    $picture = copy_uploaded_picture($source['picture'], 'dishes');
                }
            }

            if (!$errors) {
                try {
                    $id = $dishes->create(
                        $old['name'],
                        $old['description'] !== '' ? $old['description'] : null,
                        $price,
                        (int) $old['establishment_id'] ?: null,
                        current_user_id(),
                        [$old['category_id']],
                        $picture
                    );

                    $query = ['success' => $id] + ($source ? ['depuis' => $source['id']] : []);
                    header('Location: /plat/creer?' . http_build_query($query), true, 303);
                    exit;
                } catch (Throwable $e) {
                    delete_uploaded_picture($picture);
                    error_log($e->getMessage());
                    $errors[] = 'Une erreur est survenue lors de l’enregistrement.';
                }
            }
        }

        // Écran de succès : le plat créé (lien « Une erreur ? Modifier ») et, après une duplication,
        // le plat d'origine (proposition de le supprimer ou de l'archiver).
        $created = isset($_GET['success']) ? $dishes->find((int) $_GET['success']) : null;
        if ($created && (int) $created['creator_id'] !== current_user_id()) {
            $created = null;
        }
        $original = $created && $source ? $source : null;
        $originalPlan = $original && $original['archived_at'] === null ? deletion_plan('plat', $original) : null;

        $title = $source ? 'Dupliquer un plat' : 'Ajouter un plat';
        require __DIR__ . '/../views/createdish.php';
    }

    /** GET/POST /plat/{id}/modifier */
    public function edit(int $id): void
    {
        $dishes = new Dish($this->db);
        $dish = load_managed($dishes->find($id));
        $establishments = (new Establishment($this->db))->allForSelect();
        $nameLocked = dish_name_locked($dish);
        $return = safe_return($_GET['retour'] ?? $_POST['retour'] ?? null, '/dashboard?onglet=plats');

        $errors = [];
        $old = [
            'name'             => $dish['name'],
            'description'      => (string) $dish['description'],
            'price'            => $this->priceForInput($dish['price']),
            'establishment_id' => (string) (int) $dish['establishment_id'],
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_post();

            $price = $this->validateDetails($old, $establishments, $errors);

            // Nom verrouillé (plat en jeu) : la valeur envoyée est ignorée, même si le champ a été
            // déverrouillé dans les outils du navigateur. Le serveur décide, pas le formulaire.
            if (!$nameLocked) {
                $old['name'] = trim((string) ($_POST['name'] ?? ''));
                if ($old['name'] === '') {
                    $errors[] = 'Le nom du plat est obligatoire.';
                } elseif (mb_strlen($old['name']) > self::NAME_MAX) {
                    $errors[] = 'Le nom ne doit pas dépasser ' . self::NAME_MAX . ' caractères.';
                }
            }

            $removePicture = isset($_POST['remove_picture']);
            $picture = null;
            if (!$errors) {
                $picture = save_uploaded_picture('picture', 'dishes', $errors);
            }

            if (!$errors) {
                $fields = [
                    'description'      => $old['description'] !== '' ? $old['description'] : null,
                    'price'            => $price,
                    'establishment_id' => (int) $old['establishment_id'] ?: null,
                ];
                if (!$nameLocked) {
                    $fields['name'] = $old['name'];
                }
                if ($picture !== null || $removePicture) {
                    $fields['picture'] = $picture; // null = retour à l'image de la catégorie
                }

                try {
                    $dishes->update($id, $fields);
                    if (array_key_exists('picture', $fields)) {
                        delete_uploaded_picture($dish['picture']); // l'ancienne photo n'est plus utilisée
                    }
                    flash_set('« ' . ($fields['name'] ?? $dish['name']) . ' » a été modifié.');
                    header('Location: ' . $return, true, 303);
                    exit;
                } catch (Throwable $e) {
                    delete_uploaded_picture($picture);
                    error_log($e->getMessage());
                    $errors[] = 'Une erreur est survenue lors de l’enregistrement.';
                }
            }
        }

        $title = 'Modifier ' . $dish['name'];
        require __DIR__ . '/../views/editdish.php';
    }

    /** GET (confirmation) / POST /plat/{id}/supprimer : suppression, ou archivage si le plat est en jeu. */
    public function delete(int $id): void
    {
        $dishes = new Dish($this->db);
        $dish = load_managed($dishes->find($id));

        if ($dish['archived_at'] !== null) {
            flash_set('« ' . $dish['name'] . ' » est déjà archivé.', 'error');
            header('Location: ' . safe_return($_POST['retour'] ?? $_GET['retour'] ?? null, '/dashboard?onglet=plats'), true, 303);
            exit;
        }

        handle_deletion('plat', $dish, $dish['name'], static fn(): string => $dishes->remove($id));
    }

    /** POST /plat/{id}/restaurer : un plat archivé revient dans les listes et les duels. */
    public function restore(int $id): void
    {
        $dishes = new Dish($this->db);
        $dish = load_managed($dishes->find($id));
        check_post();

        $dishes->restore($id);
        flash_set('« ' . $dish['name'] . ' » est de retour dans les listes et les duels.');
        header('Location: ' . safe_return($_POST['retour'] ?? null, '/dashboard?onglet=plats'), true, 303);
        exit;
    }

    /**
     * Champs communs à la création et à la modification : description, prix, restaurant.
     * Remplit $old avec les valeurs envoyées et retourne le prix normalisé (ou null).
     */
    private function validateDetails(array &$old, array $establishments, array &$errors): ?string
    {
        $old['description'] = trim((string) ($_POST['description'] ?? ''));
        $old['price'] = trim((string) ($_POST['price'] ?? ''));
        $old['establishment_id'] = (string) (int) ($_POST['establishment_id'] ?? 0);

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

        return $price;
    }

    /** « 12.50 » (base) → « 12,50 » (saisie française). */
    private function priceForInput(?string $price): string
    {
        return $price === null ? '' : str_replace('.', ',', $price);
    }
}
