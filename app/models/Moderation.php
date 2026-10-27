<?php
// filepath: app/models/Moderation.php

/**
 * Modération a priori, commune aux plats, restaurants et catégories.
 *
 * Cycle de vie d'une proposition (colonne status) :
 *   pending  → proposée par un utilisateur, invisible et inutilisable sur le site
 *   approved → validée par un admin (ou créée par un admin) : publique
 *   rejected → refusée avec un motif ; son auteur peut la corriger, elle repart en pending
 */
class Moderation
{
    /** Type (dans les URL) → table. Liste fermée : le nom de table ne vient jamais de l'utilisateur. */
    public const TABLES = [
        'plats'       => 'dishes',
        'restaurants' => 'establishments',
        'categories'  => 'categories',
    ];

    public function __construct(private PDO $db)
    {
    }

    /** Statut d'une nouvelle ressource : un admin publie directement, un utilisateur propose. */
    public static function initialStatus(): string
    {
        return is_admin() ? 'approved' : 'pending';
    }

    /**
     * Décision de l'admin sur une proposition en attente.
     * La condition status = 'pending' est dans la requête : si un autre admin a déjà tranché,
     * rien ne change et la méthode retourne false.
     */
    public function review(string $type, int $id, string $status, ?string $reason = null): bool
    {
        $table = self::TABLES[$type];
        $stmt = $this->db->prepare(
            "UPDATE $table SET status = :status, rejection_reason = :reason, reviewed_at = CURRENT_TIMESTAMP
             WHERE id = :id AND status = 'pending'"
        );
        $stmt->execute(['status' => $status, 'reason' => $reason, 'id' => $id]);
        return $stmt->rowCount() === 1;
    }

    /** Une proposition refusée puis corrigée par son auteur repart en validation. */
    public function resubmit(string $type, int $id): void
    {
        $table = self::TABLES[$type];
        $this->db->prepare(
            "UPDATE $table SET status = 'pending', rejection_reason = NULL, reviewed_at = NULL
             WHERE id = ? AND status = 'rejected'"
        )->execute([$id]);
    }

    /** Propositions en attente par type, en une requête (pastilles des onglets du back-office). */
    public function pendingCounts(): array
    {
        $row = $this->db->query(
            "SELECT
                (SELECT COUNT(*) FROM dishes WHERE status = 'pending') AS plats,
                (SELECT COUNT(*) FROM establishments WHERE status = 'pending') AS restaurants,
                (SELECT COUNT(*) FROM categories WHERE status = 'pending') AS categories"
        )->fetch();
        return array_map('intval', $row);
    }

    /** Nom affiché d'une ressource (messages de l'admin), ou null si elle n'existe pas. */
    public function name(string $type, int $id): ?string
    {
        $table = self::TABLES[$type];
        $stmt = $this->db->prepare("SELECT name FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        $name = $stmt->fetchColumn();
        return $name === false ? null : $name;
    }
}
