#!/usr/bin/env bash
# Prépare les données de l'instance de démonstration :
#   1. démarre la pile de préparation (PostgreSQL jetable sur 127.0.0.1:5435, docker-compose.prepa.yml) ;
#   2. lance `php bin/console app:demo:preparer` (env dev) : liste des projets, profils, collecte et historique par
#      version, avec le compte SonarQube de LECTURE de la démo (DEMO_SONAR_URL / DEMO_SONAR_TOKEN) ;
#   3. exporte les tables de collecte vers docker/demo/donnees/demo.sql.gz (chargé par restaurer-demo.sh).
#
# À lancer depuis l'HÔTE, dans l'environnement de développement (PHP et vendor/ présents). Ne démarre AUCUN cron et
# n'écrit jamais dans la base de développement ni de production : la commande refuse toute connexion autre que le
# port 5435 de la pile de préparation.
#
# Usage : bash docker/demo/preparer-donnees.sh [options]
#   --reinitialiser         détruit la base de préparation (volume compris) avant de recommencer
#   --projet <clé>          ne collecter que ce projet (répétable)
#   --tag <préfixe>         préfixe de tag SonarQube (défaut : ma-moulinette)
#   --sans-historique       ne pas reconstruire l'historique par version
#   --liste-seulement       afficher les projets retenus, sans collecte ni export
#   --sans-export           collecter sans écrire demo.sql.gz
#   --export-seulement      réexporter la base de préparation telle quelle, sans relancer la collecte
set -euo pipefail

ici="$(cd "$(dirname "$0")" && pwd)"
racine="$(cd "$ici/../.." && pwd)"
cd "$ici"

P=(docker compose -f docker-compose.prepa.yml)
reinit=0
export_donnees=1
collecter=1
args=()

while [ $# -gt 0 ]; do
    case "$1" in
        --reinitialiser) reinit=1 ;;
        --sans-export) export_donnees=0 ;;
        --export-seulement) collecter=0 ;;
        --liste-seulement) args+=("$1"); export_donnees=0 ;;
        --sans-historique) args+=("$1") ;;
        --projet|--tag)
            [ $# -ge 2 ] || { echo "Valeur manquante pour $1" >&2; exit 2; }
            args+=("$1=$2"); shift ;;
        *) echo "Option inconnue : $1" >&2; exit 2 ;;
    esac
    shift
done

[ -f .env ] || { echo "docker/demo/.env introuvable (copier .env.exemple)." >&2; exit 2; }
set -a
# shellcheck disable=SC1091
. ./.env
set +a

for v in DEMO_SONAR_URL DEMO_SONAR_TOKEN DEMO_PREPA_POSTGRES_PASSWORD DEMO_PREPA_DB_USER_PASSWORD; do
    if [ -z "${!v:-}" ]; then
        echo "$v est vide dans docker/demo/.env (voir .env.exemple)." >&2
        exit 2
    fi
done

if [ "$reinit" = "1" ]; then
    echo "[prepa] destruction de la base de préparation"
    "${P[@]}" down -v
fi

echo "[prepa] démarrage de la pile de préparation (PostgreSQL 127.0.0.1:5435)"
"${P[@]}" up -d --wait postgres-prepa

if [ "$collecter" = "1" ]; then
echo "[prepa] collecte (php bin/console app:demo:preparer)"
(
    cd "$racine"
    # Les variables d'environnement réelles l'emportent sur .env.local : aucune base ni jeton de l'hôte n'est utilisé.
    export APP_ENV=dev
    export DATABASE_URL="postgresql://db_user:${DEMO_PREPA_DB_USER_PASSWORD}@127.0.0.1:5435/ma_moulinette?serverVersion=18&charset=utf8"
    export SONAR_URL="$DEMO_SONAR_URL"
    export SONAR_TOKEN="$DEMO_SONAR_TOKEN"
    php bin/console app:demo:preparer "${args[@]}"
)
fi

if [ "$export_donnees" != "1" ]; then
    echo "[prepa] terminé (sans export)"
    exit 0
fi

echo "[prepa] export des tables de collecte"
psql_prepa() { "${P[@]}" exec -T postgres-prepa psql -U postgres -d ma_moulinette "$@"; }

# Tables portant une clé de projet, plus profils et propriétés (compteurs, version de la base).
tables="$(psql_prepa -Atc "SELECT table_name FROM information_schema.columns
    WHERE table_schema = 'ma_moulinette' AND column_name = 'maven_key'
    UNION SELECT 'profiles' UNION SELECT 'properties' ORDER BY 1" | tr -d '\r')"

lignes="$(psql_prepa -Atc "SELECT count(*) FROM ma_moulinette.historique" | tr -d '\r')"
if [ "${lignes:-0}" -lt 1 ]; then
    echo "[prepa] table historique vide : export annulé (collecte incomplète ?)" >&2
    exit 1
fi

pgargs=()
liste_sql=""
for t in $tables; do
    pgargs+=(-t "ma_moulinette.$t")
    liste_sql="${liste_sql:+$liste_sql, }ma_moulinette.$t"
done

mkdir -p donnees
tmp="donnees/demo.sql.gz.tmp"
{
    echo "-- Données de démonstration (collecte SonarQube), générées par preparer-donnees.sh le $(date -u +%Y-%m-%dT%H:%M:%SZ)."
    echo "-- Chargé par restaurer-demo.sh après le schéma. Aucune donnée de compte : voir reference-demo.sql."
    echo "TRUNCATE TABLE $liste_sql CASCADE;"
    "${P[@]}" exec -T postgres-prepa pg_dump -U postgres -d ma_moulinette --data-only --no-owner --no-privileges "${pgargs[@]}"
} | gzip -9 > "$tmp"
mv "$tmp" donnees/demo.sql.gz

echo "[prepa] donnees/demo.sql.gz : $(du -h donnees/demo.sql.gz | cut -f1), $lignes ligne(s) d'historique"
psql_prepa -c "SELECT maven_key, count(*) AS lignes_historique FROM ma_moulinette.historique GROUP BY maven_key ORDER BY 1"
echo "[prepa] terminé. Charger dans la démo : bash docker/demo/restaurer-demo.sh"
