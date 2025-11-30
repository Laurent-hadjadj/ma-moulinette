/*
####################################################
##                                                ##
##           Create ROLE                          ##
##           V2.0.0 - 30/11/2025                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire de la base
\c postgres;

CREATE ROLE db_user LOGIN PASSWORD 'db_password';
ALTER ROLE db_user CREATEDB;
COMMENT ON ROLE db_user IS 'Utilisateur propriétaire de la base Ma-Moulinette.';
