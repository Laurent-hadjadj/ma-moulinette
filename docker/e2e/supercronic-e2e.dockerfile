# Sidecar supercronic de la pile e2e : lance en tâche de fond le traitement des files d'ingestion
# (OWASP Dependency-Check, ZAP), pour tester l'ingestion de bout en bout (dépôt du rapport -> file -> traitement).
#
# Cette image PART DE L'IMAGE php-fpm-e2e (contexte « php », voir docker-compose.e2e.yml : additional_contexts) :
# le code (bin/, src/, vendor/, config/), les extensions PHP, le php.ini et le fuseau y sont déjà. On n'ajoute que
# le binaire supercronic et la crontab e2e. Une image PHP « nue » ne pourrait rien exécuter : `php /var/www/bin/console`
# n'y existerait pas (aucun bind-mount depuis l'hôte dans la pile e2e, voir php-e2e.dockerfile).
#
# Build context : la racine du dépôt (../.. depuis docker/e2e/), d'où le chemin de la crontab ci-dessous.
FROM php

LABEL maintainer="Laurent HADJADJ <laurent_h@me.com>"
LABEL component="supercronic-ma-moulinette"
LABEL purpose="Orchestration des tâches (supercronic) - pile e2e"
LABEL environnement="e2e"

# Version de supercronic et somme SHA1 de supercronic-linux-amd64 pour CETTE version.
# La somme est celle publiée par le projet dans les notes de la release (section « supercronic-linux-amd64 ») :
#   https://github.com/aptible/supercronic/releases/tag/v0.2.30
# À RECALCULER À CHAQUE CHANGEMENT DE VERSION (une somme d'une autre version fait échouer le build : « FAILED »).
ARG SUPERCRONIC_VERSION=v0.2.30
ARG SUPERCRONIC_SHA1SUM=9f27ad28c5c57cd133325b2a66bba69ba2235799

USER root
RUN curl -fsSLo /usr/local/bin/supercronic \
        "https://github.com/aptible/supercronic/releases/download/${SUPERCRONIC_VERSION}/supercronic-linux-amd64" \
    && echo "${SUPERCRONIC_SHA1SUM}  /usr/local/bin/supercronic" | sha1sum -c - \
    && chmod +x /usr/local/bin/supercronic

# Crontab e2e : uniquement les traitements de file (voir le fichier pour la raison).
COPY docker/e2e/ma-moulinette /etc/supercronic/ma-moulinette

# www-data : même utilisateur que php-fpm, pour partager le volume var/ (cache et journaux) sans conflit de droits.
USER www-data

# Supercronic vivant ? (pidof est fourni par l'image de base ; voir la vérification dans la documentation e2e)
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD pidof supercronic > /dev/null || exit 1

# -quiet : pas de log par tick ; -passthrough-logs : stdout/stderr des commandes repris dans ceux de supercronic.
CMD ["supercronic", "-quiet", "-passthrough-logs", "/etc/supercronic/ma-moulinette"]
