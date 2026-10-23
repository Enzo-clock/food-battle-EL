<?php
// filepath: app/models/Category.php

class Category
{
    public function __construct(private PDO $db)
    {
    }

    /** Toutes les catégories, triées par nom : [id => name]. */
    public function all(): array
    {
        $rows = $this->db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
        return array_column($rows, 'name', 'id');
    }
}
