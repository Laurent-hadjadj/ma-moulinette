/*
####################################################
##                                                ##
##           Create DATABASE                      ##
##           V2.1.2 - 30/04/2026                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18
--- 2026-04-30 : Locale ICU (portable Windows/Linux/macOS), plus de LC_COLLATE OS-specifique

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire de la base.
-- ⚠️ Nécessite que le cluster ait étè initialisé avec ICU
--    (cf docker/.env.template : postgresql_initdb="--locale-provider=icu --icu-locale=fr-FR").
-- ⚠️ Sous windows il faut faire un set PGCLIENTENCODING=UTF8 avant de lancer la commande psql

\c postgres;

CREATE DATABASE ma_moulinette
WITH OWNER = db_user
ENCODING = 'UTF8'
LOCALE_PROVIDER = 'icu'
ICU_LOCALE = 'fr-FR'
LOCALE = 'C'
LC_COLLATE = 'French_France.1252'
LC_CTYPE = 'French_France.1252'
TEMPLATE = template0;
COMMENT ON DATABASE ma_moulinette IS 'Base Ma-Moulinette';


COMMENT ON DATABASE ma_moulinette IS 'Base Ma-Moulinette';
