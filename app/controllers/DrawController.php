<?php
// filepath: app/controllers/DrawController.php

require_once __DIR__ . '/../models/Draw.php';

/**
 * « Mon plat du jour… ? » : le destin choisit un plat au hasard.
 *
 * GET /tirage            la page. Elle contient déjà un premier tirage, fait par le serveur :
 *                        sans JavaScript, la page fonctionne (rechargement = nouveau tirage).
 * GET /api/tirage        un nouveau tirage en JSON, pour « Retenter ma chance » sans recharger.
 *
 * Les deux acceptent ?categorie=<slug> pour tirer dans une seule catégorie.
 */
class DrawController
{
    public function __construct(private PDO $db)
    {
    }

    public function index(): void
    {
        $title = 'Mon plat du jour';
        $categories = [];
        $category = null;
        $result = null;
        $error = false;
        try {
            // Tout ce qui lit la base est ici, y compris les catégories : une panne donne un message, pas une page blanche.
            $categories = array_filter(categories(), static fn(array $c): bool => $c['dishes'] > 0);
            $category = $this->requestedCategory();
            $result = (new Draw($this->db))->draw($category);
        } catch (Throwable $e) {
            // Erreur de base ou de tirage : la page s'affiche quand même, avec un message clair.
            error_log('[tirage] ' . $e->getMessage());
            $error = true;
            http_response_code(500);
        }

        require __DIR__ . '/../views/draw.php';
    }

    public function api(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        // Jamais de cache : un tirage mis en cache renverrait le même plat à chaque essai.
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');

        try {
            $result = (new Draw($this->db))->draw($this->requestedCategory());
        } catch (Throwable $e) {
            error_log('[tirage] ' . $e->getMessage());
            $this->json(500, ['error' => 'server', 'message' => 'Le tirage n’a pas pu être fait. Réessaie dans un instant.']);
        }

        if ($result === null) {
            $this->json(404, ['error' => 'empty', 'message' => 'Aucun plat à tirer pour l’instant.']);
        }

        $this->json(200, $result);
    }

    /**
     * Catégorie demandée, si elle existe et est validée ; sinon null = toutes les catégories.
     * Une catégorie validée mais vide reste acceptée : la page dit alors qu'elle est vide.
     */
    private function requestedCategory(): ?string
    {
        $slug = (string) ($_GET['categorie'] ?? '');
        return $slug !== '' && isset(categories()[$slug]) ? $slug : null;
    }

    private function json(int $status, array $data): never
    {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
