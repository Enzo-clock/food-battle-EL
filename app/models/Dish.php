<?php
// filepath: app/models/Dish.php

require_once __DIR__ . '/../includes/upload.php';

class Dish
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Crée un plat et ses catégories en une seule transaction :
     * soit tout est enregistré, soit rien (pas de plat orphelin sans catégorie).
     * Retourne l'identifiant du nouveau plat.
     */
    public function create(
        string $name,
        ?string $description,
        ?string $price,
        ?int $establishmentId,
        int $creatorId,
        array $categoryIds,
        ?string $picture = null,
        string $status = 'pending' // 'approved' si un admin le crée (Moderation::initialStatus())
    ): int {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO dishes (name, description, price, picture, establishment_id, creator_id, status, reviewed_at)
                 VALUES (:name, :description, :price, :picture, :establishment_id, :creator_id, :status, :reviewed_at)
                 RETURNING id'
            );
            $stmt->execute([
                'name'             => $name,
                'description'      => $description,
                'price'            => $price,
                'picture'          => $picture,
                'establishment_id' => $establishmentId,
                'creator_id'       => $creatorId,
                'status'           => $status,
                'reviewed_at'      => $status === 'approved' ? date('Y-m-d H:i:s') : null,
            ]);
            $dishId = (int) $stmt->fetchColumn();

            $link = $this->db->prepare('INSERT INTO dish_categories (dish_id, category_id) VALUES (?, ?)');
            foreach ($categoryIds as $categoryId) {
                $link->execute([$dishId, $categoryId]);
            }

            $this->db->commit();
            return $dishId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Un plat avec tout ce qu'il faut pour le modifier ou le supprimer :
     * restaurant, catégorie, nombre de duels et matchs (« en jeu »), votes reçus.
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT d.*, e.name AS restaurant, e.city,
                    (SELECT c.slug FROM dish_categories dc
                     JOIN categories c ON c.id = dc.category_id
                     WHERE dc.dish_id = d.id ORDER BY c.id LIMIT 1) AS category,
                    (SELECT COUNT(*) FROM battles b WHERE d.id IN (b.dish_1_id, b.dish_2_id))
                    + (SELECT COUNT(*) FROM tournament_dishes td WHERE td.dish_id = d.id) AS battles,
                    (SELECT COUNT(*) FROM user_battle_votes v WHERE v.dish_choice_id = d.id) AS votes
             FROM dishes d
             LEFT JOIN establishments e ON e.id = d.establishment_id
             WHERE d.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Met à jour les champs fournis. La catégorie n'en fait jamais partie :
     * elle définit la nature du plat et ne change pas après la création.
     */
    public function update(int $id, array $fields): void
    {
        $allowed = ['name', 'description', 'price', 'establishment_id', 'picture'];
        $fields = array_intersect_key($fields, array_flip($allowed));
        if (!$fields) {
            return;
        }

        $set = implode(', ', array_map(static fn(string $f): string => "$f = :$f", array_keys($fields)));
        $stmt = $this->db->prepare("UPDATE dishes SET $set WHERE id = :id");
        $stmt->execute($fields + ['id' => $id]);
    }

    /**
     * Supprime le plat s'il n'a jamais servi, sinon l'archive.
     * C'est la base qui tranche : les clés étrangères en RESTRICT (battles, votes, tournament_dishes)
     * refusent le DELETE d'un plat en jeu. Pas de « vérifier puis supprimer » : aucune condition de course.
     * Retourne 'deleted' ou 'archived'.
     */
    public function remove(int $id): string
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('DELETE FROM dishes WHERE id = ? RETURNING picture');
            $stmt->execute([$id]);
            $picture = $stmt->fetchColumn();
            $this->db->commit();

            if (is_string($picture)) {
                delete_uploaded_picture($picture); // la photo du plat n'est partagée avec aucun autre
            }
            return 'deleted';
        } catch (PDOException $e) {
            $this->db->rollBack();
            if ($e->getCode() !== '23503') { // 23503 = violation de clé étrangère
                throw $e;
            }
        }

        $this->db->prepare('UPDATE dishes SET archived_at = CURRENT_TIMESTAMP WHERE id = ? AND archived_at IS NULL')
            ->execute([$id]);
        return 'archived';
    }

    /** Remet un plat archivé dans les listes et les duels. */
    public function restore(int $id): void
    {
        $this->db->prepare('UPDATE dishes SET archived_at = NULL WHERE id = ?')->execute([$id]);
    }
}
