/*
####################################################
##                                                ##
##         Mise à jour de la base de données      ##
##               V1.2.0 - 08/10/2026              ##
##                                                ##
####################################################*/

-- Laurent HADJADJ, 08/10/2026 - Ajout des colonnes Empreinte Écologique
-- (plugins SonarQube Creedengo) sur la table historique : nombre
-- d'anomalies et dette estimée, pour la tendance sur la page Suivi.

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

    -- Vérifier si la colonne eco_anomalies existe
    SELECT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'ma_moulinette' AND table_name = 'historique' AND column_name = 'eco_anomalies'
    ) INTO column_exists;

    IF NOT column_exists THEN
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS eco_anomalies INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS eco_dette_minute INT DEFAULT NULL;

        COMMENT ON COLUMN ma_moulinette.historique.eco_anomalies IS 'Empreinte Écologique : nombre d''anomalies (OPEN) détectées par les règles SonarQube Creedengo. Null si le plugin CREEDENGO_ENABLED est désactivé ou non collecté.';
        COMMENT ON COLUMN ma_moulinette.historique.eco_dette_minute IS 'Empreinte Écologique : dette estimée en minutes (effortTotal SonarQube) pour ces mêmes anomalies. Null dans les mêmes conditions.';

        RAISE NOTICE 'Les colonnes pour l''Empreinte Écologique ont été ajoutées avec succès.';
    ELSE
        RAISE NOTICE 'Les colonnes pour l''Empreinte Écologique existent déjà.';
    END IF;

    COMMIT;
END $$;
