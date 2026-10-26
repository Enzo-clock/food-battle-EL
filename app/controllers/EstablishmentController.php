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

            if (!$errors) {
                try {
                    $establishment = new Establishment($this->db);
                    $establishment->create(
                        $old['name'],
                        $description,
                        null, // plus de photo pour les restaurants
                        $old['city'],
                        $old['address'],
                        (int) $_SESSION['user_id']
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
}
