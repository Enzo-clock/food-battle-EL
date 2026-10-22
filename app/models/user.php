<?php
// filepath: foodbattle-starter/app/models/User.php

class User
{
    public function __construct(private PDO $db) {}

    public function create(
        string $username,
        string $email,
        string $lastname,
        string $firstname,
        string $password
    ): bool {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO users
                    (username, email, lastname, firstname, password_hash)
                 VALUES
                    (:username, :email, :lastname, :firstname, :password_hash)'
            );

            $stmt->execute([
                'username' => $username,
                'email' => strtolower($email),
                'lastname' => $lastname,
                'firstname' => $firstname,
                'password_hash' => $hash,
            ]);

            return true;
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                return false;
            }

            throw $e;
        }
    }
}