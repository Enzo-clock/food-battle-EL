<?php
// filepath: app/models/Category.php

class Category
{
    public function __construct(private PDO $db)
    {
    }

    /** Catégories validées (les seules où l'on peut ranger un plat), triées par nom : [id => slug]. */
    public function all(): array
    {
        $rows = $this->db->query("SELECT id, slug FROM categories WHERE status = 'approved' ORDER BY name")->fetchAll();
        return array_column($rows, 'slug', 'id');
    }

    /**
     * Crée une catégorie. Retourne son identifiant.
     * $status : 'pending' (proposée par un utilisateur, à valider) ou 'approved' (créée par un admin).
     */
    public function create(string $name, string $slug, string $color, string $picture, int $creatorId, string $status = 'pending'): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categories (name, slug, color, picture, creator_id, status, reviewed_at)
             VALUES (:name, :slug, :color, :picture, :creator_id, :status, :reviewed_at)
             RETURNING id'
        );
        $stmt->execute([
            'name'        => $name,
            'slug'        => $slug,
            'color'       => $color,
            'picture'     => $picture,
            'creator_id'  => $creatorId,
            'status'      => $status,
            'reviewed_at' => $status === 'approved' ? date('Y-m-d H:i:s') : null,
        ]);
        return (int) $stmt->fetchColumn();
    }

    /** Une catégorie, avec son nombre de plats (archivés compris). */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, (SELECT COUNT(*) FROM dish_categories dc WHERE dc.category_id = c.id) AS dishes
             FROM categories c WHERE c.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Le slug ne change pas quand on renomme : il sert dans les URL (?categorie=burger),
     * les liens déjà partagés continuent de fonctionner.
     */
    /**
     * $slug : seulement pour une proposition (jamais publiée, son adresse n'a pas pu être partagée).
     * Une catégorie validée garde son slug, même renommée.
     */
    public function update(int $id, string $name, string $color, string $picture, ?string $slug = null): void
    {
        $this->db->prepare('UPDATE categories SET name = ?, color = ?, picture = ?, slug = COALESCE(?, slug) WHERE id = ?')
            ->execute([$name, $color, $picture, $slug, $id]);
    }

    /** Une proposition refusée puis corrigée par son créateur repart en validation. */
    public function resubmit(int $id): void
    {
        $this->db->prepare(
            "UPDATE categories SET status = 'pending', rejection_reason = NULL, reviewed_at = NULL
             WHERE id = ? AND status = 'rejected'"
        )->execute([$id]);
    }

    /**
     * Décision de l'admin sur une proposition en attente.
     * Retourne false si elle n'était plus en attente (déjà traitée par un autre admin).
     */
    public function review(int $id, string $status, ?string $reason = null): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE categories SET status = :status, rejection_reason = :reason, reviewed_at = CURRENT_TIMESTAMP
             WHERE id = :id AND status = 'pending'"
        );
        $stmt->execute(['status' => $status, 'reason' => $reason, 'id' => $id]);
        return $stmt->rowCount() === 1;
    }

    /** Nombre de propositions en attente (pastille de l'onglet Catégories du back-office). */
    public function pendingCount(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM categories WHERE status = 'pending'")->fetchColumn();
    }

    /**
     * Supprime la catégorie si elle n'a aucun plat : dish_categories.category_id (RESTRICT) refuse sinon.
     * Retourne la photo supprimée (à effacer du disque), ou false si la base a refusé.
     */
    public function delete(int $id): string|false
    {
        try {
            $stmt = $this->db->prepare('DELETE FROM categories WHERE id = ? RETURNING picture');
            $stmt->execute([$id]);
            return (string) $stmt->fetchColumn();
        } catch (PDOException $e) {
            if ($e->getCode() === '23503') {
                return false;
            }
            throw $e;
        }
    }
}
