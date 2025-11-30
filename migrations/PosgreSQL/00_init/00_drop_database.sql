/*
####################################################
##                                                ##
##         Drop de la base de données             ##
##         V2.0.0 - 30/11/2025                    ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18
-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire de la base
\c postgres;

DROP DATABASE IF EXISTS ma_moulinette WITH (FORCE);
DROP ROLE IF EXISTS db_user;
