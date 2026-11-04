/*
####################################################
##                                                ##
##         Mise à jour de la base de données      ##
##               V1.3.0 - 02/11/2026              ##
##                                                ##
####################################################*/

-- Laurent HADJADJ, 08/10/2026 - Ajout des colonnes Empreinte Écologique
-- (plugins SonarQube Creedengo) sur la table historique : nombre
-- d'anomalies et dette estimée, pour la tendance sur la page Suivi.
--
-- MODIF 02/11/2026 : BUG CORRIGÉ — ce script n'ajoutait que 2 des 15
-- colonnes eco_* (eco_anomalies/eco_dette_minute), alors que l'install
-- complet (20_tables/historique.sql) les a toutes depuis l'origine (note
-- globale eco_rating + détail par langage java/javascript/php/python, 3
-- colonnes chacun). Toute base peuplée via ce seul script idempotent (pas
-- via un install complet) se retrouvait donc sans eco_rating ni les 12
-- colonnes par langage — confirmé le 02/11/2026 par le message
-- SQLSTATE[42703] "column eco_rating does not exist" sur la page Suivi,
-- requête selectUnionHistoriqueProjet. Les 13 colonnes manquantes sont
-- ajoutées ici ; le test d'existence porte maintenant sur eco_rating (la
-- colonne manquante constatée), pas sur eco_anomalies (déjà présente sur
-- les bases déjà partiellement migrées, ce qui faisait sauter tout le
-- bloc sans jamais ajouter le reste).

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

    -- Vérifier si la colonne eco_rating existe (cf. note ci-dessus : pas
    -- eco_anomalies, déjà présente sur les bases partiellement migrées).
    SELECT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'ma_moulinette' AND table_name = 'historique' AND column_name = 'eco_rating'
    ) INTO column_exists;

    IF NOT column_exists THEN
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS eco_anomalies INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS eco_dette_minute INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS eco_rating VARCHAR(4) DEFAULT NULL;

        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS eco_java_anomalies INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS eco_java_dette_minute INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS eco_java_rating VARCHAR(4) DEFAULT NULL;

        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS eco_javascript_anomalies INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS eco_javascript_dette_minute INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS eco_javascript_rating VARCHAR(4) DEFAULT NULL;

        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS eco_php_anomalies INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS eco_php_dette_minute INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS eco_php_rating VARCHAR(4) DEFAULT NULL;

        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS eco_python_anomalies INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS eco_python_dette_minute INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.historique ADD COLUMN IF NOT EXISTS eco_python_rating VARCHAR(4) DEFAULT NULL;

        COMMENT ON COLUMN ma_moulinette.historique.eco_anomalies IS 'Empreinte Écologique : nombre d''anomalies (OPEN) détectées par les règles SonarQube Creedengo, tous langages confondus. Null si le plugin CREEDENGO_ENABLED est désactivé ou non collecté.';
        COMMENT ON COLUMN ma_moulinette.historique.eco_dette_minute IS 'Empreinte Écologique : dette estimée en minutes (effortTotal SonarQube), tous langages confondus. Null dans les mêmes conditions.';
        COMMENT ON COLUMN ma_moulinette.historique.eco_rating IS 'Empreinte Écologique : note A-E dérivée du ratio eco_dette_minute / (ncloc * 30), seuils SQALE 5/10/20/50%.';
        COMMENT ON COLUMN ma_moulinette.historique.eco_java_anomalies IS 'Empreinte Écologique : nombre d''anomalies pour le langage Java.';
        COMMENT ON COLUMN ma_moulinette.historique.eco_java_dette_minute IS 'Empreinte Écologique : dette estimée en minutes pour le langage Java.';
        COMMENT ON COLUMN ma_moulinette.historique.eco_java_rating IS 'Empreinte Écologique : note A-E pour le langage Java.';
        COMMENT ON COLUMN ma_moulinette.historique.eco_javascript_anomalies IS 'Empreinte Écologique : nombre d''anomalies pour le langage JavaScript.';
        COMMENT ON COLUMN ma_moulinette.historique.eco_javascript_dette_minute IS 'Empreinte Écologique : dette estimée en minutes pour le langage JavaScript.';
        COMMENT ON COLUMN ma_moulinette.historique.eco_javascript_rating IS 'Empreinte Écologique : note A-E pour le langage JavaScript.';
        COMMENT ON COLUMN ma_moulinette.historique.eco_php_anomalies IS 'Empreinte Écologique : nombre d''anomalies pour le langage PHP.';
        COMMENT ON COLUMN ma_moulinette.historique.eco_php_dette_minute IS 'Empreinte Écologique : dette estimée en minutes pour le langage PHP.';
        COMMENT ON COLUMN ma_moulinette.historique.eco_php_rating IS 'Empreinte Écologique : note A-E pour le langage PHP.';
        COMMENT ON COLUMN ma_moulinette.historique.eco_python_anomalies IS 'Empreinte Écologique : nombre d''anomalies pour le langage Python.';
        COMMENT ON COLUMN ma_moulinette.historique.eco_python_dette_minute IS 'Empreinte Écologique : dette estimée en minutes pour le langage Python.';
        COMMENT ON COLUMN ma_moulinette.historique.eco_python_rating IS 'Empreinte Écologique : note A-E pour le langage Python.';

        RAISE NOTICE 'Les colonnes pour l''Empreinte Écologique ont été ajoutées avec succès.';
    ELSE
        RAISE NOTICE 'Les colonnes pour l''Empreinte Écologique existent déjà.';
    END IF;

    COMMIT;
END $$;
