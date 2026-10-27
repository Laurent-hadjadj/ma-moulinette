/*
####################################################
##                                                ##
##         Mise à jour de la base de données      ##
##               V1.0.0 - 26/10/2026              ##
##                                                ##
####################################################*/

-- Laurent HADJADJ, 26/10/2026 - Ajout des colonnes Accessibilité (tag
-- SonarQube `accessibility`) sur la table historique : nombre d'anomalies,
-- dette estimée et note A-E, globale et par langage (css/web/javascript/
-- typescript), pour la tendance sur la page Suivi.
--
-- MODIF 26/10/2026 : Ce script ajoute les 15 colonnes accessibility_* d'un coup.

\c ma_moulinette postgres;

DO $$
DECLARE
    table_exists BOOLEAN;
    column_exists BOOLEAN;
BEGIN
    -- Vérifier si la table existe
    SELECT EXISTS (
        SELECT FROM
            pg_catalog.pg_tables
        WHERE
            schemaname = 'ma_moulinette' AND
            tablename	= 'historique'
    ) INTO table_exists;

    IF NOT table_exists THEN
        RAISE NOTICE 'La table ma_moulinette.historique n''existe pas.';
        RETURN;
    END IF;

    -- Vérifier si la colonne accessibility_anomalies existe
    SELECT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'ma_moulinette' AND table_name = 'historique' AND column_name = 'accessibility_anomalies'
    ) INTO column_exists;

    IF NOT column_exists THEN
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS accessibility_anomalies INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS accessibility_dette_minute INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS accessibility_rating VARCHAR(4) DEFAULT NULL;

        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS accessibility_css_anomalies INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS accessibility_css_dette_minute INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS accessibility_css_rating VARCHAR(4) DEFAULT NULL;

        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS accessibility_web_anomalies INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS accessibility_web_dette_minute INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS accessibility_web_rating VARCHAR(4) DEFAULT NULL;

        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS accessibility_javascript_anomalies INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS accessibility_javascript_dette_minute INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS accessibility_javascript_rating VARCHAR(4) DEFAULT NULL;

        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS accessibility_typescript_anomalies INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS accessibility_typescript_dette_minute INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS accessibility_typescript_rating VARCHAR(4) DEFAULT NULL;

        COMMENT ON COLUMN ma_moulinette.historique.accessibility_anomalies IS 'Accessibilité : nombre d''anomalies (OPEN, tag SonarQube accessibility), tous langages confondus.';
        COMMENT ON COLUMN ma_moulinette.historique.accessibility_dette_minute IS 'Accessibilité : dette estimée en minutes (effortTotal SonarQube), tous langages confondus.';
        COMMENT ON COLUMN ma_moulinette.historique.accessibility_rating IS 'Accessibilité : note A-E dérivée du ratio accessibility_dette_minute / (ncloc * 30), seuils SQALE 5/10/20/50%.';
        COMMENT ON COLUMN ma_moulinette.historique.accessibility_css_anomalies IS 'Accessibilité : nombre d''anomalies pour le langage CSS.';
        COMMENT ON COLUMN ma_moulinette.historique.accessibility_css_dette_minute IS 'Accessibilité : dette estimée en minutes pour le langage CSS.';
        COMMENT ON COLUMN ma_moulinette.historique.accessibility_css_rating IS 'Accessibilité : note A-E pour le langage CSS.';
        COMMENT ON COLUMN ma_moulinette.historique.accessibility_web_anomalies IS 'Accessibilité : nombre d''anomalies pour le langage Web (HTML).';
        COMMENT ON COLUMN ma_moulinette.historique.accessibility_web_dette_minute IS 'Accessibilité : dette estimée en minutes pour le langage Web (HTML).';
        COMMENT ON COLUMN ma_moulinette.historique.accessibility_web_rating IS 'Accessibilité : note A-E pour le langage Web (HTML).';
        COMMENT ON COLUMN ma_moulinette.historique.accessibility_javascript_anomalies IS 'Accessibilité : nombre d''anomalies pour le langage JavaScript.';
        COMMENT ON COLUMN ma_moulinette.historique.accessibility_javascript_dette_minute IS 'Accessibilité : dette estimée en minutes pour le langage JavaScript.';
        COMMENT ON COLUMN ma_moulinette.historique.accessibility_javascript_rating IS 'Accessibilité : note A-E pour le langage JavaScript.';
        COMMENT ON COLUMN ma_moulinette.historique.accessibility_typescript_anomalies IS 'Accessibilité : nombre d''anomalies pour le langage TypeScript.';
        COMMENT ON COLUMN ma_moulinette.historique.accessibility_typescript_dette_minute IS 'Accessibilité : dette estimée en minutes pour le langage TypeScript.';
        COMMENT ON COLUMN ma_moulinette.historique.accessibility_typescript_rating IS 'Accessibilité : note A-E pour le langage TypeScript.';

        RAISE NOTICE 'Les colonnes pour l''Accessibilité ont été ajoutées avec succès.';
    ELSE
        RAISE NOTICE 'Les colonnes pour l''Accessibilité existent déjà.';
    END IF;

    COMMIT;
END $$;
