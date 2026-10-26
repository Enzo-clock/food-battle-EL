<?php
// filepath: app/controllers/TournamentCreationController.php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Tournament.php';

/**
 * GET/POST /tournoi/creer : un tournoi réunit 4 duels libres de l'utilisateur,
 * d'une même catégorie et sans plat en commun. Ils deviennent les quarts de finale,
 * un match par jour à partir de la date de début.
 */
class TournamentCreationController
{
    public const QUARTERS = 4;
    private const NAME_MAX = 150; // tournaments.name VARCHAR(150)

    public function __construct(private PDO $db)
    {
    }

    public function create(): void
    {
        // Comme les autres pages de création : on renvoie vers la connexion.
        if (empty($_SESSION['user_id'])) {
            header('Location: /login', true, 303);
            exit;
        }

        $userId = (int) $_SESSION['user_id'];
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $tournament = new Tournament($this->db);
        $categories = categories();

        // Message de succès après redirection (pattern PRG)
        $success = $_SESSION['tournament_created'] ?? null;
        unset($_SESSION['tournament_created']);

        // Duels disponibles, et combien par catégorie (pour les pastilles)
        $battles = $tournament->availableBattles($userId);
        $duelCounts = array_fill_keys(array_keys($categories), 0);
        foreach ($battles as $battle) {
            foreach ($battle['shared'] as $slug) {
                if (isset($duelCounts[$slug])) {
                    $duelCounts[$slug]++;
                }
            }
        }
        $duelCounts = array_filter($duelCounts); // seules les catégories où l'utilisateur a des duels

        $errors = [];
        $today = date('Y-m-d');
        $old = [
            'name'       => '',
            'start_date' => date('Y-m-d', strtotime('+1 day')),
            'battle_ids' => [],
            // ?categorie=slug, sinon la catégorie où l'utilisateur a le plus de duels
            'category'   => (string) ($_GET['categorie'] ?? ''),
        ];
        if (!isset($duelCounts[$old['category']])) {
            arsort($duelCounts);
            $old['category'] = (string) (array_key_first($duelCounts) ?? '');
            ksort($duelCounts);
        }

        if ($method === 'POST') {
            check_post();

            $old['name'] = trim((string) ($_POST['name'] ?? ''));
            $old['start_date'] = (string) ($_POST['start_date'] ?? '');
            $old['category'] = (string) ($_POST['category'] ?? '');
            $old['battle_ids'] = array_values(array_unique(array_filter(
                array_map('intval', (array) ($_POST['battle_ids'] ?? [])),
                static fn(int $id): bool => $id > 0
            )));

            if (!isset($categories[$old['category']])) {
                $errors[] = 'Choisis la catégorie du tournoi.';
            }

            if (count($old['battle_ids']) !== self::QUARTERS) {
                $errors[] = 'Choisis exactement ' . self::QUARTERS . ' duels pour les quarts de finale.';
            }

            if ($old['name'] === '') {
                $errors[] = 'Donne un nom au tournoi.';
            } elseif (mb_strlen($old['name']) > self::NAME_MAX) {
                $errors[] = 'Le nom ne doit pas dépasser ' . self::NAME_MAX . ' caractères.';
            }

            $start = DateTimeImmutable::createFromFormat('!Y-m-d', $old['start_date']);
            if (!$start || $start->format('Y-m-d') !== $old['start_date']) {
                $errors[] = 'La date de début est invalide.';
            } elseif ($old['start_date'] < $today) {
                $errors[] = 'La date de début ne peut pas être passée.';
            }

            if (!$errors) {
                try {
                    // Le modèle revérifie tout dans une transaction : duels à l'utilisateur,
                    // encore libres, de la catégorie, et 8 plats différents.
                    $tournamentId = $tournament->createWithQuarterfinals(
                        $old['name'],
                        $old['start_date'],
                        $userId,
                        $old['category'],
                        $old['battle_ids']
                    );

                    $_SESSION['tournament_created'] = [
                        'id'         => $tournamentId,
                        'name'       => $old['name'],
                        'start_date' => $old['start_date'],
                    ];
                    header('Location: /tournoi/creer', true, 303);
                    exit;
                } catch (InvalidArgumentException $e) {
                    $errors[] = $e->getMessage();
                } catch (Throwable $e) {
                    error_log($e->getMessage());
                    $errors[] = 'La création du tournoi a échoué. Réessaie.';
                }
            }
        }

        $quarters = self::QUARTERS;
        $title = 'Créer un tournoi';
        require __DIR__ . '/../views/createtournament.php';
    }
}
