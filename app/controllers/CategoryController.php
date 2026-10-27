<?php
// filepath: app/controllers/CategoryController.php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Moderation.php';
require_once __DIR__ . '/../includes/manage.php';

class CategoryController
{
    private const NAME_MIN = 2;
    private const NAME_MAX = 30;

    public function __construct(private PDO $db)
    {
    }

    /**
     * GET/POST /categorie/creer : liste des catégories et formulaire.
     * Un utilisateur PROPOSE une catégorie (en attente de validation) ; un admin la crée directement.
     */
    public function create(): void
    {
        // La base exige un creator_id : il faut être connecté.
        require_login();
        $isAdmin = is_admin();

        // ?retour=plat : on vient du formulaire d'ajout de plat, on y retournera après la création.
        $return = ($_GET['retour'] ?? $_POST['retour'] ?? '') === 'plat' ? 'plat' : '';

        $colors = category_colors();
        $errors = [];
        $old = ['name' => '', 'color' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_post();

            $this->validate($old, $errors);
            $slug = slugify($old['name']);

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
                        current_user_id(),
                        Moderation::initialStatus()
                    );

                    // Retour direct vers l'ajout de plat seulement si la catégorie est utilisable tout de suite.
                    $target = $return === 'plat' && $isAdmin
                        ? '/plat/creer?categorie=' . $id
                        : '/categorie/creer?success=' . $id;
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

        // Écran de succès : seulement pour sa propre catégorie (validée ou en attente).
        $created = isset($_GET['success']) ? (new Category($this->db))->find((int) $_GET['success']) : null;
        if ($created && (int) $created['creator_id'] !== current_user_id()) {
            $created = null;
        }
        $title = $isAdmin ? 'Ajouter une catégorie' : 'Proposer une catégorie';

        require __DIR__ . '/../views/createcategory.php';
    }

    /**
     * GET/POST /categorie/{id}/modifier : nom, couleur et photo.
     * Le slug ne change pas : il sert dans les URL (?categorie=burger) déjà partagées.
     */
    public function edit(int $id): void
    {
        $model = new Category($this->db);
        $category = load_managed($model->find($id));
        $return = safe_return($_GET['retour'] ?? $_POST['retour'] ?? null, '/dashboard?onglet=categories');

        // Une catégorie validée appartient au site : seul un admin la modifie.
        // Sinon, il suffirait de faire valider « Desserts » puis de la renommer « Vegan ».
        if (!is_admin() && $category['status'] === 'approved') {
            flash_set('« ' . $category['name'] . ' » est validée : seul un administrateur peut désormais la modifier.', 'error');
            header('Location: ' . $return, true, 303);
            exit;
        }

        $colors = category_colors();
        $errors = [];
        $old = ['name' => $category['name'], 'color' => $category['color']];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_post();
            $this->validate($old, $errors, $id);

            // Photo facultative ici : on garde l'actuelle si aucune n'est envoyée.
            $picture = null;
            if (!$errors) {
                $picture = save_uploaded_picture('picture', 'categories', $errors);
            }

            if (!$errors) {
                try {
                    $model->update(
                        $id,
                        $old['name'],
                        $old['color'],
                        $picture ?? $category['picture'],
                        $category['status'] === 'approved' ? null : slugify($old['name'])
                    );
                    if ($picture !== null) {
                        delete_uploaded_picture($category['picture']);
                    }
                    if ($category['status'] === 'rejected') {
                        (new Moderation($this->db))->resubmit('categories', $id);
                        flash_set('« ' . $old['name'] . ' » est corrigée et renvoyée pour validation.');
                    } else {
                        flash_set('La catégorie « ' . $old['name'] . ' » a été modifiée.');
                    }
                    header('Location: ' . $return, true, 303);
                    exit;
                } catch (PDOException $e) {
                    delete_uploaded_picture($picture);
                    if ($e->getCode() !== '23505') {
                        throw $e;
                    }
                    $errors[] = 'Une catégorie porte déjà ce nom.';
                }
            }
        }

        $categories = categories();
        $title = 'Modifier ' . $category['name'];
        require __DIR__ . '/../views/editcategory.php';
    }

    /** GET (confirmation) / POST /categorie/{id}/supprimer : seulement si elle n'a aucun plat. */
    public function delete(int $id): void
    {
        $model = new Category($this->db);
        $category = load_managed($model->find($id));

        handle_deletion('categorie', $category, $category['name'], static function () use ($model, $id): bool {
            $picture = $model->delete($id);
            if ($picture === false) {
                return false;
            }
            delete_uploaded_picture($picture);
            return true;
        });
    }

    /**
     * Nom et couleur, communs à la création et à la modification.
     * $selfId : en modification, la catégorie elle-même n'est pas un doublon.
     */
    private function validate(array &$old, array &$errors, ?int $selfId = null): void
    {
        // Espaces multiples ramenés à un seul : « Plats   indiens » → « Plats indiens »
        $old['name'] = preg_replace('/\s+/u', ' ', trim((string) ($_POST['name'] ?? '')));
        $old['color'] = (string) ($_POST['color'] ?? '');

        $length = mb_strlen($old['name']);
        $similar = find_similar_category($old['name']);

        if ($old['name'] === '') {
            $errors[] = 'Le nom de la catégorie est obligatoire.';
        } elseif ($length < self::NAME_MIN || $length > self::NAME_MAX) {
            $errors[] = 'Le nom doit contenir entre ' . self::NAME_MIN . ' et ' . self::NAME_MAX . ' caractères.';
        } elseif (!preg_match("/^\p{L}[\p{L}\p{M} '’&-]*$/u", $old['name']) || slugify($old['name']) === '') {
            $errors[] = 'Le nom ne peut contenir que des lettres, des espaces, des apostrophes, des tirets ou « & ».';
        } elseif ($similar && $similar['id'] !== $selfId) {
            // « Pizzas », « PIZZAS » et « pizza » : même catégorie, au pluriel ou à la casse près.
            $errors[] = match ($similar['status']) {
                'pending'  => 'La catégorie « ' . $similar['label'] . ' » est déjà proposée et attend sa validation.',
                'rejected' => 'La catégorie « ' . $similar['label'] . ' » a déjà été proposée et refusée'
                    . ($similar['reason'] ? ' : ' . $similar['reason'] : '.'),
                default    => 'La catégorie « ' . $similar['label'] . ' » existe déjà.'
                    . ($selfId === null ? ' Ajoute plutôt ton plat dedans.' : ''),
            };
        }

        if (!isset(category_colors()[$old['color']])) {
            $errors[] = 'Choisis une couleur parmi celles proposées.';
        }
    }
}
