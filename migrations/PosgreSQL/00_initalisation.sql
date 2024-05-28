/*
####################################################
##                                                ##
##         Création de la base de données         ##
##               V1.1.1 - 22/05/2024              ##
##                                                ##
####################################################*/

--- 2024-05-22 : Laurent HADJADJ - mise à jour des attributs de création de la base de données.
-- Suppression de la base, de l'utilisateur et du rôle s'ils existent
DROP DATABASE IF EXISTS ma_moulinette WITH (FORCE);
DROP USER IF EXISTS db_user;
DROP ROLE IF EXISTS db_user;

-- Création du rôle avec des privilèges de connexion
CREATE ROLE db_user LOGIN PASSWORD 'db_password';

-- Création de la base de données avec les paramètres spécifiés
CREATE DATABASE ma_moulinette WITH OWNER = db_user ENCODING = 'UTF8' LC_COLLATE = 'French_France.1252' LC_CTYPE = 'French_France.1252' TABLESPACE = pg_default CONNECTION LIMIT = -1 IS_TEMPLATE = FALSE;
SET client_encoding = 'UTF8';
UPDATE pg_database SET datcollate='fr_FR.UTF-8', datctype='fr_FR.UTF-8' WHERE datname='ma_moulinette';

COMMENT ON DATABASE ma_moulinette IS 'Base de données Ma-Moulinette';

-- Configuration du search_path pour la base de données
ALTER DATABASE ma_moulinette SET search_path TO ma_moulinette;

-- Droits sur la base de données
GRANT TEMPORARY, CONNECT ON DATABASE ma_moulinette TO PUBLIC;
GRANT ALL ON DATABASE ma_moulinette TO db_user;

-- Configuration du search_path pour l'utilisateur spécifique
--ALTER ROLE db_user SET search_path TO ma_moulinette;
ALTER ROLE db_user IN DATABASE ma_moulinette SET search_path TO ma_moulinette;

COMMENT ON ROLE db_user IS 'Utilisateur propriétaire de la base de données Ma-Moulinette.';
