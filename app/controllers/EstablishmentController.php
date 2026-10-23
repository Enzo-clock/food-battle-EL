<?php
// filepath: app/controllers/EstablishmentController.php

require_once __DIR__ . '/../models/Establishment.php';
require_once __DIR__ . '/../includes/functions.php';

class EstablishmentController
{
    public function __construct(private PDO $db)
    {
    }

    public function create(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // La base exige un creator_id : il faut donc être connecté.
        if (empty($_SESSION['user_id'])) {
            header('Location: /login', true, 303);
            exit;
        }

        if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'POST'], true)) {
            http_response_code(405);
            exit('Méthode non autorisée');
        }

        $errors = [];
        $old = [
            'name' => '',
            'description' => '',
            'city' => '',
            'address' => '',
        ];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            check_post();

            foreach ($old as $field => $_) {
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

            $description = $old['description'] !== '' ? $old['description'] : null;
            $picturePath = null;

            if (!$errors) {
                $picturePath = $this->savePicture($errors);
            }

            if (!$errors) {
                try {
                    $establishment = new Establishment($this->db);
                    $establishment->create(
                        $old['name'],
                        $description,
                        $picturePath,
                        $old['city'],
                        $old['address'],
                        (int) $_SESSION['user_id']
                    );

                    header('Location: /restaurant/creer?success=1', true, 303);
                    exit;
                } catch (Throwable $e) {
                    if ($picturePath !== null) {
                        @unlink(dirname(__DIR__, 2) . '/public' . $picturePath);
                    }

                    error_log($e->getMessage());
                    $errors[] = 'Une erreur est survenue lors de l’enregistrement.';
                }
            }
        }

        $title = 'Ajouter un restaurant';
        require __DIR__ . '/../views/createrestaurant.php';
    }

    private function savePicture(array &$errors): ?string
    {
        $file = $_FILES['picture'] ?? null;

        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null; // L’image est facultative.
        }

        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Le téléversement de l’image a échoué.';
            return null;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'L’image ne doit pas dépasser 5 Mo.';
            return null;
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($extensions[$mime])) {
            $errors[] = 'Formats acceptés : JPG, PNG ou WebP.';
            return null;
        }

        $directory = dirname(__DIR__, 2) . '/public/uploads/establishments';

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            $errors[] = 'Le dossier de stockage des images est indisponible.';
            return null;
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];

        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
            $errors[] = 'Impossible d’enregistrer l’image.';
            return null;
        }

        return '/uploads/establishments/' . $filename;
    }
}