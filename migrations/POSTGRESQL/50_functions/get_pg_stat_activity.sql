/*
####################################################
##                                                ##
##           Create TABLES                        ##
##           V2.1.0 - 26/04/2026                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18
--- 2026-04-26 : Modification de l'utilisateur propriétaire de la fonction et ajout des droits d'exécution pour postgres

-- ⚠️ Ce script doit être lancé avec un superuser (ex: postgres)
-- car on définit l'ownership de la fonction à postgres (SECURITY DEFINER).
\c ma_moulinette postgres

CREATE OR REPLACE FUNCTION get_pg_stat_activity()
RETURNS SETOF pg_catalog.pg_stat_activity
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
    RETURN QUERY
        SELECT *
        FROM pg_catalog.pg_stat_activity
        WHERE datname = 'ma_moulinette';
END $$;

-- On définit explicitement le propriétaire (superuser)
ALTER FUNCTION get_pg_stat_activity() OWNER TO postgres;

-- On donne l'accès uniquement à l'utilisateur applicatif
GRANT EXECUTE ON FUNCTION get_pg_stat_activity() TO db_user;
