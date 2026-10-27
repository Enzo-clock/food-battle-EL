<?php
// filepath: bin/make-admin.php
//
// Donne (ou retire) le rôle administrateur à un compte existant.
// Le premier admin ne peut pas être créé depuis le site : il faut un accès au serveur,
// ce qui est déjà le niveau de confiance le plus élevé. Les admins suivants peuvent
// ensuite être nommés depuis le back-office (/admin, onglet Utilisateurs).
//
// Usage (depuis la racine du projet) :
//   docker compose exec app php bin/make-admin.php john@exemple.fr
//   docker compose exec app php bin/make-admin.php john.raven --revoke
//
// Le dossier bin/ est hors de public/ : ce script n'est pas accessible par le navigateur.

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$login = $argv[1] ?? '';
$revoke = in_array('--revoke', $argv, true);

if ($login === '' || str_starts_with($login, '--')) {
    fwrite(STDERR, "Usage : php bin/make-admin.php <email ou pseudo> [--revoke]\n");
    exit(1);
}

require __DIR__ . '/../app/includes/db.php';

$stmt = $db->prepare('SELECT id, username, email, role FROM users WHERE email = :email OR username = :username');
$stmt->execute(['email' => strtolower($login), 'username' => $login]);
$user = $stmt->fetch();

if (!$user) {
    fwrite(STDERR, "Aucun compte ne correspond à « $login ». Inscris-toi d'abord sur le site.\n");
    exit(1);
}

$role = $revoke ? 'user' : 'admin';
if ($user['role'] === $role) {
    echo "{$user['username']} ({$user['email']}) a déjà le rôle « $role ». Rien à faire.\n";
    exit(0);
}

$db->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $user['id']]);
echo "{$user['username']} ({$user['email']}) a maintenant le rôle « $role ».\n";
