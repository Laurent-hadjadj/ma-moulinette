/*
####################################################
##                                                ##
##         Création de la base de données         ##
##               V1.0.0 - 14/05/2024              ##
##                                                ##
####################################################*/

BEGIN;

-- Attribution des privilèges nécessaires au rôle
GRANT CONNECT ON DATABASE ma_moulinette TO db_user;
GRANT USAGE ON SCHEMA ma_moulinette TO db_user;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA ma_moulinette TO db_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA ma_moulinette TO db_user;

COMMIT;
