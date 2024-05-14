/*
####################################################
##                                                ##
##         Création de la base de données         ##
##               V1.1.0 - 14/05/2024              ##
##                                                ##
####################################################*/

-- Suppression de la base, de l'utilisateur et du role
DROP DATABASE IF EXISTS ma_moulinette WITH (FORCE);
DROP USER IF EXISTS zgueddou;
DROP ROLE IF EXISTS zgueddou;

--  Création du role et de la base de données
CREATE ROLE zgueddou LOGIN PASSWORD 'zaki2024';
CREATE DATABASE ma_moulinette WITH OWNER = zgueddou ENCODING 'UTF8';

-- Configuration du search path pour la base de données
ALTER DATABASE ma_moulinette SET search_path TO ma_moulinette;

-- Configuration du search path pour l'utilisateur spécifique
ALTER ROLE zgueddou SET search_path TO ma_moulinette;

-- Configuration du search path pour la base de données
ALTER DATABASE ma_moulinette SET search_path TO ma_moulinette;

-- Configuration du search path pour l'utilisateur spécifique
ALTER ROLE db_user SET search_path TO ma_moulinette;

-- Droits sur la base de données
GRANT ALL PRIVILEGES ON DATABASE ma_moulinette to zgueddou;
