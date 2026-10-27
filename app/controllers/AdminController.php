<?php
// filepath: app/controllers/AdminController.php
//
// Back-office (/admin). L'accès est vérifié une seule fois, dans routes/web.php, pour tout /admin/*.
// Même vue que le dashboard : l'admin retrouve les mêmes cartes et les mêmes actions,
// sur les contenus de tout le monde, avec une recherche et un onglet Utilisateurs.

require_once __DIR__ . '/DashboardController.php';
require_once __DIR__ . '/../models/Moderation.php';

class AdminController extends DashboardController
{
    /** GET /admin */
    public function index(): void
    {
        $tabs = self::tabs() + [
            'utilisateurs' => [
                'label'  => 'Utilisateurs',
                'title'  => 'Utilisateurs',
                'create' => null,
                'icon'   => '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0"/><path d="M16 4a4 4 0 0 1 0 8M22 21a7 7 0 0 0-5-6.7"/>',
            ],
        ];

        $dashboard = new Dashboard($this->db);
        $counts = $dashboard->counts(null) + ['utilisateurs' => count($dashboard->users())];
        // Pastille « à traiter » sur l'onglet : une file de modération qu'on ne voit pas n'est jamais traitée.
        $this->alerts = (new Moderation($this->db))->pendingCounts();
        $q = trim((string) ($_GET['q'] ?? ''));

        $this->render('admin', $tabs, ['user' => null, 'q' => mb_substr($q, 0, 100)], $counts);
    }

    /**
     * POST /admin/utilisateurs/{id}/role : nommer ou retirer un administrateur.
     * Garde-fou : on ne peut pas modifier son propre rôle. Celui qui agit reste donc admin,
     * et le site ne peut jamais se retrouver sans administrateur.
     */
    public function setRole(int $id): void
    {
        check_post();

        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'user';
        $return = '/admin?onglet=utilisateurs';

        if ($id === current_user_id()) {
            flash_set('Tu ne peux pas modifier ton propre rôle : demande à un autre administrateur.', 'error');
            header('Location: ' . $return, true, 303);
            exit;
        }

        $stmt = $this->db->prepare(
            "UPDATE users SET role = :role WHERE id = :id AND password_hash NOT LIKE '!%' RETURNING username"
        );
        $stmt->execute(['role' => $role, 'id' => $id]);
        $username = $stmt->fetchColumn();

        if ($username === false) {
            flash_set('Ce compte n’existe pas ou ne peut pas être modifié.', 'error');
        } else {
            flash_set($role === 'admin'
                ? $username . ' est maintenant administrateur.'
                : $username . ' n’est plus administrateur.');
        }
        header('Location: ' . $return, true, 303);
        exit;
    }

    /**
     * POST /admin/{plats|restaurants|categories}/{id}/{valider|refuser}
     * Valider : la ressource devient publique. Refuser : motif obligatoire, lu par son auteur
     * dans son dashboard ; la proposition n'est pas supprimée, il peut la corriger et la renvoyer.
     */
    public function review(string $type, int $id, string $decision): void
    {
        check_post();
        $moderation = new Moderation($this->db);
        $name = $moderation->name($type, $id) ?? abort_page(404);
        $return = '/admin?onglet=' . $type;

        if ($decision === 'valider') {
            $ok = $moderation->review($type, $id, 'approved');
            flash_set($ok ? 'Validation effectuée : « ' . $name . ' » apparaît maintenant sur le site.' : $this->alreadyReviewed($name), $ok ? 'success' : 'error');
        } else {
            $reason = trim(preg_replace('/\s+/u', ' ', (string) ($_POST['reason'] ?? '')));
            if ($reason === '' || mb_strlen($reason) > 255) {
                flash_set('Indique un motif de refus (255 caractères maximum) : il aide l’auteur à corriger sa proposition.', 'error');
            } elseif ($moderation->review($type, $id, 'rejected', $reason)) {
                flash_set('Refus enregistré pour « ' . $name . ' » : son auteur verra le motif dans son dashboard.');
            } else {
                flash_set($this->alreadyReviewed($name), 'error');
            }
        }

        header('Location: ' . $return, true, 303);
        exit;
    }

    private function alreadyReviewed(string $name): string
    {
        return 'Déjà traité : « ' . $name . ' » n’était plus en attente (un autre administrateur a tranché).';
    }
}
