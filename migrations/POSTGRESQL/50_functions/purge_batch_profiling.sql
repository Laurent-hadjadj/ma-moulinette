/*
####################################################
##                                                ##
##           Create FUNCTIONS                     ##
##           V2.0.1 - 03/05/2026                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18
--- 2026-05-03 : Correction du titre d'en-tête (était "Create TABLES").

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire du schema
\c ma_moulinette;

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
