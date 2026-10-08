/*
####################################################
##                                                ##
##         Mise à jour de la base de données      ##
##               V1.0.0 - 08/10/2026              ##
##                                                ##
####################################################*/

-- Laurent HADJADJ, 08/10/2026 - Ajout d'un marqueur « version du serveur
-- SonarQube » sur chaque ligne de historique. DEFAULT 'inconnu' pour satisfaire
-- le NOT NULL sur les lignes déjà en base.

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

    -- Vérifier si la colonne sonarqube_server_version existe
    SELECT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'ma_moulinette' AND table_name = 'historique' AND column_name = 'sonarqube_server_version'
    ) INTO column_exists;

    IF NOT column_exists THEN
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS sonarqube_server_version VARCHAR(32) NOT NULL DEFAULT 'inconnu';
        COMMENT ON COLUMN ma_moulinette.historique.sonarqube_server_version IS 'Version du serveur SonarQube ayant produit cette ligne.';
        RAISE NOTICE 'La colonne sonarqube_server_version a été ajoutée avec succès.';
    ELSE
        RAISE NOTICE 'La colonne sonarqube_server_version existe déjà.';
    END IF;

    COMMIT;
END $$;
