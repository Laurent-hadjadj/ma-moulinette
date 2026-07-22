/*
####################################################
##                                                ##
##           Create TABLES                        ##
##           V2.0.1 - 26/06/2026                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire du schema
\c ma_moulinette postgres;

------------------------------------------------------------
-- 0. Transfert de propriété (tables/sequences/vues créées par postgres,
--    cf. 20_tables/*.sql : "on passe le script en automatique avec
--    l'utilisateur postgres" depuis le 2026-06-28) vers db_user.
------------------------------------------------------------
-- MODIF 2026-07-22 : sans ce transfert, TRUNCATE ... RESTART IDENTITY
-- (ex. bin/e2e/reset-e2e-data.ps1) échoue pour db_user malgré le GRANT
-- TRUNCATE ci-dessous : RESTART IDENTITY sur une colonne GENERATED AS
-- IDENTITY exige la propriété de la table/sequence, pas seulement le droit
-- TRUNCATE. Doit s'executer ici, connecte en postgres, avant les GRANT.
-- REASSIGN OWNED BY postgres TO db_user (trop large) échoue avec "cannot
-- reassign objects owned by role postgres because they are required by the
-- database system" — on limite donc explicitement aux tables/sequences du
-- schema ma_moulinette.
DO $$
DECLARE r RECORD;
BEGIN
    FOR r IN SELECT tablename FROM pg_tables
            WHERE schemaname = 'ma_moulinette' AND tableowner = 'postgres'
    LOOP
        EXECUTE format('ALTER TABLE ma_moulinette.%I OWNER TO db_user', r.tablename);
    END LOOP;

    FOR r IN SELECT sequencename FROM pg_sequences
            WHERE schemaname = 'ma_moulinette' AND sequenceowner = 'postgres'
    LOOP
        EXECUTE format('ALTER SEQUENCE ma_moulinette.%I OWNER TO db_user', r.sequencename);
    END LOOP;
END $$;

------------------------------------------------------------
-- 1. Droits sur le schéma
------------------------------------------------------------
-- Retirer droits inutilement permissifs
REVOKE ALL ON SCHEMA ma_moulinette FROM PUBLIC;

-- Donne l'accès au rôle
GRANT USAGE ON SCHEMA ma_moulinette TO db_user;
GRANT CREATE ON SCHEMA ma_moulinette TO db_user;


------------------------------------------------------------
-- 2. Droits sur toutes les tables existantes
------------------------------------------------------------
-- MODIF 2026-07-22 : ajout de TRUNCATE — absent jusqu'ici, ce qui bloquait
-- tout TRUNCATE TABLE exécuté par db_user (ex. bin/e2e/reset-e2e-data.ps1,
-- "droit refusé pour la table historique").
GRANT SELECT, INSERT, UPDATE, DELETE, TRUNCATE ON ALL TABLES IN SCHEMA ma_moulinette TO db_user;

-- Les futures tables héritent aussi des droits
ALTER DEFAULT PRIVILEGES IN SCHEMA ma_moulinette
    GRANT SELECT, INSERT, UPDATE, DELETE, TRUNCATE ON TABLES TO db_user;


------------------------------------------------------------
-- 3. Droits sur les séquences (IDENTITY & SERIAL)
------------------------------------------------------------
GRANT USAGE, SELECT, UPDATE ON ALL SEQUENCES IN SCHEMA ma_moulinette TO db_user;

ALTER DEFAULT PRIVILEGES IN SCHEMA ma_moulinette
    GRANT USAGE, SELECT, UPDATE ON SEQUENCES TO db_user;


------------------------------------------------------------
-- 4. Droits sur les fonctions (si un jour tu en ajoutes)
------------------------------------------------------------
GRANT EXECUTE ON ALL FUNCTIONS IN SCHEMA ma_moulinette TO db_user;

ALTER DEFAULT PRIVILEGES IN SCHEMA ma_moulinette
    GRANT EXECUTE ON FUNCTIONS TO db_user;


------------------------------------------------------------
-- 5. En bonus
------------------------------------------------------------
-- Empêcher PUBLIC d'accéder par erreur
REVOKE ALL PRIVILEGES ON ALL TABLES    IN SCHEMA ma_moulinette FROM PUBLIC;
REVOKE ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA ma_moulinette FROM PUBLIC;
