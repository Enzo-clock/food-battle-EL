-- Migration 004 : modération a priori des plats et des restaurants.
--
-- Même principe que les catégories (migration 003) : ce qu'un utilisateur propose
-- attend la validation d'un admin (status = 'pending'). En attente, un plat n'entre
-- dans aucun duel et n'apparaît nulle part ; un restaurant n'est proposé dans aucune liste.
-- Les plats et restaurants existants sont considérés comme validés.
--
-- Idempotente : peut être rejouée sans erreur.
--   Nouvelle base : exécutée automatiquement après les migrations précédentes (compose.yaml).
--   Base existante (PowerShell) :
--   Get-Content -Raw migrations/004_moderation_plats_restaurants.sql | docker compose exec -T db psql -U foodbattle -d foodbattle

BEGIN;

-- Plats : 'approved' par défaut pour l'existant, puis 'pending' pour les nouvelles lignes
ALTER TABLE dishes
    ADD COLUMN IF NOT EXISTS status VARCHAR(10) NOT NULL DEFAULT 'approved'
        CHECK (status IN ('pending', 'approved', 'rejected'));
ALTER TABLE dishes ALTER COLUMN status SET DEFAULT 'pending';
ALTER TABLE dishes ADD COLUMN IF NOT EXISTS rejection_reason VARCHAR(255);
ALTER TABLE dishes ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMP;

-- Restaurants : même chose
ALTER TABLE establishments
    ADD COLUMN IF NOT EXISTS status VARCHAR(10) NOT NULL DEFAULT 'approved'
        CHECK (status IN ('pending', 'approved', 'rejected'));
ALTER TABLE establishments ALTER COLUMN status SET DEFAULT 'pending';
ALTER TABLE establishments ADD COLUMN IF NOT EXISTS rejection_reason VARCHAR(255);
ALTER TABLE establishments ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMP;

-- La vue des plats visibles exclut désormais aussi les propositions non validées.
-- Toutes les lectures publiques passent par elle (accueil, duels, votes, tournois) :
-- un seul endroit à changer, aucun oubli possible.
-- (DROP puis CREATE : la vue gagne des colonnes, et SELECT * est figé à la création.)
DROP VIEW IF EXISTS active_dishes;
CREATE VIEW active_dishes AS
    SELECT * FROM dishes WHERE archived_at IS NULL AND status = 'approved';

COMMIT;
