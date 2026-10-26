<?php
// filepath: app/controllers/CategoryController.php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';
require_once __DIR__ . '/../models/Category.php';

class CategoryController
{
    private const NAME_MIN = 2;
    private const NAME_MAX = 30;

    public function __construct(private PDO $db)
    {
    }

    /** GET/POST /categorie/creer : liste des catégories et formulaire de création. */
    public function create(): void
    {
        // La base exige un creator_id : il faut être connecté.
        if (empty($_SESSION['user_id'])) {
            header('Location: /login', true, 303);
            exit;
        }

        // ?retour=plat : on vient du formulaire d'ajout de plat, on y retournera après la création.
        $return = ($_GET['retour'] ?? $_POST['retour'] ?? '') === 'plat' ? 'plat' : '';

        $colors = category_colors();
        $errors = [];
        $old = ['name' => '', 'color' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_post();

            // Espaces multiples ramenés à un seul : « Plats   indiens » → « Plats indiens »
            $old['name'] = preg_replace('/\s+/u', ' ', trim((string) ($_POST['name'] ?? '')));
            $old['color'] = (string) ($_POST['color'] ?? '');

            $length = mb_strlen($old['name']);
            $slug = slugify($old['name']);

            if ($old['name'] === '') {
                $errors[] = 'Le nom de la catégorie est obligatoire.';
            } elseif ($length < self::NAME_MIN || $length > self::NAME_MAX) {
                $errors[] = 'Le nom doit contenir entre ' . self::NAME_MIN . ' et ' . self::NAME_MAX . ' caractères.';
            } elseif (!preg_match("/^\\p{L}[\\p{L}\\p{M} '’&-]*$/u", $old['name']) || $slug === '') {
                $errors[] = 'Le nom ne peut contenir que des lettres, des espaces, des apostrophes, des tirets ou « & ».';
            } elseif ($similar = find_similar_category($old['name'])) {
                // « Pizzas », « PIZZAS » et « pizza » : même catégorie, au pluriel ou à la casse près.
                $errors[] = 'La catégorie « ' . $similar['label'] . ' » existe déjà. Ajoute plutôt ton plat dedans.';
            }

            if (!isset($colors[$old['color']])) {
                $errors[] = 'Choisis une couleur parmi celles proposées.';
            }

            // Photo obligatoire : c'est l'image par défaut des plats de la catégorie.
            $pictureSent = ($_FILES['picture']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            if (!$pictureSent) {
                $errors[] = 'Ajoute une photo : elle illustrera les plats de la catégorie qui n’ont pas la leur.';
            }

            // L'image n'est enregistrée qu'une fois le reste validé (pas de fichier orphelin).
            $picture = null;
            if (!$errors) {
                $picture = save_uploaded_picture('picture', 'categories', $errors);
            }

            if (!$errors) {
                try {
                    $id = (new Category($this->db))->create(
                        $old['name'],
                        $slug,
                        $old['color'],
                        $picture,
                        (int) $_SESSION['user_id']
                    );

                    $target = $return === 'plat'
                        ? '/plat/creer?categorie=' . $id
                        : '/categorie/creer?success=' . rawurlencode($slug);
                    header('Location: ' . $target, true, 303);
                    exit;
                } catch (PDOException $e) {
                    delete_uploaded_picture($picture);
                    if ($e->getCode() !== '23505') {
                        throw $e;
                    }
                    // Créée par quelqu'un d'autre entre-temps : la contrainte UNIQUE de la base a tranché.
                    $errors[] = 'Cette catégorie vient d’être créée par quelqu’un d’autre. Elle est maintenant dans la liste.';
                }
            }
        }

        $categories = categories();
        $created = $categories[(string) ($_GET['success'] ?? '')] ?? null;
        $title = 'Ajouter une catégorie';

        require __DIR__ . '/../views/createcategory.php';
    }
}
