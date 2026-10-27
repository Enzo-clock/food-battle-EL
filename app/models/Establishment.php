<?php
// filepath: app/models/Establishment.php

class Establishment
{
    public function __construct(private PDO $db)
    {
    }

    public function create(
        string $name,
        ?string $description,
        ?string $picture,
        string $city,
        string $address,
        int $creatorId
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO establishments (name, description, picture, city, address, creator_id)
             VALUES (:name, :description, :picture, :city, :address, :creator_id)
             RETURNING id'
        );

        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'picture' => $picture,
            'city' => $city,
            'address' => $address,
            'creator_id' => $creatorId,
        ]);

        return (int) $stmt->fetchColumn();
    }

    /** Liste pour un menu déroulant : [id => ['name' => …, 'city' => …]], triée par nom. */
    public function allForSelect(): array
    {
        $rows = $this->db->query('SELECT id, name, city FROM establishments ORDER BY name, city')->fetchAll();

        $list = [];
        foreach ($rows as $row) {
            $list[(int) $row['id']] = ['name' => $row['name'], 'city' => $row['city']];
        }
        return $list;
    }

    /** Un restaurant, avec son nombre de plats (archivés compris : ils y restent rattachés). */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT e.*, (SELECT COUNT(*) FROM dishes d WHERE d.establishment_id = e.id) AS dishes
             FROM establishments e WHERE e.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function update(int $id, string $name, ?string $description, string $city, string $address): void
    {
        $this->db->prepare(
            'UPDATE establishments SET name = ?, description = ?, city = ?, address = ? WHERE id = ?'
        )->execute([$name, $description, $city, $address, $id]);
    }

    /**
     * Supprime le restaurant s'il n'a aucun plat. La clé étrangère dishes.establishment_id (RESTRICT)
     * refuse sinon. Retourne false si la base a refusé.
     */
    public function delete(int $id): bool
    {
        try {
            $this->db->prepare('DELETE FROM establishments WHERE id = ?')->execute([$id]);
            return true;
        } catch (PDOException $e) {
            if ($e->getCode() === '23503') {
                return false;
            }
            throw $e;
        }
    }
}
