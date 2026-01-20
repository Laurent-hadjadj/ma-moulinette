/*
####################################################
##                                                ##
##           Create SCHEMA                        ##
##           V2.0.0 - 30/11/2025                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire du schema
\c ma_moulinette;

CREATE SCHEMA IF NOT EXISTS ma_moulinette AUTHORIZATION db_user;
