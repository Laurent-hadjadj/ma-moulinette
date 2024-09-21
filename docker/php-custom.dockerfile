ARG php_version=8.3.6-fpm-bullseye

FROM php:${php_version}

LABEL maintainer "laurent HADJADJ <laurent_h@me.com>"
LABEL environnement "prd"
LABEL version "php-8.3.6"

ENV GIT_USER="Laurent HADJADJ"
ENV GIT_COURRIEL="laurent_h@me.com"

ENV LANG=fr_FR.UTF-8
ENV LC_ALL=fr_FR.UTF-8
ENV LANGUAGE=fr_FR.UTF-8

WORKDIR /app

# Use the default configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && apt-get update && apt-get install -y --no-install-recommends unzip locales libicu-dev libonig-dev libzip-dev git libxslt-dev \
    && sed -i '/fr_FR.UTF-8/s/^ # //g' /etc/locale.gen \
    && locale-gen \
    # Install PHP extensions for PostgreSQL and SQLite
    && apt-get install -y libsqlite3-dev libpq-dev \
    && docker-php-ext-install -j$(nproc) intl zip xsl calendar pdo_sqlite pdo_pgsql \
    && apt autoremove -y \
    && apt-get purge -y libicu-dev libonig-dev libzip-dev libxslt-dev \
    && apt-get clean \
    && rm -rf /tmp/pear /var/lib/apt/lists/*

RUN sed -i -r "s/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/g" "$PHP_INI_DIR/php.ini" \
    && sed -i -r "s/expose_php = On/expose_php = Off/g" "$PHP_INI_DIR/php.ini" \
    && sed -i -r "s/post_max_size = 8M/post_max_size = 16M/g" "$PHP_INI_DIR/php.ini" \
    && sed -i -r "s/upload_max_filesize = 2M/upload_max_filesize = 4M/g" "$PHP_INI_DIR/php.ini" \
    && sed -i -r "s/max_execution_time = 30/max_execution_time = 600/g" "$PHP_INI_DIR/php.ini" \
    && sed -i -r "s/memory_limit = .*/memory_limit = 2048M/" "$PHP_INI_DIR/php.ini" \
    && sed -i -r "s/zlib.output_compression = .*/zlib.output_compression = on/" "$PHP_INI_DIR/php.ini" \
    && sed -i -r "s/;date.timezone =/date.timezone = Europe\/Paris/" "$PHP_INI_DIR/php.ini" \
    && sed -i -r "s/display_errors = on/display_errors = off/" "$PHP_INI_DIR/php.ini" \
    && sed -i -r "s/;intl.default_locale =/intl.default_locale =fr/" "$PHP_INI_DIR/php.ini" \
    # Setup FPM global config
    && sed -i '0,/^\[www\].*/s/^\[www\].*/emergency_restart_threshold = 10\n&/' "/usr/local/etc/php-fpm.d/docker.conf" \
    && sed -i '0,/^\[www\].*/s/^\[www\].*/emergency_restart_interval = 1m\n&/' "/usr/local/etc/php-fpm.d/docker.conf" \
    && sed -i '0,/^\[www\].*/s/^\[www\].*/process_control_timeout = 5\n&/' "/usr/local/etc/php-fpm.d/docker.conf" \
    # Setup FPM `www` (default) pool
    && sed -i -r "s/pm.max_children = 5/pm.max_children = 640/g" "/usr/local/etc/php-fpm.d/www.conf" \
    && sed -i -r "s/pm.start_servers = 2/pm.start_servers = 18/g" "/usr/local/etc/php-fpm.d/www.conf" \
    && sed -i -r "s/pm.min_spare_servers = 1/pm.min_spare_servers = 12/g" "/usr/local/etc/php-fpm.d/www.conf" \
    && sed -i -r "s/pm.max_spare_servers = 3/pm.max_spare_servers = 24/g" "/usr/local/etc/php-fpm.d/www.conf" \
    && sed -i -r "s/;pm.process_idle_timeout = 10s/pm.process_idle_timeout = 10s/g" "/usr/local/etc/php-fpm.d/www.conf" \
    && sed -i -r "s/;pm.max_requests = 500/pm.max_requests = 500/g" "/usr/local/etc/php-fpm.d/www.conf" \
    && sed -i -r "s/;pm.status_path/pm.status_path/g" "/usr/local/etc/php-fpm.d/www.conf" \
    # Install symfony-cli & composer (facultatif)
    && curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && git config --global user.email ${GIT_COURRIEL} \
    && git config --global user.name ${GIT_USER}
