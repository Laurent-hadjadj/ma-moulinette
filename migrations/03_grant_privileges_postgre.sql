BEGIN;

-- Attribution des privilèges nécessaires au rôle
GRANT CONNECT ON DATABASE ma_moulinette TO ma_moulinette;
GRANT USAGE ON SCHEMA ma_moulinette TO ma_moulinette;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA ma_moulinette TO ma_moulinette;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA ma_moulinette TO ma_moulinette;

COMMIT;
