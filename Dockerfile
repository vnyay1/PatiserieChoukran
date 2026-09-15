# syntax=docker/dockerfile:1.7
#
# Image unique : nginx + php-fpm + worker de file + planificateur + SPA Vue,
# le tout servi sur un seul port. Le SPA et l'API partagent la même origine,
# ce qui supprime tout problème de CORS et de cookies tiers.
#
# Contexte de build attendu : la racine du dépôt.

# ---------------------------------------------------------------------------
# 1. Dépendances PHP
# ---------------------------------------------------------------------------
FROM composer:2.8 AS vendor
WORKDIR /app

COPY backend/composer.json backend/composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --ignore-platform-reqs \
        --no-scripts

COPY backend/ ./
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-scripts

# ---------------------------------------------------------------------------
# 2. Build du SPA Vue
# ---------------------------------------------------------------------------
FROM node:22-alpine AS spa
WORKDIR /app

# Même origine que l'API : le SPA appelle /api/v1 en relatif.
ARG VITE_API_URL=/api/v1
ENV VITE_API_URL=${VITE_API_URL}

COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY frontend/ ./
RUN npm run build

# ---------------------------------------------------------------------------
# 3. Image finale
# ---------------------------------------------------------------------------
FROM php:8.2-fpm-alpine AS app

ENV APP_ROOT=/var/www/html \
    SPA_ROOT=/var/www/spa \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR ${APP_ROOT}

RUN set -eux; \
    apk add --no-cache \
        bash \
        nginx \
        supervisor \
        tzdata \
        icu-libs \
        libzip \
        oniguruma \
        libpng \
        libjpeg-turbo \
        freetype \
        libwebp; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libwebp-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        zip; \
    apk del .build-deps; \
    rm -rf /var/cache/apk/*

COPY docker/php/app.ini      /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/opcache.ini  /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/php-fpm/www.conf /usr/local/etc/php-fpm.d/zz-www.conf
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/site.conf  /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh    /usr/local/bin/entrypoint

COPY --from=vendor /app ${APP_ROOT}
COPY --from=spa /app/dist ${SPA_ROOT}

RUN set -eux; \
    chmod +x /usr/local/bin/entrypoint; \
    rm -f /etc/nginx/http.d/default.conf.apk-new; \
    mkdir -p \
        ${APP_ROOT}/storage/framework/cache/data \
        ${APP_ROOT}/storage/framework/sessions \
        ${APP_ROOT}/storage/framework/views \
        ${APP_ROOT}/storage/logs \
        ${APP_ROOT}/storage/app/public \
        ${APP_ROOT}/bootstrap/cache \
        /var/lib/nginx/tmp \
        /var/log/supervisor \
        /run/nginx; \
    ln -sfn ${APP_ROOT}/storage/app/public ${APP_ROOT}/public/storage; \
    rm -f ${APP_ROOT}/bootstrap/cache/*.php; \
    php artisan package:discover --ansi; \
    chown -R www-data:www-data \
        ${APP_ROOT}/storage \
        ${APP_ROOT}/bootstrap/cache \
        /var/lib/nginx \
        /var/log/nginx \
        /run/nginx

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=5 \
    CMD wget -q -O /dev/null http://127.0.0.1/up || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
