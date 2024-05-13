BEGIN;

-- Attribution des privilèges nécessaires au rôle
GRANT CONNECT ON DATABASE votre_base_de_donnees TO your_role;
GRANT USAGE ON SCHEMA your_schema TO your_role;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA your_schema TO your_role;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA your_schema TO your_role;

COMMIT;
