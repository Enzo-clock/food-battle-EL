<?php
// filepath: foodbattle-starter/app/controllers/DuelController.php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Battle.php';

class DuelController
{
    private const HISTORY_SIZE = 5;

    /** GET /duel : affiche un duel à voter, ou le résultat du vote qui vient d'avoir lieu. */
    public function show(): void
    {
        global $db;

        $battles = new Battle($db);
        $categories = categories();
        $category = $this->category($_GET['categorie'] ?? '');
        $userId = $this->userId();

        // Résultat du dernier vote (pattern Post/Redirect/Get : le flash ne s'affiche qu'une fois).
        $flash = $_SESSION['duel_flash'] ?? null;
        $error = $_SESSION['duel_error'] ?? null;
        unset($_SESSION['duel_flash'], $_SESSION['duel_error']);

        $result = null;
        $duel = null;
        if ($flash && $flash['category'] === $category) {
            $dishes = $battles->dishesInCategory($category);
            if (isset($dishes[$flash['a']], $dishes[$flash['b']])) {
                $duel = [$dishes[$flash['a']], $dishes[$flash['b']]];
                $result = $this->buildResult($battles->results($flash['battle']), $flash);
            }
        }
        if (!$duel) {
            $duel = $battles->randomDuel($category, $userId, $_SESSION['duel_skip'] ?? []);
        }
        $_SESSION['duel_skip'] = $duel ? [(int) $duel[0]['id'], (int) $duel[1]['id']] : [];

        $history = $_SESSION['duel_history'] ?? [];
        $sessionVotes = $_SESSION['duel_count'] ?? 0;
        $isLogged = $userId !== null;
        $title = 'Duel ' . category_label($category);

        require __DIR__ . '/../views/duel.php';
    }

    /** POST /duel/vote : enregistre le vote puis redirige vers le résultat. */
    public function vote(): void
    {
        global $db;

        check_post();

        $category = $this->category($_POST['categorie'] ?? '');
        $back = '/duel?' . http_build_query(['categorie' => $category]);

        $userId = $this->userId();
        if ($userId === null) {
            $_SESSION['duel_error'] = 'Connecte-toi pour voter.';
            $this->redirect($back);
        }

        $a = (int) ($_POST['a'] ?? 0);
        $b = (int) ($_POST['b'] ?? 0);
        $choice = (int) ($_POST['choice'] ?? 0);

        $battles = new Battle($db);
        $dishes = $battles->dishesInCategory($category);

        // Les identifiants viennent du navigateur : on revérifie tout côté serveur.
        $valid = $a !== $b
            && isset($dishes[$a], $dishes[$b])
            && in_array($choice, [$a, $b], true);
        if (!$valid) {
            $_SESSION['duel_error'] = 'Ce duel n’est pas valide, en voici un autre.';
            $this->redirect($back);
        }

        $battleId = $battles->findOrCreateFree($a, $b, $userId);
        $isNew = $battles->vote($userId, $battleId, $choice);

        $_SESSION['duel_flash'] = [
            'category' => $category,
            'battle'   => $battleId,
            'a'        => $a,
            'b'        => $b,
            'choice'   => $choice,
            'already'  => !$isNew,
        ];

        if ($isNew) {
            $_SESSION['duel_count'] = ($_SESSION['duel_count'] ?? 0) + 1;
            $counts = $battles->results($battleId);
            $result = $this->buildResult($counts, $_SESSION['duel_flash']);
            $loser = $choice === $a ? $b : $a;
            $_SESSION['duel_history'] ??= [];
            array_unshift($_SESSION['duel_history'], [
                'pick' => $dishes[$choice]['name'],
                'vs'   => $dishes[$loser]['name'],
                'pct'  => $result['pct'][$choice],
            ]);
            $_SESSION['duel_history'] = array_slice($_SESSION['duel_history'], 0, self::HISTORY_SIZE);
        }

        $this->redirect($back);
    }

    /** Pourcentages et verdict à partir des votes de la battle. */
    private function buildResult(array $counts, array $flash): array
    {
        $a = $flash['a'];
        $b = $flash['b'];
        $votesA = $counts[$a] ?? 0;
        $votesB = $counts[$b] ?? 0;
        $total = $votesA + $votesB;
        $pctA = $total ? (int) round($votesA * 100 / $total) : 50;

        $pick = $flash['choice'];
        $pickVotes = $pick === $a ? $votesA : $votesB;
        $otherVotes = $pick === $a ? $votesB : $votesA;

        if ($total === 1) {
            $verdict = 'Premier vote sur ce duel : tu ouvres le score !';
        } elseif ($pickVotes > $otherVotes) {
            $verdict = 'Tu es avec la majorité.';
        } elseif ($pickVotes < $otherVotes) {
            $verdict = 'Choix minoritaire, assumé !';
        } else {
            $verdict = 'Égalité parfaite pour l’instant.';
        }

        return [
            'choice'  => $pick,
            'already' => $flash['already'],
            'total'   => $total,
            'pct'     => [$a => $pctA, $b => 100 - $pctA],
            'verdict' => $verdict,
        ];
    }

    /** Catégorie demandée si elle est connue, sinon une catégorie au hasard. */
    private function category(string $requested): string
    {
        $keys = array_keys(categories());
        return in_array($requested, $keys, true) ? $requested : $keys[array_rand($keys)];
    }

    /** Utilisateur connecté (rempli par le login : $_SESSION['user_id']). */
    private function userId(): ?int
    {
        $id = $_SESSION['user_id'] ?? null;
        return is_numeric($id) ? (int) $id : null;
    }

    private function redirect(string $url): never
    {
        header('Location: ' . $url, true, 303);
        exit;
    }
}
