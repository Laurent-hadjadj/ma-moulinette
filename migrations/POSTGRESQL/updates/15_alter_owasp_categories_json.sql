/*
####################################################
##                                                ##
##         Mise à jour de la base de données      ##
##               V1.0.0 - 27/10/2026              ##
##                                                ##
####################################################*/

-- Laurent HADJADJ, 27/10/2026 - Remplace les 60 colonnes a1..a10/aN_blocker..minor
-- + source de la table owasp (snapshot, toujours réécrite en entier à chaque
-- collecte, aucune donnée historique à préserver) par une seule colonne JSONB
-- `categories` (classification désormais faite localement par
-- OwaspRuleClassifierService, cf. BatchCollecteOwaspController et
-- mkDocs/docs/ma-moulinette/application/owasp.md), et ajoute module_frontend/
-- backend/autre (répartition par module des vulnérabilités OWASP, remplace
-- l'ancienne répartition basée sur les hotspots).
--
-- ⚠️ DROP COLUMN inhabituel dans ce projet : exception
-- documentée ici, `owasp` est une table snapshot (deleteOwaspMavenKey puis
-- insertOwasp à chaque collecte), aucune perte de donnée réelle.

\c ma_moulinette postgres;

DO $$
DECLARE
    table_exists BOOLEAN;
    column_exists BOOLEAN;
BEGIN
    SELECT EXISTS (
        SELECT FROM pg_catalog.pg_tables
        WHERE schemaname = 'ma_moulinette' AND tablename = 'owasp'
    ) INTO table_exists;

    IF NOT table_exists THEN
        RAISE NOTICE 'La table ma_moulinette.owasp n''existe pas.';
        RETURN;
    END IF;

    SELECT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'ma_moulinette' AND table_name = 'owasp' AND column_name = 'categories'
    ) INTO column_exists;

    IF NOT column_exists THEN
        ALTER TABLE ma_moulinette.owasp ADD COLUMN IF NOT EXISTS categories JSONB NOT NULL DEFAULT '{}'::jsonb;
        ALTER TABLE ma_moulinette.owasp ADD COLUMN IF NOT EXISTS module_frontend INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.owasp ADD COLUMN IF NOT EXISTS module_backend INT DEFAULT NULL;
        ALTER TABLE ma_moulinette.owasp ADD COLUMN IF NOT EXISTS module_autre INT DEFAULT NULL;

        COMMENT ON COLUMN ma_moulinette.owasp.categories IS 'Compteurs par catégorie A1..A10 (JSON, sparse) : total/blocker/critical/major/minor/info/source (core|findsecbugs|mixed) — cf. OwaspRuleClassifierService::classify().';
        COMMENT ON COLUMN ma_moulinette.owasp.module_frontend IS 'Nombre de vulnérabilités OWASP classées module frontend.';
        COMMENT ON COLUMN ma_moulinette.owasp.module_backend IS 'Nombre de vulnérabilités OWASP classées module backend.';
        COMMENT ON COLUMN ma_moulinette.owasp.module_autre IS 'Nombre de vulnérabilités OWASP classées module autre (ou non classées).';

        -- Table snapshot : les anciennes colonnes n'ont plus d'utilité, aucune
        -- donnée à migrer (la prochaine collecte réécrit entièrement la table).
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS source;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a1;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a2;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a3;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a4;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a5;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a6;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a7;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a8;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a9;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a10;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a1_blocker;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a1_critical;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a1_major;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a1_info;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a1_minor;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a2_blocker;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a2_critical;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a2_major;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a2_info;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a2_minor;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a3_blocker;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a3_critical;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a3_major;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a3_info;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a3_minor;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a4_blocker;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a4_critical;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a4_major;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a4_info;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a4_minor;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a5_blocker;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a5_critical;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a5_major;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a5_info;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a5_minor;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a6_blocker;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a6_critical;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a6_major;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a6_info;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a6_minor;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a7_blocker;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a7_critical;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a7_major;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a7_info;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a7_minor;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a8_blocker;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a8_critical;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a8_major;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a8_info;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a8_minor;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a9_blocker;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a9_critical;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a9_major;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a9_info;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a9_minor;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a10_blocker;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a10_critical;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a10_major;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a10_info;
        ALTER TABLE ma_moulinette.owasp DROP COLUMN IF EXISTS a10_minor;

        RAISE NOTICE 'La table ma_moulinette.owasp a été migrée vers le nouveau modèle (categories JSONB + module_*).';
    ELSE
        RAISE NOTICE 'La table ma_moulinette.owasp est déjà au nouveau modèle.';
    END IF;

    COMMIT;
END $$;
