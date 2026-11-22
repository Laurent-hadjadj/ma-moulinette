#!/usr/bin/env bash
# Déploie (ou met à jour) la démo sur le serveur, sans action manuelle. Mêmes étapes que la section « Déploiement sur le
# serveur » de mkDocs/docs/ma-moulinette/architecture/demo/etapes-detaillees.md (vue d'ensemble : architecture/demo.md).
#
# Usage :  bash deployer-demo.sh <tag>                  ex. bash deployer-demo.sh v2.9.0
#          bash deployer-demo.sh <tag> --env-seulement  génère .env sans déployer (voir l'avertissement plus bas)
#
# Premier déploiement : cloner le dépôt une fois, puis lancer le script depuis le clone.
#   git clone https://github.com/Laurent-hadjadj/ma-moulinette.git /opt/DOCKER/ma-moulinette-demo/depot
#   bash /opt/DOCKER/ma-moulinette-demo/depot/docker/demo/deployer-demo.sh v2.9.0
#
# Variables d'environnement (facultatives) :
#   SRV         dossier de travail (défaut /opt/DOCKER/ma-moulinette-demo) : depot/, app/, sauvegardes/
#   DEPOT_URL   dépôt à cloner (défaut : dépôt public)
#   DEMO_SONAR_URL, DEMO_SONAR_TOKEN   adresse et jeton SonarQube, pris en compte au premier déploiement seulement
#
# Secrets de .env, à chaque déploiement :
#   régénérés : DEMO_POSTGRES_PASSWORD, DEMO_DB_USER_PASSWORD (appliqués à la base existante par ALTER ROLE),
#               DEMO_APP_SECRET (base64 de 32 octets : il sert aussi de clé Actuator)
#   conservés : DEMO_USER_PASSWORD (communiqué aux visiteurs : le changer casserait le compte de démonstration),
#               DEMO_SONAR_URL, DEMO_SONAR_TOKEN (valeurs externes)
#   Une copie de l'ancien .env est rangée dans sauvegardes/ (droits 600).
set -euo pipefail

