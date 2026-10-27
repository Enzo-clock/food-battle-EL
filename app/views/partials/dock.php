<?php
// filepath: foodbattle-starter/app/views/partials/dock.php
//
// Dock flottant, affiché sur toutes les pages une fois connecté (inclus par footer.php).
//   ≥ 1400 px  : pilule verticale dans la marge gauche, étiquette au survol / focus
//   701–1399 px : barre horizontale flottante en bas, étiquette au survol / focus
//   ≤ 700 px   : barre d'onglets en bas, libellé court toujours visible (pas de survol au doigt)
//
// Ordre : on joue d'abord (actions les plus fréquentes, en tête), on explore, puis on contribue.
// 'href' => null : page pas encore créée → bouton affiché mais inactif (« Bientôt »).

$dockPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$dockGroups = [
    'Jouer' => [
        [
            'href'  => '/duel/creer',
            'label' => 'Ajouter un duel',
            'short' => '+ Duel',
            // éclair : l’affrontement
            'icon'  => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
        ],
        [
            'href'  => '/tournoi/creer',
            'label' => 'Ajouter un Tournoi',
            'short' => '+ Tournoi',
            // trophée
            'icon'  => '<path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0z"/><path d="M17 6h3a3 3 0 0 1-3 4M7 6H4a3 3 0 0 0 3 4"/>',
        ],
    ],
    'Contribuer' => [
        [
            'href'  => '/plat/creer',
            'label' => is_admin() ? 'Ajouter un plat' : 'Proposer un plat',
            'short' => '+ Plat',
            // burger : un plat (le badge « + » orange indique l'ajout)
            'icon'  => '<path d="M4 11a8 7 0 0 1 16 0z"/><path d="M9.5 7.5h.01M12.5 6.5h.01M14.5 8.5h.01"/><path d="M3 14.5c1.5 0 1.5 1 3 1s1.5-1 3-1 1.5 1 3 1 1.5-1 3-1 1.5 1 3 1 1.5-1 3-1"/><path d="M4 18h16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/>',
        ],
        [
            'href'  => '/restaurant/creer',
            'label' => is_admin() ? 'Ajouter un restaurant' : 'Proposer un restaurant',
            'short' => '+ Resto',
            // devanture avec auvent
            'icon'  => '<path d="M4 10v10h16V10"/><path d="M3 10l2-6h14l2 6"/><path d="M10 20v-5h4v5"/>',
        ],
        [
            'href'  => '/categorie/creer',
            // un utilisateur propose (validation par un admin), un admin crée directement
            'label' => is_admin() ? 'Ajouter une catégorie' : 'Proposer une catégorie',
            'short' => '+ Catégorie',
            // étiquette : une catégorie
            'icon'  => '<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9z"/><path d="M7.5 7.5h.01"/>',
        ],
    ],
];
?>
<nav class="dock" aria-label="Accès rapide">
    <?php foreach ($dockGroups as $groupName => $items): ?>
        <ul class="dock__group" aria-label="<?= h($groupName) ?>">
            <?php foreach ($items as $item): ?>
                <?php
                $isCurrent = $item['href'] !== null && $dockPath === $item['href'];
                $classes = 'dock__btn dock__btn--create';
                ?>
                <li>
                    <?php if ($item['href'] !== null): ?>
                        <a class="<?= $classes ?>" href="<?= h($item['href']) ?>" <?= $isCurrent ? 'aria-current="page"' : '' ?>>
                    <?php else: ?>
                        <span class="<?= $classes ?> is-disabled" aria-disabled="true">
                    <?php endif ?>
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $item['icon'] ?></svg>
                            <span class="dock__label"><?= h($item['label']) ?><?= $item['href'] === null ? ' · bientôt' : '' ?></span>
                            <span class="dock__short" aria-hidden="true"><?= h($item['short']) ?></span>
                    <?php if ($item['href'] !== null): ?>
                        </a>
                    <?php else: ?>
                        </span>
                    <?php endif ?>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endforeach ?>
</nav>
