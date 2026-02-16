BEGIN;

-- vérifier les colonnes nullable
SELECT
    column_name,
    data_type,
    is_nullable
FROM information_schema.columns
WHERE table_schema = 'ma_moulinette'
    AND table_name = 'historique'
    AND is_nullable = 'YES'
ORDER BY ordinal_position;

ALTER TABLE ma_moulinette.historique
    ADD COLUMN IF NOT EXISTS analyse_key VARCHAR(32),
    ADD COLUMN IF NOT EXISTS todo INT,
    ADD COLUMN IF NOT EXISTS nombre_classes INT,
    ADD COLUMN IF NOT EXISTS nombre_functions INT,
    ADD COLUMN IF NOT EXISTS nombre_files INT,
    ADD COLUMN IF NOT EXISTS inconnu INT,
    ADD COLUMN IF NOT EXISTS sqale_debt_ratio DOUBLE PRECISION,
    ADD COLUMN IF NOT EXISTS menace_potentielle_reviewed_high INT,
    ADD COLUMN IF NOT EXISTS menace_potentielle_reviewed_medium INT,
    ADD COLUMN IF NOT EXISTS menace_potentielle_reviewed_low INT,
    ADD COLUMN IF NOT EXISTS logger_info INT,
    ADD COLUMN IF NOT EXISTS logger_warn INT,
    ADD COLUMN IF NOT EXISTS logger_error INT,
    ADD COLUMN IF NOT EXISTS logger_debug INT;

-- COPY ou INSERT historique

UPDATE ma_moulinette.historique
SET
    analyse_key = COALESCE(analyse_key, 'UNKNOWN'),
    todo = COALESCE(todo, 0),
    nombre_classes = COALESCE(nombre_classes, -1),
    nombre_functions = COALESCE(nombre_functions, -1),
    nombre_files = COALESCE(nombre_files, -1),
    inconnu = COALESCE(inconnu, 0),
    sqale_debt_ratio = COALESCE(sqale_debt_ratio, 0),
    menace_potentielle_reviewed_high = COALESCE(menace_potentielle_reviewed_high, 0),
    menace_potentielle_reviewed_medium = COALESCE(menace_potentielle_reviewed_medium, 0),
    menace_potentielle_reviewed_low = COALESCE(menace_potentielle_reviewed_low, 0),
    logger_info = COALESCE(logger_info, 0),
    logger_warn = COALESCE(logger_warn, 0),
    logger_error = COALESCE(logger_error, 0),
    logger_debug = COALESCE(logger_debug, 0);

DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM ma_moulinette.historique
        WHERE analyse_key IS NULL
            OR todo IS NULL
            OR nombre_classes IS NULL
            OR nombre_functions IS NULL
            OR nombre_files IS NULL
            OR inconnu IS NULL
            OR sqale_debt_ratio IS NULL
            OR menace_potentielle_reviewed_high IS NULL
            OR menace_potentielle_reviewed_medium IS NULL
            OR menace_potentielle_reviewed_low IS NULL
            OR logger_info IS NULL
            OR logger_warn IS NULL
            OR logger_error IS NULL
            OR logger_debug IS NULL
    ) THEN
        RAISE EXCEPTION 'Migration bloquée : valeurs NULL restantes';
    END IF;
END $$;

ALTER TABLE ma_moulinette.historique
    ALTER COLUMN analyse_key SET NOT NULL,
    ALTER COLUMN todo SET NOT NULL,
    ALTER COLUMN nombre_classes SET NOT NULL,
    ALTER COLUMN nombre_functions SET NOT NULL,
    ALTER COLUMN nombre_files SET NOT NULL,
    ALTER COLUMN inconnu SET NOT NULL,
    ALTER COLUMN sqale_debt_ratio SET NOT NULL,
    ALTER COLUMN menace_potentielle_reviewed_high SET NOT NULL,
    ALTER COLUMN menace_potentielle_reviewed_medium SET NOT NULL,
    ALTER COLUMN menace_potentielle_reviewed_low SET NOT NULL,
    ALTER COLUMN logger_info SET NOT NULL,
    ALTER COLUMN logger_warn SET NOT NULL,
    ALTER COLUMN logger_error SET NOT NULL,
    ALTER COLUMN logger_debug SET NOT NULL;

--ALTER TABLE ma_moulinette.historique
--    ALTER COLUMN analyse_key SET DEFAULT 'UNKNOWN',
--    ALTER COLUMN todo SET DEFAULT 0,
--    ALTER COLUMN nombre_classes SET DEFAULT -1,
--    ALTER COLUMN nombre_functions SET DEFAULT -1,
--    ALTER COLUMN nombre_files SET DEFAULT -1,
--    ALTER COLUMN inconnu SET DEFAULT 0,
--    ALTER COLUMN sqale_debt_ratio SET DEFAULT 0,
--    ALTER COLUMN menace_potentielle_reviewed_high SET DEFAULT 0,
--    ALTER COLUMN menace_potentielle_reviewed_medium SET DEFAULT 0,
--    ALTER COLUMN menace_potentielle_reviewed_low SET DEFAULT 0,
--    ALTER COLUMN logger_info SET DEFAULT 0,
--    ALTER COLUMN logger_warn SET DEFAULT 0,
--    ALTER COLUMN logger_error SET DEFAULT 0,
--    ALTER COLUMN logger_debug SET DEFAULT 0;

COMMIT;
