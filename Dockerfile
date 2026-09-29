# syntax=docker/dockerfile:1

# Muraqib: Laravel + Inertia (Vue) + Filament, served by nginx + PHP-FPM.
# The same image runs the web app, the queue worker, the scheduler and the SSR server
# (see docker-compose.yml).

ARG PHP_VERSION=8.5

############################################
# Build: PHP dependencies, then the front end (client + SSR bundle).
# Wayfinder runs `php artisan` during the Vite build, so PHP and Node share this stage.
############################################
FROM serversideup/php:${PHP_VERSION}-cli AS build

USER root
RUN install-php-extensions intl bcmath exif gd \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/* \
    && corepack enable

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist

COPY package.json pnpm-lock.yaml ./
RUN corepack prepare --activate && pnpm install --frozen-lockfile

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && cp .env.example .env \
    && php artisan key:generate --force \
    && php artisan package:discover --ansi \
    && pnpm run build:ssr \
    && rm -rf node_modules .env storage/logs/*.log bootstrap/cache/*.php

############################################
# Runtime
############################################
FROM serversideup/php:${PHP_VERSION}-fpm-nginx

USER root
RUN install-php-extensions intl bcmath exif gd
# Node only runs the SSR server (`php artisan inertia:start-ssr`)
COPY --from=build /usr/bin/node /usr/local/bin/node
COPY --chmod=755 docker/entrypoint.d/ /etc/entrypoint.d/

ENV PHP_OPCACHE_ENABLE=1 \
    AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION_ISOLATION=false \
    LOG_CHANNEL=stderr \
    SSL_MODE=off

USER www-data
COPY --from=build --chown=www-data:www-data /app /var/www/html

EXPOSE 8080
