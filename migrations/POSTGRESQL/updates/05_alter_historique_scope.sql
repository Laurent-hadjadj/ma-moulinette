/*
####################################################
##                                                ##
##         Mise à jour de la base de données      ##
##               V2.2.0 - 06/10/2026               ##
##                                                ##
####################################################*/

-- Laurent HADJADJ, 06/10/2026 - Ajout de la répartition par portée SonarQube
-- (MAIN = code source, TEST = code de test) sur la table historique, globale
-- et par catégorie (bug/vulnerability/code_smell), en miroir des colonnes
-- déjà ajoutées sur la table anomalie (updates/04_alter_anomalie_scope.sql).

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

    -- Vérifier si la colonne scope_main existe
    SELECT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'ma_moulinette' AND table_name = 'historique' AND column_name = 'scope_main'
    ) INTO column_exists;

    IF NOT column_exists THEN
        -- Ajouter les colonnes
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS scope_main INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS scope_test INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS bug_scope_main INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS bug_scope_test INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS vulnerability_scope_main INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS vulnerability_scope_test INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS code_smell_scope_main INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS code_smell_scope_test INT DEFAULT NULL;
        RAISE NOTICE 'Les colonnes pour les mesures scopes ont été ajoutées avec succès.';
    ELSE
        RAISE NOTICE 'Les colonnes pour les mesures scopes existent déjà.';
    END IF;

    IF NOT column_exists THEN
        -- Ajout des commentaires aux colonnes
        COMMENT ON COLUMN ma_moulinette.historique.scope_main IS 'Nombre total de problèmes sur le code source (scope MAIN)';
        COMMENT ON COLUMN ma_moulinette.historique.scope_test IS 'Nombre total de problèmes sur le code de test (scope TEST)';
        COMMENT ON COLUMN ma_moulinette.historique.bug_scope_main IS 'Nombre de bugs sur le code source (scope MAIN)';
        COMMENT ON COLUMN ma_moulinette.historique.bug_scope_test IS 'Nombre de bugs sur le code de test (scope TEST)';
        COMMENT ON COLUMN ma_moulinette.historique.vulnerability_scope_main IS 'Nombre de vulnérabilités sur le code source (scope MAIN)';
        COMMENT ON COLUMN ma_moulinette.historique.vulnerability_scope_test IS 'Nombre de vulnérabilités sur le code de test (scope TEST)';
        COMMENT ON COLUMN ma_moulinette.historique.code_smell_scope_main IS 'Nombre de mauvaises pratiques sur le code source (scope MAIN)';
        COMMENT ON COLUMN ma_moulinette.historique.code_smell_scope_test IS 'Nombre de mauvaises pratiques sur le code de test (scope TEST)';
        RAISE NOTICE 'Les commentaires ont été ajoutés aux colonnes des attributs scopes.';
    ELSE
        RAISE NOTICE 'Les commentaires pour les mesures scopes existent déjà.';
    END IF;

    COMMIT;
END $$;
