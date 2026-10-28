<?php

require_once __DIR__ . '/../models/Ranking.php';

class RankingController
{
    public function __construct(private PDO $db)
    {
    }

    public function index(): void
    {
        $title = 'Classements';
        $categories = (new Ranking($this->db))->byCategory();

        require __DIR__ . '/../views/classement.php';
    }
}