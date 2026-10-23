<?php
// filepath: foodbattle-starter/app/views/auth/login.php
// Variables fournies par AuthController::login() : $errors, $login, $csrfToken
$errors ??= [];
$login ??= '';
$csrfToken ??= '';

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="auth">
    <div class="auth__card box">
        <h1 class="auth__title">Connexion</h1>

        <?php if ($errors): ?>
            <ul class="auth__errors" role="alert">
                <?php foreach ($errors as $error): ?>
                    <li><?= h($error) ?></li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>

        <form class="auth__form" method="post" action="/login">
            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">

            <label>E-mail ou nom d’utilisateur
                <input type="text" name="login" autocomplete="username" maxlength="254" required autofocus value="<?= h($login) ?>">
            </label>

            <label>Mot de passe
                <input type="password" name="password" autocomplete="current-password" required>
            </label>

            <button class="btn btn--primary btn--block" type="submit">Se connecter</button>
        </form>

        <p class="auth__switch">Pas encore de compte ? <a href="/signup">Inscris-toi</a></p>
    </div>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>