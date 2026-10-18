/*
####################################################
##                                                ##
##         Mise à jour de la base de données      ##
##               V1.0.0 - 16/10/2026              ##
##                                                ##
####################################################*/

-- Laurent HADJADJ, 16/10/2026 - Ajout du detail des alertes ZAP, manquant
-- depuis la phase 2 (page /zap affichait des compteurs sans la liste des
-- alertes elles-mêmes) :
--   - zap_rule.description : texte ZAP de l'alerte (desc), en plus de
--     solution/reference deja presents.
--   - zap_finding.instances : liste brute uri/methode/evidence/otherinfo
--     de chaque alerte, en plus du simple compteur instance_count.
-- Gabarit : migrations/POSTGRESQL/20_tables/{zap_rule,zap_finding}.sql
-- (fichiers d'install complet).

\c ma_moulinette postgres;

DO $$
BEGIN
    IF EXISTS (
        SELECT FROM pg_catalog.pg_tables
        WHERE schemaname = 'ma_moulinette' AND tablename = 'zap_rule'
    ) THEN
        ALTER TABLE ma_moulinette.zap_rule ADD COLUMN IF NOT EXISTS description TEXT NOT NULL DEFAULT '';
        COMMENT ON COLUMN ma_moulinette.zap_rule.description IS 'Texte ZAP de l''alerte (desc), indépendant du scan.';
        RAISE NOTICE 'zap_rule.description ajoutée (ou déjà présente).';
    ELSE
        RAISE NOTICE 'La table ma_moulinette.zap_rule n''existe pas.';
    END IF;

    IF EXISTS (
        SELECT FROM pg_catalog.pg_tables
        WHERE schemaname = 'ma_moulinette' AND tablename = 'zap_finding'
    ) THEN
        ALTER TABLE ma_moulinette.zap_finding ADD COLUMN IF NOT EXISTS instances JSONB NOT NULL DEFAULT '[]'::jsonb;
        COMMENT ON COLUMN ma_moulinette.zap_finding.instances IS 'Liste brute uri/methode/evidence/otherinfo de l''alerte.';
        RAISE NOTICE 'zap_finding.instances ajoutée (ou deja presente).';
    ELSE
        RAISE NOTICE 'La table ma_moulinette.zap_finding n''existe pas.';
    END IF;
END $$;
