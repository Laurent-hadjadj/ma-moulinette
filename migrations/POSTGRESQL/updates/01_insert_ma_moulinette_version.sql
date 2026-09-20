/*
####################################################
##                                                ##
##         Mise à jour de la base de données      ##
##               V1.0.0 - 20/09/2026              ##
##                                                ##
####################################################*/

-- Laurent HADJADJ, 20/09/2026 - 1.0.0 version initiale ;

\c ma_moulinette postgres;

DO $$
DECLARE
    table_exists BOOLEAN;
    data_exists BOOLEAN;
    record RECORD;
BEGIN
    -- Vérifier si la table existe
    SELECT EXISTS (
        SELECT FROM
            pg_catalog.pg_tables
        WHERE
            schemaname = 'ma_moulinette' AND
            tablename  = 'ma_moulinette'
    ) INTO table_exists;

    IF NOT table_exists THEN
        RAISE NOTICE 'Table ma_moulinette n’existe pas.';
        RETURN;
    END IF;

    -- Données à insérer
    FOR record IN (
        SELECT * FROM (VALUES
            ('2.0.1', '2026-09-13'),
            ('2.0.2', '2026-09-19'),
            ('2.1.0', '2026-09-20')
        ) AS t(version, date_str)
    ) LOOP
        -- Vérifier si les données existent déjà
        SELECT EXISTS (
            SELECT 1 FROM ma_moulinette.ma_moulinette WHERE version = record.version AND date_version = record.date_str::timestamp
        ) INTO data_exists;

        IF NOT data_exists THEN
            -- Insérer les données
            INSERT INTO ma_moulinette.ma_moulinette (version, date_version, date_enregistrement)
            VALUES (record.version, record.date_str::timestamp, NOW());
            RAISE NOTICE 'Les données pour la version % (%) ont été insérées correctement.', record.version, record.date_str::timestamp;
        ELSE
            RAISE NOTICE 'Les données pour la version % (%) existent déjà.', record.version, record.date_str::timestamp;
        END IF;
    END LOOP;
END $$;
