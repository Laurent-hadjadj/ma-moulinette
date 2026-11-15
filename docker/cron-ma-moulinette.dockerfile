# MODIF 2026-05-08 : sidecar cron pour la queue OWASP DependencyCheck.
#
# Image légère PHP CLI + Supercronic (cron-style scheduler conçu pour conteneurs).
# Pourquoi pas le cron Linux classique :
#   - cron Linux fork des processus avec un PID 1 mal géré dans un container
#   - les logs partent dans syslog au lieu de stdout (perdus par docker logs)
#
# Pourquoi Supercronic :
#   - tourne en foreground (PID 1 OK)
#   - logs sur stdout -> captes par docker logs et collecteurs
#   - chaque tick lance un nouveau process PHP qui meurt apres le batch
#     -> garantie zero fuite mémoire (process court)
#
# Cle de robustesse : si le sidecar redémarre, le worker reprend a la prochaine
# minute. Si une row reste "processing" plus de 5 min sans flush, la commande
# `process` la requeue (cf reclaimStaleProcessing).

ARG php_version=8.5.12-cli-trixie
FROM php:${php_version}

LABEL maintainer="Laurent HADJADJ <laurent_h@me.com>"
LABEL component="supercronic-ma-moulinette"
LABEL purpose="Orchestration des tâches (supercronic)"

ENV LANG=fr_FR.UTF-8
ENV LC_ALL=fr_FR.UTF-8
ENV LANGUAGE=fr_FR.UTF-8
ENV DEBIAN_FRONTEND=noninteractive

# Version Supercronic et somme SHA1 de supercronic-linux-amd64 pour CETTE version.
# La somme est celle publiée par le projet dans les notes de la release (section « supercronic-linux-amd64 ») :
#   https://github.com/aptible/supercronic/releases/tag/v0.2.30
# À RECALCULER À CHAQUE CHANGEMENT DE VERSION : une somme d'une autre version fait échouer le build (« FAILED »).
# MODIF 2026-11-15 : la valeur précédente (9aeb41e0...) ne correspondait pas à ce binaire, le build échouait.
ARG SUPERCRONIC_VERSION=v0.2.30
ARG SUPERCRONIC_SHA1SUM=9f27ad28c5c57cd133325b2a66bba69ba2235799

WORKDIR /var/www

# Installation : extensions PHP minimales pour Symfony + Doctrine + ZIP/PG,
# locales fr_FR, et binaire Supercronic.
# Note : pas besoin de gd, intl complet, xsl, etc. (le worker ne fait que
# décompresser + parser JSON + écrire en BDD).
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates curl locales \
        libicu-dev libpq-dev libzip-dev libonig-dev \
    && sed -i '/fr_FR.UTF-8/s/^# //g' /etc/locale.gen \
    && locale-gen \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl zip \
    # Supercronic
    && curl -fsSLo /tmp/supercronic \
        "https://github.com/aptible/supercronic/releases/download/${SUPERCRONIC_VERSION}/supercronic-linux-amd64" \
    && echo "${SUPERCRONIC_SHA1SUM}  /tmp/supercronic" | sha1sum -c - \
    && chmod +x /tmp/supercronic \
    && mv /tmp/supercronic /usr/local/bin/supercronic \
    # Tuning php.ini minimal pour le worker
    && sed -i -r "s/memory_limit = .*/memory_limit = 512M/" "$PHP_INI_DIR/php.ini" \
    && sed -i -r "s/;date.timezone =/date.timezone = Europe\/Paris/" "$PHP_INI_DIR/php.ini" \
    && sed -i -r "s/display_errors = on/display_errors = off/" "$PHP_INI_DIR/php.ini" \
    # Cleanup : on purge les paquets -dev et curl SEULEMENT. Pas de « apt autoremove » : il supprimait aussi les
    # bibliothèques d'exécution (libicu, libzip), arrivées comme dépendances des -dev, et les extensions intl et zip
    # ne se chargeaient plus (« Unable to load dynamic library »). MODIF 2026-11-15.
    && apt-get purge -y libicu-dev libzip-dev libonig-dev curl \
    && apt-get clean \
    && rm -rf /tmp/* /var/lib/apt/lists/*

# Installation du crontab
# MODIF 2026-05-21 : renomme dc-ingest → ma-moulinette.
COPY etc/cron/ma-moulinette /etc/supercronic/ma-moulinette

# Creation user www-data avec UID stable (alignement permission volume partage
# avec php-fpm qui tourne aussi en www-data UID 33).
# www-data existe deja dans l'image PHP officielle, on s'en sert tel quel.
USER www-data

# Healthcheck : Supercronic vivant ? pidof est fourni par l'image de base ; pgrep (procps) n'y est pas installé,
# le healthcheck précédent échouait donc toujours. MODIF 2026-11-15.
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD pidof supercronic > /dev/null || exit 1

# -quiet : pas de log par tick (juste les commandes)
# -passthrough-logs : merge stdout/stderr de la commande dans ceux de supercronic
CMD ["supercronic", "-quiet", "-passthrough-logs", "/etc/supercronic/ma-moulinette"]
