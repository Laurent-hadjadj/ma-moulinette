/*
####################################################
##                                                ##
##         Drop de la base de données             ##
##         V2.1.0 - 26/04/2026                    ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18
--- 2026_04-26 : Suppressionde la base de tests si elle exite.

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire de la base
-- a lancer depuis la console
\c postgres;

-- La base principale
DROP DATABASE IF EXISTS ma_moulinette WITH (FORCE);
-- La base de test Symfony (APP_ENV=test)
DROP DATABASE IF EXISTS ma_moulinette_test WITH (FORCE);
-- Le role peut maintenant etre supprime (plus aucun objet dependant)
DROP ROLE IF EXISTS db_user;
