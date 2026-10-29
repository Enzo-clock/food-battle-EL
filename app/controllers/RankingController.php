<?php
// filepath: app/controllers/RankingController.php

require_once __DIR__ . '/../models/Ranking.php';

class RankingController
{
    public function __construct(private PDO $db)
    {
    }

    /** GET /classements?categorie=burger : le classement d'une catégorie à la fois. */
    public function index(): void
    {
        $title = 'Classements';
        $categories = (new Ranking($this->db))->byCategory();

        // Catégorie demandée ; sinon la première qui a un classement.
        $requested = (string) ($_GET['categorie'] ?? '');
        $current = $categories[0] ?? null;
        foreach ($categories as $category) {
            if ($category['slug'] === $requested) {
                $current = $category;
                break;
            }
        }

        $maxScore = Ranking::MAX_SCORE;
        require __DIR__ . '/../views/classement.php';
    }
}
