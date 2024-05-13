/*
####################################################
##                                                ##
##         Création de la base de données         ##
##               V1.0.0 - 14/05/2024              ##
##                                                ##
####################################################*/

-- Suppression de la base, de l'utilisateur et du role
DROP DATABASE IF EXISTS ma_moulinette WITH (FORCE);
DROP USER IF EXISTS db_user;
DROP ROLE IF EXISTS db_user;

--  Création du role et de la base de données
CREATE ROLE db_user LOGIN PASSWORD 'db_password';
CREATE DATABASE ma_moulinette WITH OWNER = db_user ENCODING 'UTF8';

-- Droits sur la base de données
GRANT ALL PRIVILEGES ON DATABASE ma_moulinette to db_user;
