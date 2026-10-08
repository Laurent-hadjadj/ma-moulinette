/*
####################################################
##                                                ##
##         Mise à jour de la base de données      ##
##               V1.0.0 - 06/10/2026              ##
##                                                ##
####################################################*/

-- Laurent HADJADJ, 06/10/2026 - Ajout de la répartition par scope SonarQube
-- (MAIN = code source, TEST = code de test) sur la table anomalie, globale et
-- par catégorie (bug/vulnerability/code_smell). DEFAULT 0 pour satisfaire le
-- NOT NULL sur les lignes déjà en base : la prochaine collecte (delete puis
-- insert) remplacera ces lignes avec les vraies valeurs.

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
            tablename	= 'anomalie'
    ) INTO table_exists;

    IF NOT table_exists THEN
        RAISE NOTICE 'La table ma_moulinette.anomalie n''existe pas.';
        RETURN;
    END IF;

    -- Vérifier si la colonne scope_main existe
    SELECT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'ma_moulinette' AND table_name = 'anomalie' AND column_name = 'scope_main'
    ) INTO column_exists;

    IF NOT column_exists THEN
        -- Ajouter les colonnes
        ALTER TABLE ma_moulinette.anomalie ADD COLUMN IF NOT EXISTS scope_main INT NOT NULL DEFAULT 0;
        ALTER TABLE ma_moulinette.anomalie ADD COLUMN IF NOT EXISTS scope_test INT NOT NULL DEFAULT 0;
        ALTER TABLE ma_moulinette.anomalie ADD COLUMN IF NOT EXISTS bug_scope_main INT NOT NULL DEFAULT 0;
        ALTER TABLE ma_moulinette.anomalie ADD COLUMN IF NOT EXISTS bug_scope_test INT NOT NULL DEFAULT 0;
        ALTER TABLE ma_moulinette.anomalie ADD COLUMN IF NOT EXISTS vulnerability_scope_main INT NOT NULL DEFAULT 0;
        ALTER TABLE ma_moulinette.anomalie ADD COLUMN IF NOT EXISTS vulnerability_scope_test INT NOT NULL DEFAULT 0;
        ALTER TABLE ma_moulinette.anomalie ADD COLUMN IF NOT EXISTS code_smell_scope_main INT NOT NULL DEFAULT 0;
        ALTER TABLE ma_moulinette.anomalie ADD COLUMN IF NOT EXISTS code_smell_scope_test INT NOT NULL DEFAULT 0;

        RAISE NOTICE 'Les colonnes pour les mesures scopes ont été ajoutées avec succès.';
    ELSE
        RAISE NOTICE 'Les colonnes pour les mesures scopes existent déjà.';
    END IF;

    IF NOT column_exists THEN
        -- Ajout des commentaires aux colonnes
        COMMENT ON COLUMN ma_moulinette.anomalie.scope_main IS 'Nombre total de problèmes sur le code source (scope MAIN)';
        COMMENT ON COLUMN ma_moulinette.anomalie.scope_test IS 'Nombre total de problèmes sur le code de test (scope TEST)';
        COMMENT ON COLUMN ma_moulinette.anomalie.bug_scope_main IS 'Nombre de bugs sur le code source (scope MAIN)';
        COMMENT ON COLUMN ma_moulinette.anomalie.bug_scope_test IS 'Nombre de bugs sur le code de test (scope TEST)';
        COMMENT ON COLUMN ma_moulinette.anomalie.vulnerability_scope_main IS 'Nombre de vulnérabilités sur le code source (scope MAIN)';
        COMMENT ON COLUMN ma_moulinette.anomalie.vulnerability_scope_test IS 'Nombre de vulnérabilités sur le code de test (scope TEST)';
        COMMENT ON COLUMN ma_moulinette.anomalie.code_smell_scope_main IS 'Nombre de mauvaises pratiques sur le code source (scope MAIN)';
        COMMENT ON COLUMN ma_moulinette.anomalie.code_smell_scope_test IS 'Nombre de mauvaises pratiques sur le code de test (scope TEST)';
        RAISE NOTICE 'Les commentaires ont été ajoutés aux colonnes des attributs scopes.';
    ELSE
        RAISE NOTICE 'Les colonnes pour les mesures scopes existent déjà.';
    END IF;

    COMMIT;
END $$;
