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

<main class="section">
    <h1>Créer un compte</h1>

    <?php if ($success): ?>
        <p class="message" role="status">
            Ton compte a été créé. La connexion sera disponible une fois la page login mise en place.
        </p>
    <?php else: ?>
        <?php if ($errors): ?>
            <ul class="message" role="alert">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>

        <form method="post" action="/signup">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
            >

            <label>
                Prénom
                <input
                    type="text"
                    name="firstname"
                    autocomplete="given-name"
                    maxlength="100"
                    required
                    value="<?= htmlspecialchars($firstname, ENT_QUOTES, 'UTF-8') ?>"
                >
            </label>

            <label>
                Nom
                <input
                    type="text"
                    name="lastname"
                    autocomplete="family-name"
                    maxlength="100"
                    required
                    value="<?= htmlspecialchars($lastname, ENT_QUOTES, 'UTF-8') ?>"
                >
            </label>

            <label>
                Nom d’utilisateur
                <input
                    type="text"
                    name="username"
                    autocomplete="username"
                    minlength="3"
                    maxlength="30"
                    required
                    value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                >
            </label>

            <label>
                Adresse e-mail
                <input
                    type="email"
                    name="email"
                    autocomplete="email"
                    maxlength="254"
                    required
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                >
            </label>

            <label>
                Mot de passe
                <input
                    type="password"
                    name="password"
                    autocomplete="new-password"
                    minlength="12"
                    required
                >
            </label>

            <label>
                Confirmer le mot de passe
                <input
                    type="password"
                    name="password_confirmation"
                    autocomplete="new-password"
                    minlength="12"
                    required
                >
            </label>

            <button type="submit">Créer mon compte</button>
        </form>
    <?php endif ?>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>