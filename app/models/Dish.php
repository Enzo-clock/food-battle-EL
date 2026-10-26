<?php
// filepath: app/models/Dish.php

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
        ?string $picture = null
    ): int {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO dishes (name, description, price, picture, establishment_id, creator_id)
                 VALUES (:name, :description, :price, :picture, :establishment_id, :creator_id)
                 RETURNING id'
            );
            $stmt->execute([
                'name'             => $name,
                'description'      => $description,
                'price'            => $price,
                'picture'          => $picture,
                'establishment_id' => $establishmentId,
                'creator_id'       => $creatorId,
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
}
