#!/usr/bin/env bash
# Vérifie, en LECTURE SEULE, le compte SonarQube utilisé par l'instance de démonstration.
#
# Contrôles :
#   1. le compte n'a AUCUN droit global (admin, scan, provisioning...) ;
#   2. il voit les projets (Browse) et lit mesures, anomalies, hotspots, analyses, profils ;
#   3. il est refusé sur le code source et sur les routes d'administration.
#
# Aucune écriture n'est tentée : l'absence de droit global est lue, pas vérifiée.
# Le jeton vient de docker/demo/.env (DEMO_SONAR_URL, DEMO_SONAR_TOKEN) et n'est jamais affiché.
#
# Usage : bash docker/demo/verifier-sonar.sh [login_attendu]     (défaut : ma-moulinette-demo)

set -u

ici="$(cd "$(dirname "$0")" && pwd)"
login_attendu="${1:-ma-moulinette-demo}"

if [ ! -f "$ici/.env" ]; then
  echo "Fichier $ici/.env introuvable." >&2
  exit 2
fi
set -a
# shellcheck disable=SC1091
. "$ici/.env"
set +a

url="${DEMO_SONAR_URL:-}"
jeton="${DEMO_SONAR_TOKEN:-}"
if [ -z "$url" ] || [ -z "$jeton" ]; then
  echo "DEMO_SONAR_URL ou DEMO_SONAR_TOKEN vide dans .env." >&2
  exit 2
fi
url="${url%/}"

corps="$(mktemp)"
trap 'rm -f "$corps"' EXIT

echecs=0
ok()   { printf '  [OK]   %s\n' "$1"; }
ko()   { printf '  [ECHEC] %s\n' "$1"; echecs=$((echecs + 1)); }

# Appel GET ; le jeton passe par stdin (pas dans la liste des processus). Code HTTP dans $code, corps dans $corps.
appel() {
  code="$(printf 'user = "%s:"\n' "$jeton" | curl -s -m 30 -K - -o "$corps" -w '%{http_code}' "$url$1")"
}

attendu() { # attendu <code_attendu> <chemin> <libellé>
  appel "$2"
  if [ "$code" = "$1" ]; then ok "$3 ($code)"; else ko "$3 : attendu $1, reçu $code"; fi
}

echo "Serveur : $url"
echo

echo "1. Identité et droits globaux"
appel "/api/users/current"
if [ "$code" != "200" ]; then
  ko "jeton refusé ou serveur injoignable (HTTP $code)"
  echo; echo "Arrêt : impossible de poursuivre."; exit 1
fi
login="$(sed -n 's/.*"login":"\([^"]*\)".*/\1/p' "$corps" | head -n1)"
if [ "$login" = "$login_attendu" ]; then ok "le jeton appartient à « $login »"; else ko "le jeton appartient à « $login » (attendu « $login_attendu »)"; fi
globaux="$(sed -n 's/.*"permissions":{"global":\[\([^]]*\)\].*/\1/p' "$corps" | head -n1)"
if [ -z "$globaux" ]; then ok "aucun droit global"; else ko "droits globaux présents : $globaux"; fi
echo

echo "2. Lecture des projets"
appel "/api/components/search_projects?ps=100"
if [ "$code" != "200" ]; then
  ko "search_projects : HTTP $code"
  cles=""
else
  total="$(sed -n 's/.*"total":\([0-9]*\).*/\1/p' "$corps" | head -n1)"
  cles="$(grep -o '"key":"[^"]*","name"' "$corps" | sed 's/"key":"\([^"]*\)".*/\1/')"
  if [ "${total:-0}" -ge 1 ]; then ok "search_projects : $total projet(s) visible(s)"; else ko "search_projects : aucun projet visible (droit Browse manquant ?)"; fi
fi

if [ -n "$cles" ]; then
  while IFS= read -r cle; do
    [ -z "$cle" ] && continue
    echo "  -- $cle"
    attendu 200 "/api/measures/component?component=$cle&metricKeys=ncloc,coverage,bugs,vulnerabilities" "mesures"
    attendu 200 "/api/issues/search?componentKeys=$cle&ps=1" "anomalies"
    attendu 200 "/api/hotspots/search?project=$cle&ps=1" "hotspots de sécurité"
    attendu 200 "/api/project_analyses/search?project=$cle" "historique des analyses"
    attendu 403 "/api/sources/raw?key=$cle:pom.xml" "code source refusé"
  done <<EOF
$cles
EOF
fi

attendu 200 "/api/qualityprofiles/search" "profils de qualité"
attendu 200 "/api/server/version" "version du serveur"
echo

echo "3. Routes d'administration (doivent être refusées)"
attendu 403 "/api/permissions/users?ps=1" "liste des droits"
# users/search est ouvert à tout utilisateur connecté (login et nom seulement, sans courriel) : non testé.
# Un compte peut lister ses PROPRES jetons : on teste ceux d'un autre compte.
attendu 403 "/api/user_tokens/search?login=admin" "jetons d'un autre utilisateur"
attendu 403 "/api/projects/search?ps=1" "recherche de projets (administration)"
echo

if [ "$echecs" -eq 0 ]; then
  echo "Résultat : tous les contrôles sont conformes."
  exit 0
fi
echo "Résultat : $echecs contrôle(s) en échec."
exit 1
