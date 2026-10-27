    </main>
    <?php if (isset($_SESSION['user_id'])) require __DIR__ . '/../views/partials/dock.php'; ?>
    <footer class="site-footer">
        <div class="site-footer__inner">
            <p><strong>FoodBattle</strong> — le meilleur du quartier, selon vous.<br>
                <small>Prototype pédagogique · établissements, avis et données fictifs · photographies d’illustration</small>
            </p>
            <nav aria-label="Liens légaux">
                <a href="/mentions-legales">Mentions légales</a>
                <a href="/confidentialite">Confidentialité (RGPD)</a>
                <a href="/contact">Contact</a>
            </nav>
        </div>
    </footer>
    <script src="/assets/app.js"></script>
</body>

</html>