main() {
  local TAG="${1:?Usage : bash deployer-demo.sh <tag> [--env-seulement]}" MODE="${2:-}"
  local SRV="${SRV:-/opt/DOCKER/ma-moulinette-demo}"
  local DEPOT_URL="${DEPOT_URL:-https://github.com/Laurent-hadjadj/ma-moulinette.git}"
  local DEPOT="$SRV/depot" DEMO="$SRV/depot/docker/demo"
  local ENVF="$DEMO/.env" PREMIERE=0 VOLUME=0 HORODATAGE
  HORODATAGE="$(date +%Y%m%d-%H%M)"

  local cmd
  for cmd in docker git openssl curl; do
    command -v "$cmd" >/dev/null || { echo "[demo] commande requise absente : $cmd" >&2; return 1; }
  done

  # --- 1. Code : clone, tag ; relance si le tag apporte une nouvelle version de ce script
  mkdir -p "$SRV/sauvegardes"
  local COPIE; COPIE="$(mktemp)"
  cp "${BASH_SOURCE[0]}" "$COPIE"
  if [ -d "$DEPOT/.git" ]; then
    git -C "$DEPOT" fetch --tags --prune --quiet
  else
    git clone --quiet "$DEPOT_URL" "$DEPOT"
  fi
  git -C "$DEPOT" rev-parse -q --verify "refs/tags/$TAG" >/dev/null || { echo "[demo] tag introuvable : $TAG" >&2; return 1; }
  git -C "$DEPOT" checkout --quiet "$TAG"
  echo "[demo] version $TAG"
  if [ -z "${DEPLOIEMENT_RELANCE:-}" ] && [ -f "$DEMO/deployer-demo.sh" ] && ! cmp -s "$COPIE" "$DEMO/deployer-demo.sh"; then
    echo "[demo] le tag apporte une nouvelle version du script : relance"
    rm -f "$COPIE"
    DEPLOIEMENT_RELANCE=1 exec bash "$DEMO/deployer-demo.sh" "$@"
  fi
  rm -f "$COPIE"

  [ -f "$ENVF" ] || PREMIERE=1
  docker volume inspect ma-moulinette-demo-pgdata >/dev/null 2>&1 && VOLUME=1
  [ -f "$DEMO/donnees/demo.sql.gz" ] || echo "[demo] ATTENTION : donnees/demo.sql.gz absent : la démo n'aura que le schéma (copier le fichier puis relancer)" >&2

  local C=(docker compose -f "$DEMO/docker-compose.demo.yml")

  if [ "$MODE" != "--env-seulement" ]; then
    # --- 2. Image PHP (la même que la production, sans code). Variables factices : le compose exige les secrets, pas la construction.
    echo "[demo] image PHP"
    DEMO_POSTGRES_PASSWORD=x DEMO_DB_USER_PASSWORD=x DEMO_APP_SECRET=x "${C[@]}" build php-fpm-demo

    # --- 3. Code construit dans app.neuf : seulement ce qui sert à l'exécution
    echo "[demo] construction du code"
    rm -rf "${SRV:?}/app.neuf" && mkdir "$SRV/app.neuf"
    cp "$DEPOT/composer.json" "$SRV/app.neuf/"
    # Composer dans un dossier qui ne contient que composer.json : sans symfony.lock (non versionné), Symfony Flex y dépose des
    # fichiers d'exemple (compose.yaml, base.html.twig...) ; on ne garde que vendor/.
    docker run --rm -v "$SRV/app.neuf:/app" -w /app composer:lts \
      composer install --no-dev --no-interaction --no-scripts --no-progress --prefer-dist --ignore-platform-reqs
    find "$SRV/app.neuf" -mindepth 1 -maxdepth 1 ! -name vendor ! -name composer.json ! -name composer.lock -exec rm -rf {} +
    (cd "$DEPOT" && cp -r bin config public src templates translations assets migrations importmap.php .env "$SRV/app.neuf/")
    cp "$DEPOT/assets/js/common/secrets.local.js.dist" "$SRV/app.neuf/assets/js/common/secrets.local.js"
    docker run --rm -v "$SRV/app.neuf:/app" -w /app composer:lts \
      composer dump-autoload --optimize --no-dev --ignore-platform-reqs
    # assets/vendor/ est ignoré par git : importmap:install le télécharge. Toute variable citée par config/ reçoit une valeur
    # factice, pour ces commandes seulement.
    local ENVS
    ENVS="$(cd "$DEPOT" && grep -rhoE '%env\((\w+:)*[A-Z0-9_]+\)%' config | sed -E 's/.*[(:]([A-Z0-9_]+)\)%/\1/' | sort -u | sed 's/.*/-e &=construction/' | tr '\n' ' ')"
    # shellcheck disable=SC2086
    docker run --rm $ENVS -e APP_ENV=prod -e APP_DEBUG=0 -e TRUSTED_PROXIES=127.0.0.1 \
      -e 'DATABASE_URL=postgresql://x:x@localhost/x?serverVersion=18' \
      -v "$SRV/app.neuf:/var/www" -w /var/www ma-moulinette-php-demo:8.5.12 \
      sh -c 'php bin/console importmap:install --no-interaction && php bin/console asset-map:compile --no-interaction'
    [ -f "$SRV/app.neuf/public/assets/manifest.json" ] && [ -d "$SRV/app.neuf/assets/vendor/foundation-sites" ] \
      || { echo "[demo] construction incomplète (assets) : l'ancienne version reste en place" >&2; return 1; }
  fi

  # --- 4. Secrets : .env régénéré (voir l'en-tête pour ce qui est conservé)
  ancien() { if [ -f "$ENVF" ]; then sed -n "s/^$1=//p" "$ENVF" | head -n 1; fi; }
  local MDP_UTILISATEUR SONAR_URL SONAR_TOKEN PG_MDP DB_MDP SECRET
  MDP_UTILISATEUR="$(ancien DEMO_USER_PASSWORD)"
  [ -n "$MDP_UTILISATEUR" ] || MDP_UTILISATEUR="$(openssl rand -base64 24 | tr -d '/+=\n' | cut -c1-16)"
  SONAR_URL="$(ancien DEMO_SONAR_URL)";     [ -n "$SONAR_URL" ]   || SONAR_URL="${DEMO_SONAR_URL:-}"
  SONAR_TOKEN="$(ancien DEMO_SONAR_TOKEN)"; [ -n "$SONAR_TOKEN" ] || SONAR_TOKEN="${DEMO_SONAR_TOKEN:-}"
  PG_MDP="$(openssl rand -hex 24)"       # hexadécimal : aucune contrainte
  DB_MDP="$(openssl rand -hex 24)"       # hexadécimal : il entre dans DATABASE_URL (pas de « / », « + », « = »)
  SECRET="$(openssl rand -base64 32)"    # base64 de 32 octets : clé Actuator
  if [ -f "$ENVF" ]; then
    ( umask 077; cp "$ENVF" "$SRV/sauvegardes/env-$HORODATAGE" )
  fi
  (
    umask 077
    {
      echo "# Généré par deployer-demo.sh le $(date '+%d/%m/%Y %H:%M') (version $TAG) : ne pas committer."
      echo "DEMO_POSTGRES_PASSWORD=$PG_MDP"
      echo "DEMO_DB_USER_PASSWORD=$DB_MDP"
      echo "DEMO_APP_SECRET=$SECRET"
      echo "DEMO_USER_PASSWORD=$MDP_UTILISATEUR"
      echo "DEMO_SONAR_URL=$SONAR_URL"
      echo "DEMO_SONAR_TOKEN=$SONAR_TOKEN"
    } > "$ENVF.neuf"
  )
  mv "$ENVF.neuf" "$ENVF"
  if [ "$PREMIERE" -eq 1 ]; then
    echo "[demo] .env créé"
  else
    echo "[demo] .env régénéré (ancien : sauvegardes/env-$HORODATAGE)"
  fi

  if [ "$MODE" = "--env-seulement" ]; then
    echo "[demo] --env-seulement : rien d'autre n'est fait."
    if [ "$VOLUME" -eq 1 ]; then
      echo "[demo] ATTENTION : la base existe déjà et ses mots de passe PostgreSQL ne sont PAS modifiés. Ne recréez pas les conteneurs avant un déploiement complet." >&2
    fi
    return 0
  fi

  # --- 5. Mots de passe PostgreSQL : la base existante garde l'ancien jusqu'à un ALTER ROLE (par le socket du conteneur, sans mot de passe)
  if [ "$VOLUME" -eq 1 ]; then
    echo "[demo] application des nouveaux mots de passe PostgreSQL"
    "${C[@]}" up -d postgres-demo
    local i
    for i in $(seq 1 30); do
      "${C[@]}" exec -T postgres-demo pg_isready -U postgres -q && break
      sleep 2
    done
    "${C[@]}" exec -T -u postgres postgres-demo psql -v ON_ERROR_STOP=1 -q -U postgres <<SQL
ALTER ROLE db_user PASSWORD '$DB_MDP';
ALTER ROLE postgres PASSWORD '$PG_MDP';
SQL
  fi

  # --- 6. Mise en place : arrêt, sauvegarde tar.gz de l'ancienne version, bascule
  echo "[demo] mise en place"
  "${C[@]}" stop nginx-demo php-fpm-demo || true
  if [ -d "$SRV/app" ]; then
    tar -czf "$SRV/sauvegardes/app-$HORODATAGE.tar.gz" -C "$SRV" app
  fi
  rm -rf "${SRV:?}/app" && mv "$SRV/app.neuf" "$SRV/app"

  # --- 7. Démarrage puis restauration du SQL (schéma, données, compte de démonstration)
  "${C[@]}" up -d --force-recreate
  bash "$DEMO/restaurer-demo.sh"

  # --- 8. Contrôle
  local ok=0
  for i in $(seq 1 15); do
    if curl -fsS -o /dev/null -I http://127.0.0.1:8081/; then ok=1; break; fi
    sleep 2
  done
  if [ "$ok" -ne 1 ]; then
    echo "[demo] ÉCHEC : http://127.0.0.1:8081/ ne répond pas (docker compose logs php-fpm-demo nginx-demo)" >&2
    return 1
  fi
  echo "[demo] OK : version $TAG en ligne sur http://127.0.0.1:8081/"
  if [ "$PREMIERE" -eq 1 ]; then
    echo "[demo] Compte de démonstration : demo@ma-moulinette.fr / $MDP_UTILISATEUR  (premier déploiement : à communiquer aux visiteurs, conservé ensuite)"
  fi
}

# Une seule ligne : bash lit la commande en entier avant de l'exécuter, donc le checkout du tag (qui peut remplacer ce fichier)
# n'a aucun effet sur la suite de l'exécution.
main "$@"; exit $?
