<?php
// filepath: app/views/partials/danger-zone.php
// Bas des pages de modification : ce que produirait une suppression, et le lien vers sa confirmation.
// Variables : $plan (deletion_plan()), $deleteUrl, $what (« ce plat », « ce restaurant »…)
if (!$plan) {
    return;
}
?>
<section class="box danger-zone" aria-labelledby="danger-title">
    <div>
        <h2 class="danger-zone__title" id="danger-title"><?= h($plan['label']) ?> <?= h($what) ?></h2>
        <p><?= h($plan['message']) ?></p>
    </div>
    <?php if ($plan['mode'] !== 'blocked'): ?>
        <a class="btn btn--sm <?= $plan['mode'] === 'delete' ? 'btn--danger' : 'btn--dark' ?>" href="<?= h($deleteUrl) ?>"><?= h($plan['label']) ?></a>
    <?php endif ?>
</section>
