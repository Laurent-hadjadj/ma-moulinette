/*
####################################################
##                                                ##
##         Creation des tables et des objets      ##
##               V1.0.0 - 02/11/2025              ##
##                                                ##
####################################################*/

/* #### Le script doit être lancé avec l'utilisateur propriétaire de la base, ici db_user #### */

-- SCHEMA: ma_moulinette

-- ===============================================
-- PROC_STOCK: ma_moulinette.vw_batch_profiling_stats
-- ===============================================

CREATE OR REPLACE FUNCTION ma_moulinette.purge_batch_profiling(v_limit_days INTEGER DEFAULT 90)
RETURNS INTEGER
LANGUAGE plpgsql
AS
$$
DECLARE
    v_deleted INTEGER;
BEGIN
    DELETE FROM ma_moulinette.batch_profiling
    WHERE date_execution < NOW() - (v_limit_days || ' days')::INTERVAL
    RETURNING COUNT(*) INTO v_deleted;

    RETURN v_deleted;
END;
$$;

COMMENT ON FUNCTION ma_moulinette.purge_batch_profiling IS
'Supprime les entrées de profiling plus anciennes que X jours et retourne le nombre de lignes supprimées.';

--------------------------------------------------------------------
-----                                                        -------
-----                Historique des changements              -------
-----                                                        -------
--------------------------------------------------------------------
-- 02/11/2025 : Laurent HADJADJ - Ajout d'une procédure stocké pour supprimer tous les 90 jours les données de la table batch_profiling
