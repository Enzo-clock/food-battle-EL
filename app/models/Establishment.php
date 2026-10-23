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
}