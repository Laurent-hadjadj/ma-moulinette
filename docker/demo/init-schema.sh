#!/bin/bash
# Premier démarrage du conteneur PostgreSQL de la démo (volume vide) : rôle, base « ma_moulinette » (comme en
# production : le SQL cite le schéma ma_moulinette) puis schéma. Les données de démonstration sont chargées par
# restaurer-demo.sh.
set -euo pipefail

psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" <<SQL
CREATE ROLE db_user LOGIN PASSWORD '${DB_USER_PASSWORD}';
CREATE DATABASE ma_moulinette OWNER = db_user ENCODING = 'UTF8' TEMPLATE = template0;
SQL

bash /assembler-schema.sh /tmp/schema-demo.sql
psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -f /tmp/schema-demo.sql
echo "[init-schema-demo] ma_moulinette (démo) prête."
