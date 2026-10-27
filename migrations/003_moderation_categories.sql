-- Migration 003 : modération a priori des catégories.
--
-- Une catégorie crée toute une famille de duels : c'est la seule ressource qui change
-- la structure du jeu. Celle proposée par un utilisateur attend la validation d'un admin
-- (status = 'pending') ; elle est invisible et inutilisable jusque-là.
-- Les catégories existantes sont considérées comme validées.
--
-- Idempotente : peut être rejouée sans erreur.
--   Nouvelle base : exécutée automatiquement après init.sql et 002 (compose.yaml).
--   Base existante (PowerShell) :
--   Get-Content -Raw migrations/003_moderation_categories.sql | docker compose exec -T db psql -U foodbattle -d foodbattle

BEGIN;

-- Ajoutée avec 'approved' par défaut : les lignes existantes sont validées d'office…
ALTER TABLE categories
    ADD COLUMN IF NOT EXISTS status VARCHAR(10) NOT NULL DEFAULT 'approved'
        CHECK (status IN ('pending', 'approved', 'rejected'));

-- … puis toute nouvelle catégorie est « en attente » si l'application ne précise rien.
ALTER TABLE categories ALTER COLUMN status SET DEFAULT 'pending';

-- Motif du refus, lu par le créateur dans son dashboard.
ALTER TABLE categories ADD COLUMN IF NOT EXISTS rejection_reason VARCHAR(255);

-- Date de la décision (validation ou refus).
ALTER TABLE categories ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMP;

COMMIT;
