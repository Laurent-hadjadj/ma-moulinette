# Image PHP-FPM de la démo : APP_ENV=prod, sans debug, sans dépendances de développement, sans .env.local.
# Contexte : racine du dépôt. Aucun secret n'est copié : tout vient des variables d'environnement du compose.
ARG php_version=8.5.12-fpm-trixie
FROM php:${php_version}

LABEL environnement="demo"

ENV LANG=fr_FR.UTF-8 LC_ALL=fr_FR.UTF-8 LANGUAGE=fr_FR.UTF-8 DEBIAN_FRONTEND=noninteractive
WORKDIR /var/www

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && apt-get update && apt-get install -y --no-install-recommends \
        unzip locales libicu-dev libonig-dev libzip-dev git libxslt-dev libldap2-dev \
        libsqlite3-dev libpq-dev libfreetype-dev libjpeg62-turbo-dev libpng-dev \
    && sed -i '/fr_FR.UTF-8/s/^# //g' /etc/locale.gen && locale-gen \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure ldap \
    && docker-php-ext-install -j$(nproc) gd intl zip xsl calendar pdo_sqlite pdo_pgsql fileinfo ldap \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN sed -i -r "s/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/; s/memory_limit = .*/memory_limit = 512M/; s/;date.timezone =/date.timezone = Europe\/Paris/; s/;intl.default_locale =/intl.default_locale = fr/" "$PHP_INI_DIR/php.ini"

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-progress --prefer-dist

COPY bin           ./bin
COPY config        ./config
COPY public        ./public
COPY src           ./src
COPY templates     ./templates
COPY translations  ./translations
COPY assets        ./assets
COPY migrations    ./migrations
COPY importmap.php ./importmap.php
COPY .env          ./.env

# Compilation des assets (en prod, AssetMapper sert des fichiers statiques, copiés ensuite dans l'image nginx) et
# préchauffage du cache. Le conteneur de construction n'a pas les variables d'exécution : toute variable citée par
# config/ reçoit une valeur factice, uniquement pour cette commande (rien n'est conservé dans l'image).
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && for v in $(grep -rhoE '%env\((\w+:)*[A-Z0-9_]+\)%' config | sed -E 's/.*[(:]([A-Z0-9_]+)\)%/\1/' | sort -u); do export "$v=construction"; done \
    && export APP_ENV=prod APP_DEBUG=0 DATABASE_URL="postgresql://x:x@localhost/x?serverVersion=18" TRUSTED_PROXIES=127.0.0.1 \
    && php bin/console asset-map:compile --no-interaction \
    && rm -rf var/cache/*
