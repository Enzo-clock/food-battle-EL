<?php
/** @var array $categories */
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section" aria-labelledby="classements-title">
    <h1 id="classements-title" class="section__title">Classements</h1>
    <p>
        Les plats sont classés selon la moyenne de leurs scores dans les tournois
        terminés de leur catégorie. Chaque tournoi compte autant que les autres.
    </p>

    <?php if ($categories === []): ?>
        <p class="message" role="status">
            Aucun classement n’est disponible pour le moment. Une catégorie apparaîtra
            dès qu’un de ses tournois aura au moins une participation terminée.
        </p>
    <?php else: ?>
        <?php foreach ($categories as $category): ?>
            <section aria-labelledby="categorie-<?= (int) $category['id'] ?>">
                <h2
                    id="categorie-<?= (int) $category['id'] ?>"
                    class="section__title"
                >
                    <?= h($category['name']) ?>
                </h2>

                <table>
                    <caption>
                        Classement des plats de la catégorie <?= h($category['name']) ?>
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col">Rang</th>
                            <th scope="col">Plat</th>
                            <th scope="col">Score moyen</th>
                            <th scope="col">Tournois pris en compte</th>
                            <th scope="col">Participations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($category['dishes'] as $index => $dish): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <th scope="row"><?= h($dish['name']) ?></th>
                                <td>
                                    <?= number_format(
                                        $dish['average_score'],
                                        2,
                                        ',',
                                        ' '
                                    ) ?>
                                </td>
                                <td>
                                    <?= (int) $dish['tournament_count'] ?>
                                </td>
                                <td>
                                    <?= (int) $dish['participation_count'] ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </section>
        <?php endforeach ?>
    <?php endif ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>