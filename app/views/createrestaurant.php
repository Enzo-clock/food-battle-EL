<?php
// filepath: app/views/createrestaurant.php
if (!isset($errors) || !is_array($errors)) {
    $errors = [];
}

if (!isset($old) || !is_array($old)) {
    $old = [];
}

$old = array_merge(
    ['name' => '', 'description' => '', 'city' => '', 'address' => ''],
    $old
);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="section__head">
        <h1 class="section__title">Ajouter un restaurant</h1>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <p class="message" role="status">Le restaurant a bien été ajouté.</p>
    <?php endif ?>

    <?php if ($errors): ?>
        <div class="auth__errors" role="alert">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= h($error) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

    <form class="auth__form" method="post" action="/restaurant/creer" enctype="multipart/form-data">
        <input type="hidden" name="token" value="<?= h(csrf()) ?>">

        <label for="name">Nom *</label>
        <input id="name" name="name" maxlength="150" required value="<?= h($old['name']) ?>">

        <label for="description">Description</label>
        <textarea id="description" name="description"><?= h($old['description']) ?></textarea>

        <label for="picture">Image (JPG, PNG ou WebP, 5 Mo maximum)</label>
        <input id="picture" name="picture" type="file" accept="image/jpeg,image/png,image/webp">

        <label for="city">Ville *</label>
        <input id="city" name="city" maxlength="80" required value="<?= h($old['city']) ?>">

        <label for="address">Adresse *</label>
        <textarea id="address" name="address" required><?= h($old['address']) ?></textarea>

        <button class="btn btn--primary" type="submit">Enregistrer le restaurant</button>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>