-- Migration 002 : archivage des plats (suppression logique).
--
-- Un plat déjà en jeu (présent dans un duel ou un tournoi) n'est jamais supprimé :
-- il est archivé. Il disparaît des listes publiques et des nouveaux duels,
-- mais reste dans l'historique (duels passés, tableaux de tournoi, votes).
--
-- Idempotente : peut être rejouée sans erreur.
--   Nouvelle base : exécutée automatiquement après init.sql (compose.yaml).
--   Base existante : docker compose exec -T db psql -U foodbattle -d foodbattle < migrations/002_archive_plats.sql

BEGIN;

ALTER TABLE dishes
    ADD COLUMN IF NOT EXISTS archived_at TIMESTAMP NULL;

-- Source unique des plats visibles : toutes les lectures publiques passent par cette vue,
-- pour qu'un oubli de filtre ne fasse jamais réapparaître un plat archivé.
CREATE OR REPLACE VIEW active_dishes AS
    SELECT * FROM dishes WHERE archived_at IS NULL;

COMMIT;
