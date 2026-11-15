#!/bin/bash
# Assemble le SQL du schéma de la DÉMO à partir de migrations/POSTGRESQL/ (monté en /migrations, lecture seule).
# Écrit le résultat dans le fichier passé en argument ; ne crée NI rôle NI base (voir init-schema.sh et restaurer-demo.sh).
# Exclus : les 3 fichiers 00_init de création (locale Windows) et 90_fixtures/fixtures-e2e.sql (comptes de test aux mots
# de passe connus : jamais dans une instance exposée).
set -euo pipefail

MIGRATIONS_DIR=/migrations
OUT="${1:?fichier de sortie manquant}"
EXCLUDED="00_init/00_drop_database.sql 00_init/01_create_roles.sql 00_init/02_create_database.sql 90_fixtures/fixtures-e2e.sql"

: > "$OUT"
printf '%s\n' '\c ma_moulinette;' >> "$OUT"
grep -E '^.ir ' "$MIGRATIONS_DIR/99_master_install.sql" | sed -E 's/^.ir +//' | tr -d '\r' | while read -r rel; do
    skip=0
    for ex in $EXCLUDED; do
        [ "$rel" = "$ex" ] && skip=1
    done
    [ "$skip" = "1" ] && continue
    echo "\echo ===== $rel =====" >> "$OUT"
    cat "$MIGRATIONS_DIR/$rel" >> "$OUT"
done
