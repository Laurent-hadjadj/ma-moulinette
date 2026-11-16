#!/usr/bin/env bash
# Remet la base de la démo dans son état de référence : base recréée, schéma rejoué, données de démonstration chargées.
# À lancer depuis l'HÔTE (ex. chaque nuit par son planificateur). Cette pile ne contient AUCUN conteneur cron : aucun
# traitement batch ni purge de journaux ne tourne dans la démo.
#
# Usage : bash docker/demo/restaurer-demo.sh [fichier.sql.gz]
#   fichier : données à charger après le schéma (défaut : docker/demo/donnees/demo.sql.gz ; s'il n'existe pas, la base
#             reste avec le schéma et les données d'initialisation seulement).
set -euo pipefail

cd "$(dirname "$0")"
C=(docker compose -f docker-compose.demo.yml)
DONNEES="${1:-donnees/demo.sql.gz}"

echo "[demo] arrêt de l'application (la base reste lancée)"
"${C[@]}" stop nginx-demo php-fpm-demo

echo "[demo] recréation de la base et du schéma"
# Messages NOTICE (« table ... does not exist, skipping » des DROP IF EXISTS du schéma sur une base neuve, « truncate cascades »)
# : ils sortent sur l'erreur standard, donc « >/dev/null » ne les retient pas. On ne garde que les avertissements et erreurs.
SILENCE=(-e "PGOPTIONS=-c client_min_messages=warning")
"${C[@]}" exec -T "${SILENCE[@]}" postgres-demo psql -v ON_ERROR_STOP=1 -U postgres -d postgres \
    -c "DROP DATABASE IF EXISTS ma_moulinette WITH (FORCE);" \
    -c "CREATE DATABASE ma_moulinette OWNER = db_user ENCODING = 'UTF8' TEMPLATE = template0;"
# -u postgres : /tmp/schema-demo.sql a été créé par init-schema.sh (utilisateur postgres). Écrit par root, il est refusé
# sur un hôte où fs.protected_regular est actif (Debian/Ubuntu : /tmp collant, fichier d'un autre utilisateur).
"${C[@]}" exec -T -u postgres "${SILENCE[@]}" postgres-demo bash -c \
    'bash /assembler-schema.sh /tmp/schema-demo.sql && psql -v ON_ERROR_STOP=1 -q -U postgres -f /tmp/schema-demo.sql >/dev/null'

if [ -f "$DONNEES" ]; then
    echo "[demo] chargement des données : $DONNEES"
    gunzip -c "$DONNEES" | "${C[@]}" exec -T "${SILENCE[@]}" postgres-demo psql -v ON_ERROR_STOP=1 -q -U postgres -d ma_moulinette >/dev/null
else
    echo "[demo] aucun fichier de données ($DONNEES) : schéma seul"
fi

echo "[demo] comptes et groupes de démonstration"
# Le mot de passe vient de docker/demo/.env (DEMO_USER_PASSWORD) ; seule son empreinte bcrypt entre en base.
if [ -f .env ]; then set -a; . ./.env; set +a; fi
if [ -z "${DEMO_USER_PASSWORD:-}" ]; then
    echo "[demo] DEMO_USER_PASSWORD absent de docker/demo/.env : aucun compte de démonstration ne peut être créé" >&2
    exit 1
fi
HASH="$("${C[@]}" run --rm --no-deps -T --entrypoint php -e PW="$DEMO_USER_PASSWORD" php-fpm-demo     -r 'echo password_hash(getenv("PW"), PASSWORD_BCRYPT, ["cost" => 13]);')"
"${C[@]}" exec -T "${SILENCE[@]}" postgres-demo psql -v ON_ERROR_STOP=1 -q -U postgres -d ma_moulinette -v hash="$HASH"     < reference-demo.sql >/dev/null

echo "[demo] redémarrage de l'application"
"${C[@]}" start php-fpm-demo nginx-demo
echo "[demo] terminé"
