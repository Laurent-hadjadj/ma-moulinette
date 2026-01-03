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
