/*
####################################################
##                                                ##
##           Set SEARCH_PATH                      ##
##           V2.0.1 - 03/05/2026                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18
--- 2026-05-03 : Correction du titre d'en-tête (était "Create DATABASE",
---              copié-collé erroné du script précédent).

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire du schema
\c postgres;

ALTER DATABASE ma_moulinette SET search_path = ma_moulinette, public;
ALTER ROLE db_user IN DATABASE ma_moulinette SET search_path = ma_moulinette, public;
