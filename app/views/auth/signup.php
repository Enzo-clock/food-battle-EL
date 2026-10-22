<?php
// filepath: foodbattle-starter/app/views/auth/signup.php
$success ??= false;
$errors ??= [];
$csrfToken ??= '';
$email ??= '';
$username ??= '';
$firstname ??= '';
$lastname ??= '';

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="auth">
    <div class="auth__card box">
        <h1 class="auth__title">Créer un compte</h1>

        <?php if ($success): ?>
            <p class="message" role="status">Ton compte a été créé.</p>
            <a class="btn btn--primary btn--block" href="/login">Se connecter</a>
        <?php else: ?>
            <?php if ($errors): ?>
                <ul class="auth__errors" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <li><?= h($error) ?></li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>

            <form class="auth__form" method="post" action="/signup">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">

                <div class="auth__row">
                    <label>Prénom
                        <input type="text" name="firstname" autocomplete="given-name" maxlength="100" required value="<?= h($firstname) ?>">
                    </label>
                    <label>Nom
                        <input type="text" name="lastname" autocomplete="family-name" maxlength="100" required value="<?= h($lastname) ?>">
                    </label>
                </div>

                <label>Nom d’utilisateur
                    <input type="text" name="username" autocomplete="username" minlength="3" maxlength="30" required value="<?= h($username) ?>">
                </label>

                <label>Adresse e-mail
                    <input type="email" name="email" autocomplete="email" maxlength="254" required value="<?= h($email) ?>">
                </label>

                <label>Mot de passe
                    <input type="password" name="password" autocomplete="new-password" minlength="12" required>
                    <small>12 caractères minimum</small>
                </label>

                <label>Confirmer le mot de passe
                    <input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" required>
                </label>

                <button class="btn btn--primary btn--block" type="submit">Créer mon compte</button>
            </form>

            <p class="auth__switch">Déjà inscrit ? <a href="/login">Connecte-toi</a></p>
        <?php endif ?>
    </div>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>