# Image PHP-FPM e2e totalement autonome : composer install tourne ICI, dans ce
# conteneur Linux — jamais de bind-mount depuis l’hôte Windows pour src/vendor.
# Raison : vendor/ généré par composer sous Windows n'est pas garanti identique
# à celui généré sous Linux (meme si ce projet n'a que des deps PHP pures à ce
# jour), et le bind-mount Windows -> Docker Desktop (virtiofs) est de toute
# façon très lent pour les milliers de petits fichiers de vendor/ (mesure en
# pratique : ~30s/requête avec bind-mount, ~5s sans). Seul var/ (cache/log,
# en écriture) reste sur un volume Docker nommé (cf. docker-compose.e2e.yml).
#
# Build context attendu : la racine du depot (../.. depuis docker/e2e/).
ARG php_version=8.5-fpm-bookworm
FROM php:${php_version}

LABEL maintainer "laurent HADJADJ <laurent_h@me.com>"
LABEL environnement "e2e"

ENV LANG=fr_FR.UTF-8
ENV LC_ALL=fr_FR.UTF-8
ENV LANGUAGE=fr_FR.UTF-8
ENV DEBIAN_FRONTEND=noninteractive

WORKDIR /var/www

# ── Extensions systeme (meme liste que docker/php-custom.dockerfile) ──────────
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && apt-get update && apt-get install -y --no-install-recommends \
        unzip locales libicu-dev libonig-dev libzip-dev git libxslt-dev libldap2-dev \
        libsqlite3-dev libpq-dev libfreetype-dev libjpeg62-turbo-dev libpng-dev \
    && sed -i '/fr_FR.UTF-8/s/^# //g' /etc/locale.gen \
    && locale-gen \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure ldap \
    && docker-php-ext-install -j$(nproc) gd intl zip xsl calendar pdo_sqlite pdo_pgsql fileinfo ldap \
    && apt-get purge -y libicu-dev libonig-dev libzip-dev libxslt-dev libfreetype-dev libjpeg62-turbo-dev libpng-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN sed -i -r "s/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/g" "$PHP_INI_DIR/php.ini" \
    && sed -i -r "s/memory_limit = .*/memory_limit = 2048M/" "$PHP_INI_DIR/php.ini" \
    && sed -i -r "s/max_execution_time = 30/max_execution_time = 600/g" "$PHP_INI_DIR/php.ini" \
    && sed -i -r "s/;date.timezone =/date.timezone = Europe\/Paris/" "$PHP_INI_DIR/php.ini" \
    && sed -i -r "s/;intl.default_locale =/intl.default_locale = fr/" "$PHP_INI_DIR/php.ini"

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ── Dépendances : vendor/ reconstruit ICI depuis composer.lock (jamais copie) ──
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-interaction --no-scripts --no-progress --prefer-dist

# ── Code applicatif ────────────────────────────────────────────────────────
# PAS de .env.local / .env.test.local / .env-prod : ces fichiers gitignores
# contiennent (ou ont contenu) de vraies valeurs locales (ex. un token
# SonarQube reel dans .env.test.local). Les baker dans un layer d'image serait
# une fuite indépendante du commit git (meme un fichier jamais commite finit
# dans l'image). Seuls .env et .env.test (committes, valeurs factices
# uniquement) sont copies ; tout le reste vient des variables d'environnement
# du compose (voir docker-compose.e2e.yml).
COPY bin            ./bin
COPY config          ./config
COPY public          ./public
COPY src             ./src
COPY templates       ./templates
COPY translations    ./translations
COPY assets          ./assets
COPY migrations      ./migrations
COPY importmap.php   ./importmap.php
# tests/Support/ (PAS tout tests/) : App\Tests\SonarFixtureClientService est
# chargée par le container DI même en simple affichage de page (remplace les
# vrais appels SonarQube en APP_ENV=test) — sans ce dossier, "Class ... not
# found" sur a peu pres toutes les pages qui touchent EasyAdmin/Dashboard.
# Le reste de tests/ (Unit/Integration/e2e) n'est jamais chargé à l’exécution.
COPY tests/Support    ./tests/Support
# Bug latent découvert en construisant cette image : SonarFixtureClientService.php
# lit en dur dirname(__DIR__) . '/fixtures/sonarqube' (minuscule), alors que le
# dossier committe s'appelle tests/Fixtures (majuscule) — fonctionne par accident
# sous Windows (NTFS insensible a la casse), casserait sur tout Linux reel/CI.
# On copie donc explicitement vers la casse exacte attendue par le code.
COPY tests/Fixtures   ./tests/fixtures
COPY .env            ./.env
COPY .env.test       ./.env.test

RUN composer dump-autoload --optimize
