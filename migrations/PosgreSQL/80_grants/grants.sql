/*
####################################################
##                                                ##
##           Create TABLES                        ##
##           V2.0.0 - 30/11/2025                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire du schema
\c ma_moulinette db_user

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
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA ma_moulinette TO db_user;

-- Les futures tables héritent aussi des droits
ALTER DEFAULT PRIVILEGES IN SCHEMA ma_moulinette
    GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO db_user;


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
