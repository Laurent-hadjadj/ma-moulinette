#!/bin/bash
# Rejoue les vrais fichiers migrations/POSTGRESQL/ (montes en lecture seule
# dans /migrations) pour construire "ma_moulinette_test" dans ce conteneur
# Postgres jetable — meme principe que bin/e2e/rebuild-database.ps1 (qui fait
# la meme réécriture ma_moulinette -> ma_moulinette_test sans jamais modifier
# les fichiers sur disque), adapte ici en bash pour tourner dans l'image
# officielle Postgres (exécuté automatiquement au 1er démarrage, volume vide).
#
# Deux differences volontaires avec rebuild-database.ps1, spécifiques à ce
# conteneur Linux jetable :
#   - 00_init/00_drop_database.sql et 00_init/01_create_roles.sql exclus
#     comme cote natif (role recrée ci-dessous, rien a dropper sur un volume
#     frais) ;
#   - 00_init/02_create_database.sql exclu EN PLUS : ce fichier fixe
#     LC_COLLATE/LC_CTYPE='French_France.1252' (locale Windows), absente de
#     l'image Linux — remplace par un CREATE DATABASE minimal (UTF8,
#     template0, sans contrainte de locale) juste apres la creation du role.
set -euo pipefail

MIGRATIONS_DIR=/migrations
MASTER="$MIGRATIONS_DIR/99_master_install.sql"
OUT=/tmp/ma_moulinette_test_install.sql

EXCLUDED="00_init/00_drop_database.sql 00_init/01_create_roles.sql 00_init/02_create_database.sql"

rewrite() {
    sed -E \
        -e 's/^\\c ma_moulinette\b/\\c ma_moulinette_test/' \
        -e 's/\bCREATE DATABASE ma_moulinette\b/CREATE DATABASE ma_moulinette_test/I' \
        -e 's/\bDROP DATABASE IF EXISTS ma_moulinette\b/DROP DATABASE IF EXISTS ma_moulinette_test/I' \
        -e 's/\bALTER DATABASE ma_moulinette\b/ALTER DATABASE ma_moulinette_test/I' \
        -e 's/\bIN DATABASE ma_moulinette\b/IN DATABASE ma_moulinette_test/I' \
        -e 's/\bCOMMENT ON DATABASE ma_moulinette\b/COMMENT ON DATABASE ma_moulinette_test/I' \
        -e "s/datname = 'ma_moulinette'/datname = 'ma_moulinette_test'/"
}

: > "$OUT"

echo "\\c postgres;" >> "$OUT"
echo "CREATE ROLE db_user LOGIN PASSWORD '${DB_USER_PASSWORD}';" >> "$OUT"
echo "ALTER ROLE db_user CREATEDB;" >> "$OUT"
echo "CREATE DATABASE ma_moulinette_test OWNER = db_user ENCODING = 'UTF8' TEMPLATE = template0;" >> "$OUT"

grep -E '^\\ir ' "$MASTER" | sed -E 's/^\\ir +//' | while read -r rel; do
    skip=0
    for ex in $EXCLUDED; do
        [ "$rel" = "$ex" ] && skip=1
    done
    [ "$skip" = "1" ] && continue

    echo "\\echo ===== $rel =====" >> "$OUT"
    rewrite < "$MIGRATIONS_DIR/$rel" >> "$OUT"
done

echo "[init-schema] Script assemble : $OUT"
psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -f "$OUT"
echo "[init-schema] ma_moulinette_test prete."
