/*
####################################################
##                                                ##
##           Create ROLE                          ##
##           V3.0.0 - 03/05/2026                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18
--- 2026-05-03 : Mot de passe injecté depuis la variable d'environnement
---              DB_USER_PASSWORD via la variable psql `db_user_password`
---              (cf. docker/scripts/postgresql/init_sql_files.sh).
---              Plus de literal `db_password` en dur.

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire de la base
-- a lancer depuis la console.
\c postgres;

CREATE ROLE db_user LOGIN PASSWORD :'db_user_password';
ALTER ROLE db_user CREATEDB;
COMMENT ON ROLE db_user IS 'Utilisateur propriétaire de la base Ma-Moulinette.';
