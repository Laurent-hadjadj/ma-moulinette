/*
####################################################
##                                                ##
##           Create DATABASE                      ##
##           V2.0.0 - 30/11/2025                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire du schema
\c postgres;

ALTER DATABASE ma_moulinette SET search_path = ma_moulinette, public;
ALTER ROLE db_user IN DATABASE ma_moulinette SET search_path = ma_moulinette, public;
