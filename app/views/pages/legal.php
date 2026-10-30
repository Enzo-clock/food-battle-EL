<?php
// filepath: foodbattle-starter/app/views/pages/legal.php
// Les passages entre [crochets] sont à compléter par l'équipe.
require_once __DIR__ . '/../../includes/header.php';
?>

<article class="prose">
    <span class="pill">INFORMATIONS LÉGALES</span>
    <h1 class="prose__title">Mentions légales</h1>
    <p class="prose__lead">FoodBattle est un <strong>projet pédagogique</strong> réalisé dans le cadre de la formation
        Concepteur Développeur d’Applications. Les établissements, plats et avis présentés sont fictifs.</p>

    <section class="box prose__block" aria-labelledby="editeur">
        <h2 id="editeur">Éditeur du site</h2>
        <p>Projet réalisé par Enzo Gohard et Liamine Djellali, apprenants en formation Concepteur Développeur
            d’Applications à Simplon.</p>
        <p>Contact : <a href="/contact">page Contact</a>.</p>
        <p><strong>Directeurs de la publication :</strong> Enzo & Liamine.</p>
    </section>

    <section class="box prose__block" aria-labelledby="hebergeur">
        <h2 id="hebergeur">Hébergement</h2>
        <p>Le site n’est pas publié sur Internet : il fonctionne uniquement en environnement local (conteneurs
            Docker sur le poste des développeurs).</p>
        <p>En cas de mise en ligne, cette rubrique indiquera le nom, l’adresse et le numéro de téléphone de
            l’hébergeur, comme l’exige l’article 6 de la loi pour la confiance dans l’économie numérique (LCEN).</p>
    </section>

    <section class="box prose__block" aria-labelledby="propriete">
        <h2 id="propriete">Propriété intellectuelle</h2>
        <p>Le code source, le logo et la charte graphique FoodBattle ont été créés par l’équipe projet.</p>
        <p>Les photographies d’illustration proviennent de <a href="https://www.pexels.com/fr-fr/license/" rel="noopener">Pexels</a>,
            utilisables gratuitement selon leur licence. La police Poppins est distribuée sous licence SIL Open Font License.</p>
    </section>

    <section class="box prose__block" aria-labelledby="donnees">
        <h2 id="donnees">Données personnelles</h2>
        <p>Le traitement des données des utilisateurs est décrit dans la page
            <a href="/confidentialite">Confidentialité (RGPD)</a>.</p>
    </section>

    <p class="prose__updated">Dernière mise à jour : 29/10/2026.</p>
</article>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
