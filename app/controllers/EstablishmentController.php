<?php
// filepath: app/controllers/EstablishmentController.php

require_once __DIR__ . '/../models/Establishment.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/manage.php';

class EstablishmentController
{
    public function __construct(private PDO $db)
    {
    }

    /** GET/POST /restaurant/creer */
    public function create(): void
    {
        // La base exige un creator_id : il faut donc être connecté.
        require_login();

        $errors = [];
        $old = ['name' => '', 'description' => '', 'city' => '', 'address' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_post();
            $this->validate($old, $errors);

            if (!$errors) {
                try {
                    (new Establishment($this->db))->create(
                        $old['name'],
                        $old['description'] !== '' ? $old['description'] : null,
                        null, // plus de photo pour les restaurants
                        $old['city'],
                        $old['address'],
                        current_user_id()
                    );

                    header('Location: /restaurant/creer?success=1', true, 303);
                    exit;
                } catch (Throwable $e) {
                    error_log($e->getMessage());
                    $errors[] = 'Une erreur est survenue lors de l’enregistrement.';
                }
            }
        }

        $title = 'Ajouter un restaurant';
        require __DIR__ . '/../views/createrestaurant.php';
    }

    /** GET/POST /restaurant/{id}/modifier : tous les champs restent modifiables (corriger une adresse…). */
    public function edit(int $id): void
    {
        $establishments = new Establishment($this->db);
        $restaurant = load_managed($establishments->find($id));
        $return = safe_return($_GET['retour'] ?? $_POST['retour'] ?? null, '/dashboard?onglet=restaurants');

        $errors = [];
        $old = [
            'name'        => $restaurant['name'],
            'description' => (string) $restaurant['description'],
            'city'        => $restaurant['city'],
            'address'     => $restaurant['address'],
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_post();
            $this->validate($old, $errors);

            if (!$errors) {
                $establishments->update(
                    $id,
                    $old['name'],
                    $old['description'] !== '' ? $old['description'] : null,
                    $old['city'],
                    $old['address']
                );
                flash_set('« ' . $old['name'] . ' » a été modifié.');
                header('Location: ' . $return, true, 303);
                exit;
            }
        }

        $title = 'Modifier ' . $restaurant['name'];
        require __DIR__ . '/../views/editrestaurant.php';
    }

    /** GET (confirmation) / POST /restaurant/{id}/supprimer : seulement s'il n'a aucun plat. */
    public function delete(int $id): void
    {
        $establishments = new Establishment($this->db);
        $restaurant = load_managed($establishments->find($id));

        handle_deletion('restaurant', $restaurant, $restaurant['name'], static fn(): bool => $establishments->delete($id));
    }

    /** Champs communs à la création et à la modification. Remplit $old avec les valeurs envoyées. */
    private function validate(array &$old, array &$errors): void
    {
        foreach (array_keys($old) as $field) {
            $old[$field] = trim((string) ($_POST[$field] ?? ''));
        }

        if ($old['name'] === '') {
            $errors[] = 'Le nom du restaurant est obligatoire.';
        } elseif (mb_strlen($old['name']) > 150) {
            $errors[] = 'Le nom ne doit pas dépasser 150 caractères.';
        }

        if ($old['city'] === '') {
            $errors[] = 'La ville est obligatoire.';
        } elseif (mb_strlen($old['city']) > 80) {
            $errors[] = 'La ville ne doit pas dépasser 80 caractères.';
        }

        if ($old['address'] === '') {
            $errors[] = 'L’adresse est obligatoire.';
        }

        if (mb_strlen($old['description']) > 1000) {
            $errors[] = 'La description ne doit pas dépasser 1000 caractères.';
        }
    }
}
