/*
####################################################
##                                                ##
##         Mise à jour de la base de données      ##
##               V1.0.0 - 11/10/2026              ##
##                                                ##
####################################################*/

-- Laurent HADJADJ, 11/10/2026 - Ajout des métriques "optionnelles"
-- maintainability_issue_severity, reliability_issue_severity, security_issue_severity
-- (apparues entre SonarQube 26.3.0 et 26.9.0, pas encore documentées/LTS par
-- SonarSource) sur les tables mesures et historique. Valeur brute (INT), pas de
-- note A-E — collectées via un appel /api/measures/component séparé, non
-- bloquant (cf. BatchCollecteMesureController), donc toujours nullable.

\c ma_moulinette postgres;

DO $$
DECLARE
    table_exists BOOLEAN;
BEGIN
    -- ma_moulinette.mesures
    SELECT EXISTS (
        SELECT FROM pg_catalog.pg_tables
        WHERE schemaname = 'ma_moulinette' AND tablename = 'mesures'
    ) INTO table_exists;

    IF NOT table_exists THEN
        RAISE NOTICE 'La table ma_moulinette.mesures n''existe pas.';
    ELSE
        ALTER TABLE ma_moulinette.mesures ADD COLUMN IF NOT EXISTS maintainability_issue_severity INT;
        ALTER TABLE ma_moulinette.mesures ADD COLUMN IF NOT EXISTS reliability_issue_severity INT;
        ALTER TABLE ma_moulinette.mesures ADD COLUMN IF NOT EXISTS security_issue_severity INT;
        RAISE NOTICE 'Colonnes *_issue_severity ajoutées (ou déjà présentes) sur ma_moulinette.mesures.';
    END IF;

    -- ma_moulinette.historique
    SELECT EXISTS (
        SELECT FROM pg_catalog.pg_tables
        WHERE schemaname = 'ma_moulinette' AND tablename = 'historique'
    ) INTO table_exists;

    IF NOT table_exists THEN
        RAISE NOTICE 'La table ma_moulinette.historique n''existe pas.';
    ELSE
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS maintainability_issue_severity INT;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS reliability_issue_severity INT;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS security_issue_severity INT;
        RAISE NOTICE 'Colonnes *_issue_severity ajoutées (ou déjà présentes) sur ma_moulinette.historique.';
    END IF;

    COMMIT;
END $$;
