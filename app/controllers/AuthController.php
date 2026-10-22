<?php
// filepath: foodbattle-starter/app/controllers/AuthController.php

require_once __DIR__ . '/../models/User.php';

class AuthController
{
    public function __construct(private PDO $db) {}

    public function signup(): void
    {
        $errors = [];
        $lastname = '';
        $firstname = '';
        $email = '';
        $username = '';
        $success = false;

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $lastname = trim($_POST['lastname'] ?? '');
            $firstname = trim($_POST['firstname'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmation = $_POST['password_confirmation'] ?? '';
            $submittedToken = $_POST['csrf_token'] ?? '';

            if (!preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $username)) {
                $errors[] = 'Le nom d’utilisateur doit contenir 3 à 30 lettres, chiffres, points, tirets ou tirets bas.';
            }

            $namePattern = "/\A\p{L}[\p{L}\p{M}]*(?:[ '\x{2019}-]\p{L}[\p{L}\p{M}]*)*\z/u";

            if (!preg_match($namePattern, $firstname)) {
                $errors[] = 'Le prénom contient des caractères invalides.';
            }

            if (!preg_match($namePattern, $lastname)) {
                $errors[] = 'Le nom contient des caractères invalides.';
            }

            if (
                !is_string($submittedToken)
                || !hash_equals($_SESSION['csrf_token'], $submittedToken)
            ) {
                $errors[] = 'Le formulaire a expiré. Recharge la page et réessaie.';
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Adresse e-mail invalide.';
            }

            if (strlen($password) < 12) {
                $errors[] = 'Le mot de passe doit contenir au moins 12 caractères.';
            }

            if ($password !== $confirmation) {
                $errors[] = 'Les mots de passe ne correspondent pas.';
            }

            if (!$errors) {
                if ((new User($this->db))->create(
                    $username,
                    $email,
                    $lastname,
                    $firstname,
                    $password
                )) {
                    $success = true;
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    $errors[] = 'Cette adresse e-mail ou ce nom d’utilisateur est déjà utilisé.';
                }
            }
        }

        $csrfToken = $_SESSION['csrf_token'];
        $title = 'Créer un compte';

        require __DIR__ . '/../views/auth/signup.php';
    }

    public function login(): void
{
    $errors = [];
    $login = '';

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        $submittedToken = $_POST['csrf_token'] ?? '';

        if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
            $errors[] = 'Le formulaire a expiré. Recharge la page et réessaie.';
        } else {
            $user = (new User($this->db))->findByLogin($login);

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']  = (int) $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                header('Location: /', true, 303);
                exit;
            }

            $errors[] = 'Identifiant ou mot de passe incorrect.';
        }
    }

    $csrfToken = $_SESSION['csrf_token'];
    $title = 'Connexion';

    require __DIR__ . '/../views/auth/login.php';
}
}
