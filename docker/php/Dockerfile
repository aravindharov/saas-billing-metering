# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Application image (development / CI)
#
# A single CLI-based image for every PHP responsibility: HTTP server,
# migrations, queue worker, scheduler, tests and static analysis.
# ---------------------------------------------------------------------------

ARG PHP_VERSION=8.4
ARG NODE_MAJOR=22

FROM composer:2 AS composer

FROM php:${PHP_VERSION}-cli-bookworm

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1 \
    COMPOSER_HOME=/composer \
    npm_config_cache=/tmp/npm-cache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libicu-dev \
        libonig-dev \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini

WORKDIR /var/www/html

ARG NODE_MAJOR
RUN curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY composer.json composer.lock package.json package-lock.json* ./
RUN composer install --no-interaction --no-scripts --prefer-dist \
    && npm ci --ignore-scripts

ARG APP_UID=1000
ARG APP_GID=1000
RUN groupmod --gid "${APP_GID}" www-data \
    && usermod --uid "${APP_UID}" --gid "${APP_GID}" www-data

COPY --chown=www-data:www-data . .

RUN mkdir -p bootstrap/cache storage/app/public storage/app/private \
        storage/framework/cache/data storage/framework/sessions \
        storage/framework/testing storage/framework/views storage/logs \
    && composer dump-autoload --optimize --no-interaction \
    && chown -R www-data:www-data bootstrap storage vendor node_modules

USER www-data

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
