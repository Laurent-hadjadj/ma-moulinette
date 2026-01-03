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

CREATE OR REPLACE FUNCTION ma_moulinette.purge_batch_profiling(v_limit_days INTEGER DEFAULT 90)
RETURNS INTEGER
LANGUAGE plpgsql
AS
$$
DECLARE
    v_deleted INTEGER;
BEGIN
    WITH deleted AS (
        DELETE FROM ma_moulinette.batch_profiling
        WHERE date_execution < NOW() - (v_limit_days || ' days')::INTERVAL
        RETURNING 1
    )
    SELECT COUNT(*) INTO v_deleted FROM deleted;

    RETURN v_deleted;
END;
$$;
