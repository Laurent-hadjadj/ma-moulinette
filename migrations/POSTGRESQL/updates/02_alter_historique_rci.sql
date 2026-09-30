/*
####################################################
##                                                ##
##         Mise à jour de la base de données      ##
##               V1.1.0 - 28/09/2026              ##
##                                                ##
####################################################*/

-- Laurent HADJADJ, 25/09/2026 - Ajout des colonnes RCI (Risk Criticality Index,
-- plugin sonar-rci porté en PHP) sur la table historique : RCI global +
-- RCI par module (frontend/backend/autre/inconnu, calculé par
-- BatchCollecteRciController).
-- MODIF 2026-09-28 : le calcul global a été unifié (une seule taxonomie
-- sélectionnée selon sonar.version au lieu de deux paires de colonnes
-- core/2024) -- rci_core_indicator/rci_core_rating/rci_2024_indicator/
-- rci_2024_rating (jamais peuplées, orphelines) sont remplacées par
-- rci_indicator/rci_rating.

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

    -- Vérifier si la colonne rci_ncloc existe
    SELECT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'ma_moulinette' AND table_name = 'historique' AND column_name = 'rci_ncloc'
    ) INTO column_exists;

    IF NOT column_exists THEN
        -- Ajouter les colonnes
        ALTER TABLE ma_moulinette."historique" ADD COLUMN custom_consigne01 json DEFAULT '[]'::json;
        RAISE NOTICE 'La colonne custom_consigne01 a été ajoutée avec succès.';
    ELSE
        RAISE NOTICE 'La colonne custom_consigne01 existe déjà.';
    END IF;

    -- Vérifier si la colonne custom_consigne02 existe
    SELECT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'ma_moulinette' AND table_name = 'historique' AND column_name = 'custom_consigne02'
    ) INTO column_exists;

    IF NOT column_exists THEN
        -- Ajouter la colonne custom_consigne02
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_indicator DOUBLE PRECISION DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_rating VARCHAR(4) DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_frontend_indicator DOUBLE PRECISION DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_frontend_rating VARCHAR(4) DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_backend_indicator DOUBLE PRECISION DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_backend_rating VARCHAR(4) DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_autre_indicator DOUBLE PRECISION DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_autre_rating VARCHAR(4) DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_inconnu_indicator DOUBLE PRECISION DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_inconnu_rating VARCHAR(4) DEFAULT NULL;

        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_ncloc INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_weight INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_frontend_ncloc INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_frontend_weight INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_backend_ncloc INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_backend_weight INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_autre_ncloc INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_autre_weight INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_inconnu_ncloc INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS rci_inconnu_weight INT DEFAULT NULL;

        RAISE NOTICE 'Les colonnes pour les mesures RCI ont été ajoutées avec succès.';
    ELSE
        RAISE NOTICE 'Les colonnes pour les mesures RCI existent déjà.';
    END IF;

    -- Ajout des commentaires aux colonnes
    COMMENT ON COLUMN ma_moulinette.historique.rci_indicator IS 'RCI global (Risk Criticality Index)';
    COMMENT ON COLUMN ma_moulinette.historique.rci_rating IS 'Note A-E dérivée de rci_indicator';
    COMMENT ON COLUMN ma_moulinette.historique.rci_frontend_indicator IS 'RCI du module frontend';
    COMMENT ON COLUMN ma_moulinette.historique.rci_frontend_rating IS 'Note A-E dérivée de rci_frontend_indicator';
    COMMENT ON COLUMN ma_moulinette.historique.rci_backend_indicator IS 'RCI du module backend';
    COMMENT ON COLUMN ma_moulinette.historique.rci_backend_rating IS 'Note A-E dérivée de rci_backend_indicator';
    COMMENT ON COLUMN ma_moulinette.historique.rci_autre_indicator IS 'RCI du module autre';
    COMMENT ON COLUMN ma_moulinette.historique.rci_autre_rating IS 'Note A-E dérivée de rci_autre_indicator';
    COMMENT ON COLUMN ma_moulinette.historique.rci_inconnu_indicator IS 'RCI des fichiers non classés';
    COMMENT ON COLUMN ma_moulinette.historique.rci_inconnu_rating IS 'Note A-E dérivée de rci_inconnu_indicator';

    COMMENT ON COLUMN ma_moulinette.historique.rci_ncloc IS 'Nombre de lignes de code pris en compte pour le RCI global';
    COMMENT ON COLUMN ma_moulinette.historique.rci_weight IS 'Poids pondéré des anomalies pour le RCI global';
    COMMENT ON COLUMN ma_moulinette.historique.rci_frontend_ncloc IS 'Nombre de lignes de code du module frontend';
    COMMENT ON COLUMN ma_moulinette.historique.rci_frontend_weight IS 'Poids pondéré des anomalies du module frontend';
    COMMENT ON COLUMN ma_moulinette.historique.rci_backend_ncloc IS 'Nombre de lignes de code du module backend';
    COMMENT ON COLUMN ma_moulinette.historique.rci_backend_weight IS 'Poids pondéré des anomalies du module backend';
    COMMENT ON COLUMN ma_moulinette.historique.rci_autre_ncloc IS 'Nombre de lignes de code du module autre';
    COMMENT ON COLUMN ma_moulinette.historique.rci_autre_weight IS 'Poids pondéré des anomalies du module autre';
    COMMENT ON COLUMN ma_moulinette.historique.rci_inconnu_ncloc IS 'Nombre de lignes de code des fichiers non classés';
    COMMENT ON COLUMN ma_moulinette.historique.rci_inconnu_weight IS 'Poids pondéré des anomalies des fichiers non classés';

    RAISE NOTICE 'Les commentaires ont été ajoutés aux colonnes des attributs RCI.';

    COMMIT;
END $$;
