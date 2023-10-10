ARG PHP_VERSION=8.2.11
ARG COMPOSER_VERSION=2.6.5

FROM composer:${COMPOSER_VERSION} as composer

FROM php:${PHP_VERSION}-cli-alpine as runtime

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.json
RUN composer update --no-dev

COPY merger merger
COPY src src

ENTRYPOINT ["/app/merger"]
