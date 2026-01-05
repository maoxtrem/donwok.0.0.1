# =====================
# BASE
# =====================
FROM php:8.3-cli-alpine AS base

WORKDIR /app

RUN apk add --no-cache \
    git \
    unzip \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    bash

RUN docker-php-ext-install \
    intl \
    pdo \
    pdo_mysql \
    zip \
    opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Código (se sobreescribe por volumen en dev)
COPY . .

# =====================
# DEV
# =====================
FROM base AS dev

RUN apk add --no-cache \
    nodejs \
    npm

RUN curl -sS https://get.symfony.com/cli/installer | bash \
 && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

ENV APP_ENV=dev
ENV APP_DEBUG=1

CMD ["symfony", "serve", "--no-tls", "--allow-http", "--listen-ip=0.0.0.0"]

# =====================
# PROD
# =====================
FROM php:8.3-fpm-alpine AS prod

WORKDIR /app

RUN apk add --no-cache \
    icu-dev \
    libzip-dev

RUN docker-php-ext-install \
    intl \
    pdo \
    pdo_mysql \
    zip \
    opcache

COPY --from=base /app /app

ENV APP_ENV=prod
ENV APP_DEBUG=0

CMD ["php-fpm"]
