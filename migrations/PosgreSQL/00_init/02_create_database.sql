/*
####################################################
##                                                ##
##           Create DATABASE                      ##
##           V2.0.0 - 30/11/2025                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire de la base
\c postgres;

CREATE DATABASE ma_moulinette
WITH OWNER = db_user
ENCODING = 'UTF8'
LC_COLLATE = 'French_France.1252'
LC_CTYPE = 'French_France.1252'
TEMPLATE = template0;
COMMENT ON DATABASE ma_moulinette IS 'Base Ma-Moulinette';
