/*
####################################################
##                                                ##
##         Mise à jour de la base de données      ##
##               V1.0.0 - 15/11/2026              ##
##                                                ##
####################################################*/

-- Laurent HADJADJ, 15/11/2026 - dc_cve.cve_id : VARCHAR(64) -> VARCHAR(255).
-- BUG CORRIGÉ : le champ "name" d'une vulnerability DependencyCheck n'est pas
-- toujours un identifiant court (CVE-YYYY-NNNNN / GHSA-...) — l'analyseur
-- Retire.js (dépendances JS) y met un titre libre, confirmé en conditions
-- réelles jusqu'à 74 caractères ("ReDoS vulnerability in Autolink plugin and
-- Advanced Tab for Dialogs plugin"), provoquant un "value too long for type
-- character varying(64)" à l'ingestion.

\c ma_moulinette postgres;

DO $$
DECLARE
    current_length INTEGER;
BEGIN
    SELECT character_maximum_length
    FROM information_schema.columns
    WHERE table_schema = 'ma_moulinette'
        AND table_name = 'dc_cve'
        AND column_name = 'cve_id'
    INTO current_length;

    IF current_length IS NULL THEN
        RAISE NOTICE 'Colonne ma_moulinette.dc_cve.cve_id introuvable (table pas encore créée ?).';
    ELSIF current_length >= 255 THEN
        RAISE NOTICE 'ma_moulinette.dc_cve.cve_id est déjà VARCHAR(%), rien à faire.', current_length;
    ELSE
        ALTER TABLE ma_moulinette.dc_cve ALTER COLUMN cve_id TYPE VARCHAR(255);
        RAISE NOTICE 'ma_moulinette.dc_cve.cve_id élargie de VARCHAR(%) à VARCHAR(255).', current_length;
    END IF;
END $$;
