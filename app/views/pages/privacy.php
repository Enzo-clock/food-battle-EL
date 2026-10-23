<?php
// filepath: foodbattle-starter/app/views/pages/privacy.php
// Les passages entre [crochets] sont à compléter ou à valider par l'équipe.
require_once __DIR__ . '/../../includes/header.php';
?>

<article class="prose">
    <span class="pill">RGPD</span>
    <h1 class="prose__title">Confidentialité</h1>
    <p class="prose__lead">On collecte le strict nécessaire pour faire fonctionner ton compte et les duels.
        Pas de publicité, pas de revente, pas de traceur.</p>

    <section class="box prose__block" aria-labelledby="responsable">
        <h2 id="responsable">Responsable du traitement</h2>
        <p>L’équipe projet FoodBattle ([Prénom Nom] et [Prénom Nom]), joignable via la <a href="/contact">page Contact</a>.</p>
    </section>

    <section class="box prose__block" aria-labelledby="collecte">
        <h2 id="collecte">Données collectées</h2>
        <div class="prose__table">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Donnée</th>
                        <th scope="col">Pourquoi</th>
                        <th scope="col">Base légale</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Nom d’utilisateur, prénom, nom, adresse e-mail</td>
                        <td>Créer et identifier ton compte</td>
                        <td>Exécution du service (art. 6.1.b RGPD)</td>
                    </tr>
                    <tr>
                        <td>Mot de passe</td>
                        <td>Te connecter. Il est transformé de façon <strong>irréversible</strong> (hachage) avant d’être stocké : personne ne peut le relire, pas même nous.</td>
                        <td>Exécution du service</td>
                    </tr>
                    <tr>
                        <td>Tes votes dans les duels (plat choisi, date)</td>
                        <td>Calculer les résultats et le classement, éviter les doubles votes</td>
                        <td>Exécution du service</td>
                    </tr>
                    <tr>
                        <td>Tournois que tu crées</td>
                        <td>Les publier et les faire voter</td>
                        <td>Exécution du service</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="box prose__block" aria-labelledby="cookies">
        <h2 id="cookies">Cookies</h2>
        <p>Le site dépose un seul cookie, <code>PHPSESSID</code>, qui garde ta session ouverte et protège les
            formulaires. Il est <strong>strictement nécessaire</strong> au fonctionnement du site et supprimé à la
            fermeture du navigateur : il ne demande donc pas ton consentement.</p>
        <p>Aucun cookie publicitaire ni outil de mesure d’audience n’est utilisé.</p>
    </section>

    <section class="box prose__block" aria-labelledby="tiers">
        <h2 id="tiers">Services tiers</h2>
        <p>Pour afficher la police Poppins et les photographies, ton navigateur contacte les serveurs de
            Google Fonts et de Pexels, qui reçoivent ton adresse IP. Aucune autre donnée ne leur est transmise.</p>
    </section>

    <section class="box prose__block" aria-labelledby="duree">
        <h2 id="duree">Durée de conservation</h2>
        <p>Tes données sont conservées tant que ton compte existe. [Proposition : suppression automatique après
            3 ans d’inactivité.] Tes votes sont supprimés avec ton compte.</p>
    </section>

    <section class="box prose__block" aria-labelledby="droits">
        <h2 id="droits">Tes droits</h2>
        <p>Tu peux à tout moment <strong>accéder</strong> à tes données, les <strong>corriger</strong>, les
            <strong>supprimer</strong>, t’opposer à leur traitement ou les récupérer dans un format lisible
            (articles 15 à 21 du RGPD). Écris-nous via la <a href="/contact">page Contact</a>.</p>
        <p>Si tu estimes que tes droits ne sont pas respectés, tu peux adresser une réclamation à la
            <a href="https://www.cnil.fr/fr/plaintes" rel="noopener">CNIL</a>.</p>
    </section>

    <p class="prose__updated">Dernière mise à jour : [date].</p>
</article>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
