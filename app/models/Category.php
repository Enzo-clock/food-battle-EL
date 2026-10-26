<?php
// filepath: app/models/Category.php

class Category
{
    public function __construct(private PDO $db)
    {
    }

    /** Toutes les catégories, triées par nom affiché : [id => slug]. */
    public function all(): array
    {
        $rows = $this->db->query('SELECT id, slug FROM categories ORDER BY name')->fetchAll();
        return array_column($rows, 'slug', 'id');
    }

    /** Crée une catégorie. Retourne son identifiant. */
    public function create(string $name, string $slug, string $color, string $picture, int $creatorId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categories (name, slug, color, picture, creator_id)
             VALUES (:name, :slug, :color, :picture, :creator_id)
             RETURNING id'
        );
        $stmt->execute([
            'name'       => $name,
            'slug'       => $slug,
            'color'      => $color,
            'picture'    => $picture,
            'creator_id' => $creatorId,
        ]);
        return (int) $stmt->fetchColumn();
    }
}
